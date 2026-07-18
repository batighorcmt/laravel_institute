import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:go_router/go_router.dart';
import 'presentation/routes/app_router.dart';
import 'core/navigation.dart';
import 'theme/app_theme.dart';
import 'core/network/dio_client.dart';
import 'core/config/env.dart';
import 'dart:developer' as developer;
import 'core/services/notification_service.dart';
import 'presentation/widgets/update_checker.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  try {
    // 1. Initialize Firebase
    await Firebase.initializeApp();

    // 2. Initialize Dio
    await DioClient().init();

    // 3. Initialize Notification Service
    await NotificationService().init();

    developer.log('Firebase and Notifications initialized');
  } catch (e) {
    developer.log('Initialization Error: $e');
  }
  
  // Handle notification which opened the app from terminated state
  try {
    final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      final data = initialMessage.data;
      final type = data['type'];
      final id = data['id'] ?? data['notice_id'] ?? data['noticeId'];
      if (type == 'notice' && id != null) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          final ctx = rootNavigatorKey.currentContext;
          if (ctx != null) {
            try {
              GoRouter.of(ctx).push('/notices/${id.toString()}');
            } catch (_) {}
          }
        });
      }
    }
  } catch (_) {}

  developer.log(
    'App starting with API_BASE_URL=${Env.apiBaseUrl}',
    name: 'Main',
  );
  runApp(
    UncontrolledProviderScope(
      container: appProviderContainer,
      child: const MyApp(),
    ),
  );
}

class MyApp extends ConsumerWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);
    return UpdateChecker(
      child: MaterialApp.router(
        title: 'Batighor EIMS',
        theme: AppTheme.light(),
        themeMode: ThemeMode.light,
        routerConfig: router,
      ),
    );
  }
}
