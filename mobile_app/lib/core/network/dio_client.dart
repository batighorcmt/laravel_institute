import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:go_router/go_router.dart';
import '../config/env.dart';
import '../navigation.dart';
import '../../presentation/state/auth_state.dart';
import 'dart:developer' as developer;

class DioClient {
  static final DioClient _instance = DioClient._internal();
  factory DioClient() => _instance;
  DioClient._internal();

  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();

  // Ensure baseUrl always ends with a trailing slash for safe concatenation
  String get _normalizedBaseUrl =>
      Env.apiBaseUrl.endsWith('/') ? Env.apiBaseUrl : '${Env.apiBaseUrl}/';

  final Dio dio = Dio(
    BaseOptions(
      baseUrl: Env.apiBaseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 20),
      headers: {'Accept': 'application/json'},
    ),
  );

  Future<void> init() async {
    // Update dio's baseUrl in case the dart-define or default lacked a slash
    dio.options.baseUrl = _normalizedBaseUrl;
    developer.log(
      'Initializing Dio | baseUrl=${dio.options.baseUrl}',
      name: 'DioClient',
    );
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _secureStorage.read(key: 'auth_token');
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          developer.log(
            'HTTP ${options.method} ${options.path}',
            name: 'DioClient',
          );
          handler.next(options);
        },
        onResponse: (response, handler) {
          try {
            developer.log(
              'HTTP ${response.requestOptions.method} ${response.requestOptions.path} -> ${response.statusCode}',
              name: 'DioClient',
            );
          } catch (_) {}
          handler.next(response);
        },
        onError: (err, handler) async {
          try {
            developer.log(
              'HTTP ERROR ${err.requestOptions.method} ${err.requestOptions.path} -> ${err.response?.statusCode} | ${err.message}',
              name: 'DioClient',
            );
          } catch (_) {}

          if (err.response?.statusCode == 401) {
            try {
              await _secureStorage.delete(key: 'auth_token');
            } catch (_) {}
            try {
              // Reset auth state (and dependent cached data) so GoRouter's
              // redirect logic sees the user as logged out instead of
              // bouncing the forced navigation below back to a dashboard.
              await appProviderContainer
                  .read(authProvider.notifier)
                  .forceLogout();
            } catch (_) {}
            try {
              final ctx = rootNavigatorKey.currentContext;
              if (ctx != null && ctx.mounted) {
                GoRouter.of(ctx).go('/login');
              }
            } catch (_) {}
          }

          handler.next(err);
        },
      ),
    );
  }
}
