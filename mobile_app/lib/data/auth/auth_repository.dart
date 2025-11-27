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
    final resp = await _dio.post(
      '/auth/login',
      data: {
        'username': username,
        'password': password,
        'device_name': deviceName,
      },
    );
    final data = resp.data as Map<String, dynamic>;
    final token = data['token'] as String?;
    if (token != null) {
      final sp = await SharedPreferences.getInstance();
      await sp.setString('auth_token', token);
    }
    return data;
  }

  Future<void> logout() async {
    try {
      await _dio.post('/auth/logout');
    } finally {
      final sp = await SharedPreferences.getInstance();
      await sp.remove('auth_token');
    }
  }

  Future<Map<String, dynamic>> me() async {
    final resp = await _dio.get('/me');
    return resp.data as Map<String, dynamic>;
  }
}
