import 'dart:developer' as developer;
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/network/dio_client.dart';

class AuthRepository {
  final Dio _dio = DioClient().dio;

  Future<Map<String, dynamic>> login({
    required String username,
    required String password,
    required String deviceName,
  }) async {
    try {
      developer.log(
        'Login request -> POST /auth/login | baseUrl=${_dio.options.baseUrl} | username=${_mask(username)} | pwdLen=${password.length}',
        name: 'AuthRepository',
      );
      final resp = await _dio.post(
        '/auth/login',
        data: {
          'username': username,
          'password': password,
          'device_name': deviceName,
        },
      );
      final data = resp.data as Map<String, dynamic>;
      developer.log(
        'Login response <- status=${resp.statusCode} | hasToken=${data['token'] != null}',
        name: 'AuthRepository',
      );
      final token = data['token'] as String?;
      if (token != null) {
        final sp = await SharedPreferences.getInstance();
        await sp.setString('auth_token', token);
      }
      return data;
    } on DioException catch (e) {
      // Surface server message if available
      final serverMsg = (e.response?.data is Map<String, dynamic>)
          ? (e.response!.data['message'] as String?)
          : null;
      developer.log(
        'Login error !! type=${e.type} status=${e.response?.statusCode} message=${serverMsg ?? e.message} data=${e.response?.data}',
        name: 'AuthRepository',
        error: e,
        stackTrace: e.stackTrace,
      );
      throw Exception(serverMsg ?? 'Network error: ${e.message}');
    }
  }

  Future<void> logout() async {
    try {
      developer.log(
        'Logout request -> POST /auth/logout',
        name: 'AuthRepository',
      );
      final resp = await _dio.post('/auth/logout');
      developer.log(
        'Logout response <- status=${resp.statusCode}',
        name: 'AuthRepository',
      );
    } catch (_) {
      // ignore network errors on logout
    } finally {
      final sp = await SharedPreferences.getInstance();
      await sp.remove('auth_token');
    }
  }

  Future<Map<String, dynamic>> me() async {
    developer.log('Me request -> GET /me', name: 'AuthRepository');
    final resp = await _dio.get('/me');
    developer.log(
      'Me response <- status=${resp.statusCode}',
      name: 'AuthRepository',
    );
    return resp.data as Map<String, dynamic>;
  }

  // Redact sensitive text for logs
  String _mask(String input) {
    if (input.isEmpty) return '';
    if (input.contains('@')) {
      final parts = input.split('@');
      final name = parts[0];
      if (name.length <= 2) return '*' * name.length + '@' + parts[1];
      final masked =
          name[0] + ('*' * (name.length - 2)) + name[name.length - 1];
      return '$masked@${parts[1]}';
    }
    if (input.length <= 2) return '*' * input.length;
    return input[0] + ('*' * (input.length - 2)) + input[input.length - 1];
  }
}
