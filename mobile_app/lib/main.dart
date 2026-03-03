import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'presentation/routes/app_router.dart';
import 'theme/app_theme.dart';
import 'core/network/dio_client.dart';
import 'core/config/env.dart';
import 'dart:developer' as developer;
import 'core/services/notification_service.dart';

@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  developer.log('Handling background message: ${message.messageId}');
}

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  try {
    // 1. Initialize Firebase
    await Firebase.initializeApp();
    
    // 2. Initialize Dio
    await DioClient().init();
    
    // 3. Set Background Handler
    FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
    
    // 4. Initialize Notification Service
    await NotificationService().init();
    
    developer.log('Firebase and Notifications initialized');
  } catch (e) {
    developer.log('Initialization Error: $e');
  }
  developer.log(
    'App starting with API_BASE_URL=${Env.apiBaseUrl}',
    name: 'Main',
  );
  runApp(const ProviderScope(child: MyApp()));
}
class MyApp extends ConsumerWidget {
  const MyApp({super.key});

  // This widget is the root of your application.
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);
    // Force light mode per request; dark mode optional.
    // final mode = ref.watch(themeModeProvider);
    return MaterialApp.router(
      title: 'Batighor EIMS',
      theme: AppTheme.light(),
      // darkTheme: AppTheme.dark(),
      themeMode: ThemeMode.light,
      routerConfig: router,
    );
  }
}
