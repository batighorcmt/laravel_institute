import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';

/// Class-teacher review of STUDENT leave applications — distinct from
/// TeacherLeaveRepository, which is the teacher's own leave requests.
class StudentLeaveRepository {
  final Dio _dio = DioClient().dio;

  Future<List<Map<String, dynamic>>> listLeaves({String? status}) async {
    final resp = await _dio.get(
      'teacher/student-leaves',
      queryParameters: status != null && status.isNotEmpty
          ? {'status': status}
          : null,
    );
    final data = resp.data;
    if (data is List) return data.cast<Map<String, dynamic>>();
    if (data is Map<String, dynamic> && data['data'] is List) {
      return (data['data'] as List).cast<Map<String, dynamic>>();
    }
    return [];
  }

  Future<Map<String, dynamic>> getLeave(int id) async {
    final resp = await _dio.get('teacher/student-leaves/$id');
    final data = resp.data;
    if (data is Map<String, dynamic> && data['data'] is Map) {
      return Map<String, dynamic>.from(data['data']);
    }
    return Map<String, dynamic>.from(data);
  }

  Future<void> review(int id, {required String action, String? note}) async {
    await _dio.post(
      'teacher/student-leaves/$id/review',
      data: {'action': action, if (note != null && note.isNotEmpty) 'note': note},
    );
  }
}
