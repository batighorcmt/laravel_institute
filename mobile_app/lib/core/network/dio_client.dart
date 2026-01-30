import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/env.dart';
import 'dart:developer' as developer;

class DioClient {
  static final DioClient _instance = DioClient._internal();
  factory DioClient() => _instance;
  DioClient._internal();

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
          final sp = await SharedPreferences.getInstance();
          final token = sp.getString('auth_token');
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
              'HTTP ${response.requestOptions.method} ${response.requestOptions.path} -> ${response.statusCode} | ${response.data}',
              name: 'DioClient',
            );
          } catch (_) {}
          handler.next(response);
        },
        onError: (err, handler) {
          try {
            developer.log(
              'HTTP ERROR ${err.requestOptions.method} ${err.requestOptions.path} -> ${err.response?.statusCode} | ${err.message} | ${err.response?.data}',
              name: 'DioClient',
            );
          } catch (_) {}
          handler.next(err);
        },
      ),
    );
  }
}
