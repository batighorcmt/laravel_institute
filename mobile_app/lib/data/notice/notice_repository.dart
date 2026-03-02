import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:http_parser/http_parser.dart';
import '../../core/network/dio_client.dart';

class NoticeRepository {
  final Dio _dio = DioClient().dio;

  Future<List<dynamic>> getNotices({int? schoolId}) async {
    final resp = await _dio.get('notices', queryParameters: schoolId != null ? {'school_id': schoolId} : {});
    return _parseList(resp.data);
  }

  Future<Map<String, dynamic>> getNoticeDetails(int id) async {
    final resp = await _dio.get('notices/$id');
    return resp.data['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> createNotice(Map<String, dynamic> data) async {
    final resp = await _dio.post('notices', data: data);
    return resp.data;
  }

  Future<Map<String, dynamic>> updateNotice(int id, Map<String, dynamic> data) async {
    final resp = await _dio.put('notices/$id', data: data);
    return resp.data;
  }

  Future<void> deleteNotice(int id) async {
    await _dio.delete('notices/$id');
  }

  Future<Map<String, dynamic>> getNoticeStats(int id) async {
    final resp = await _dio.get('notices/$id/stats');
    return resp.data as Map<String, dynamic>;
  }

  Future<void> markAsRead(int id) async {
    await _dio.post('notices/$id/read');
  }

  Future<void> submitVoiceReply(int id, String filePath, double duration) async {
    debugPrint('[NoticeRepo] submitVoiceReply: id=$id path=$filePath duration=$duration');
    
    // Verify file exists
    final file = File(filePath);
    if (!await file.exists()) {
      throw Exception('Audio file not found at: $filePath');
    }
    final fileSize = await file.length();
    debugPrint('[NoticeRepo] File exists, size=${fileSize}B');

    final formData = FormData.fromMap({
      'voice': await MultipartFile.fromFile(
        filePath,
        filename: 'reply.m4a',
        // Explicitly set audio/mp4 MIME for .m4a (iOS AAC recorded files)
        contentType: MediaType('audio', 'mp4'),
      ),
      'duration': duration,
    });
    debugPrint('[NoticeRepo] FormData created, posting to notices/$id/reply ...');
    final resp = await _dio.post('notices/$id/reply', data: formData);
    debugPrint('[NoticeRepo] Response: ${resp.statusCode} | ${resp.data}');
  }

  // Meta data for targeting
  Future<List<dynamic>> getClasses() async {
    final resp = await _dio.get('meta/classes');
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getSections() async {
    final resp = await _dio.get('meta/sections');
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getGroups() async {
    final resp = await _dio.get('meta/groups');
    return _parseList(resp.data);
  }

  Future<List<dynamic>> searchStudents(String query) async {
    final resp = await _dio.get('principal/students/search', queryParameters: {'q': query});
    return _parseList(resp.data);
  }

  List<dynamic> _parseList(dynamic data) {
    if (data is List) return data;
    if (data is Map<String, dynamic> && data['data'] is List) {
      return data['data'] as List<dynamic>;
    }
    return [];
  }
}
