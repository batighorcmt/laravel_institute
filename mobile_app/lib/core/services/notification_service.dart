import 'dart:io';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter/material.dart';
import 'dart:developer' as developer;
import 'dart:convert';
import '../navigation.dart';
import 'package:go_router/go_router.dart';
import '../network/dio_client.dart';
import 'package:shared_preferences/shared_preferences.dart';

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FirebaseMessaging _fcm = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  Future<void> init() async {
    // 1. Request permissions for iOS & Android 13+
    NotificationSettings settings = await _fcm.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      developer.log('User granted notification permission');
    }

    // 2. Initialize Local Notifications for Foreground messages
    // Create Android channel (supports custom sound if provided in res/raw)
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher');

    const DarwinInitializationSettings initializationSettingsIOS =
        DarwinInitializationSettings(
          // You can add onDidReceiveLocalNotification callback here if needed
        );

    const InitializationSettings initializationSettings =
        InitializationSettings(
          android: initializationSettingsAndroid,
          iOS: initializationSettingsIOS,
        );

    await _localNotifications.initialize(
      initializationSettings,
      onDidReceiveNotificationResponse: (details) {
        try {
          if (details.payload != null && details.payload!.isNotEmpty) {
            final Map<String, dynamic> data = jsonDecode(details.payload!);
            final type = data['type'];
            final id = data['id'] ?? data['notice_id'] ?? data['noticeId'];
            if (type == 'notice' && id != null) {
              final ctx = rootNavigatorKey.currentContext;
              if (ctx != null)
                GoRouter.of(ctx).push('/notices/${id.toString()}');
            }
          }
        } catch (e) {
          developer.log('Error handling local notification tap: $e');
        }
      },
    );

    // Android notification channel with sound support (place raw/notice_sound.mp3)
    const AndroidNotificationChannel channel = AndroidNotificationChannel(
      'notice_channel', // New channel ID to ensure internal update
      'Notice Notifications',
      description: 'Notifications for new notices',
      importance: Importance.max,
      playSound: true,
      sound: RawResourceAndroidNotificationSound('notice_sound'),
    );

    await _localNotifications
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.createNotificationChannel(channel);

    // iOS: show alert, badge and play sound when app is in foreground
    await _fcm.setForegroundNotificationPresentationOptions(
      alert: true,
      badge: true,
      sound: true,
    );

    // 3. Handle messages
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);
    FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

    // When user taps a notification (app in background or terminated)
    FirebaseMessaging.onMessageOpenedApp.listen((message) {
      try {
        final data = message.data;
        final type = data['type'];
        final id = data['id'] ?? data['notice_id'] ?? data['noticeId'];
        if (type == 'notice' && id != null) {
          final ctx = rootNavigatorKey.currentContext;
          if (ctx != null) GoRouter.of(ctx).push('/notices/${id.toString()}');
        }
      } catch (e) {
        developer.log('Error handling onMessageOpenedApp: $e');
      }
    });

    // 4. Token management
    String? token = await _fcm.getToken();
    if (token != null) {
      _sendTokenToServer(token);
    }

    // If previous attempts failed, retry any pending token
    try {
      final prefs = await SharedPreferences.getInstance();
      final pending = prefs.getString('pending_device_token');
      if (pending != null && pending.isNotEmpty) {
        await _sendTokenToServer(pending);
        prefs.remove('pending_device_token');
      }
    } catch (_) {}

    _fcm.onTokenRefresh.listen((token) {
      _sendTokenToServer(token);
    });
  }

  static Future<void> _firebaseMessagingBackgroundHandler(
    RemoteMessage message,
  ) async {
    // Handle background message if needed
    developer.log('Handling a background message: ${message.messageId}');
  }

  void _handleForegroundMessage(RemoteMessage message) {
    RemoteNotification? notification = message.notification;
    AndroidNotification? android = message.notification?.android;

    if (notification != null) {
      final soundName = message.data['sound'] ?? 'notice_sound';
      final androidDetails = AndroidNotificationDetails(
        'notice_channel',
        'Notice Notifications',
        channelDescription: 'Notifications for new notices',
        importance: Importance.max,
        priority: Priority.high,
        icon: '@mipmap/ic_launcher',
        playSound: true,
        sound: RawResourceAndroidNotificationSound(soundName),
        visibility: NotificationVisibility.public,
      );

      final iosDetails = DarwinNotificationDetails(
        presentAlert: true,
        presentBadge: true,
        presentSound: true,
        sound: '${message.data['sound'] ?? 'notice_sound'}.mp3',
      );

      _localNotifications.show(
        notification.hashCode,
        notification.title,
        notification.body,
        NotificationDetails(android: androidDetails, iOS: iosDetails),
        payload: jsonEncode(message.data),
      );
    }
  }

  Future<void> _sendTokenToServer(String token) async {
    try {
      final dio = DioClient().dio;
      await dio.post(
        'devices',
        data: {
          'token': token,
          'platform': Platform.isAndroid ? 'android' : 'ios',
        },
      );
      developer.log('Device token sent to server: $token');
      try {
        final prefs = await SharedPreferences.getInstance();
        if (prefs.getString('pending_device_token') == token) {
          prefs.remove('pending_device_token');
        }
      } catch (_) {}
    } catch (e) {
      developer.log('Error sending token to server: $e');
      try {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('pending_device_token', token);
      } catch (_) {}
    }
  }

  /// Public helper to (re-)register current FCM token for the authenticated user.
  Future<void> registerDeviceForUser() async {
    try {
      String? token = await _fcm.getToken();
      if (token != null) {
        await _sendTokenToServer(token);
      }
      // also retry pending
      try {
        final prefs = await SharedPreferences.getInstance();
        final pending = prefs.getString('pending_device_token');
        if (pending != null && pending.isNotEmpty) {
          await _sendTokenToServer(pending);
          prefs.remove('pending_device_token');
        }
      } catch (_) {}
    } catch (e) {
      developer.log('Error in registerDeviceForUser: $e');
    }
  }
}
