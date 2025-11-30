import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:firebase_core/firebase_core.dart';
import 'presentation/routes/app_router.dart';
import 'theme/app_theme.dart';
import 'core/network/dio_client.dart';
import 'core/config/env.dart';
import 'dart:developer' as developer;

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Initialize Firebase if config exists; otherwise continue without it.
  try {
    await Firebase.initializeApp();
  } catch (_) {
    // No firebase options configured; skip for now.
  }
  await DioClient().init();
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
