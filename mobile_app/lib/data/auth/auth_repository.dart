// AuthRepository.dart (Final Fixed Version)

import 'dart:convert';
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
        'LOGIN <-- username:${_mask(username)}',
        name: "AuthRepository",
      );

      final response = await _dio.post(
        "auth/login",
        data: {
          "username": username,
          "password": password,
          "device_name": deviceName,
        },
      );

      developer.log(
        "RAW RESPONSE TYPE = ${response.data.runtimeType}",
        name: "AuthRepository",
      );
      developer.log(
        "RAW RESPONSE DATA = ${response.data}",
        name: "AuthRepository",
      );

      // 🔥 MAIN FIX --- Safe parsing for all cases
      Map<String, dynamic> data;

      if (response.data is Map<String, dynamic>) {
        data = response.data;
      } else if (response.data is String) {
        final String s = response.data;
        // Trim any non-JSON prefix (e.g., BOM or text before '{'/'[')
        final int iBrace = s.indexOf('{');
        final int iBracket = s.indexOf('[');
        int start = -1;
        if (iBrace >= 0 && iBracket >= 0) {
          start = iBrace < iBracket ? iBrace : iBracket;
        } else if (iBrace >= 0) {
          start = iBrace;
        } else if (iBracket >= 0) {
          start = iBracket;
        }
        final String trimmed = start >= 0 ? s.substring(start) : s;
        data = jsonDecode(trimmed);
      } else if (response.data is List) {
        throw Exception("❌ API returned LIST — expected MAP JSON object.");
      } else {
        throw Exception(
          "❌ Invalid API Structure — must return JSON Map e.g. {status:true}",
        );
      }

      // Token Save
      if (data["token"] != null) {
        final sp = await SharedPreferences.getInstance();
        await sp.setString("auth_token", data["token"]);
      }

      return data;
    }
    // ⛔ Error Handler
    on DioException catch (e) {
      final serverMsg = (e.response?.data is Map<String, dynamic>)
          ? e.response?.data["message"]
          : e.response?.data.toString();

      developer.log("LOGIN ERROR: $serverMsg", name: "AuthRepository");

      throw Exception(serverMsg ?? "Network Error");
    }
  }

  Future<void> logout() async {
    try {
      await _dio.post("auth/logout");
    } catch (_) {
    } finally {
      final sp = await SharedPreferences.getInstance();
      sp.remove("auth_token");
    }
  }

  Future<Map<String, dynamic>> me() async {
    final resp = await _dio.get("me");
    if (resp.data is Map<String, dynamic>) return resp.data;
    if (resp.data is Map) return Map<String, dynamic>.from(resp.data);
    if (resp.data is String) {
      final String s = resp.data;
      final int iBrace = s.indexOf('{');
      final int iBracket = s.indexOf('[');
      int start = -1;
      if (iBrace >= 0 && iBracket >= 0) {
        start = iBrace < iBracket ? iBrace : iBracket;
      } else if (iBrace >= 0) {
        start = iBrace;
      } else if (iBracket >= 0) {
        start = iBracket;
      }
      final String trimmed = start >= 0 ? s.substring(start) : s;
      final decoded = jsonDecode(trimmed);
      if (decoded is Map<String, dynamic>) return decoded;
      if (decoded is Map) return Map<String, dynamic>.from(decoded);
    }
    return {};
  }

  // mask email
  String _mask(String input) {
    if (input.length < 3) return "***";
    if (input.contains("@")) {
      final p = input.split("@");
      return "${p[0][0]}***${p[0].substring(p[0].length - 1)}@${p[1]}";
    }
    return "${input[0]}***${input[input.length - 1]}";
  }

  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    try {
      if (newPassword != confirmPassword) {
        throw Exception("New password and confirm password do not match");
      }
      final resp = await _dio.post(
        "auth/change-password",
        data: {
          "current_password": currentPassword,
          "new_password": newPassword,
          "new_password_confirmation": confirmPassword,
        },
      );
      if (resp.statusCode != 200) {
        throw Exception(resp.data['message'] ?? 'Failed to update password');
      }
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response?.data['message'] ?? e.message)
          : e.message;
      throw Exception(msg);
    }
  }
}
