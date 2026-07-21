import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';

/// Review of STUDENT leave applications — distinct from TeacherLeaveRepository,
/// which is the teacher's own leave requests. [basePath] switches between the
/// class-teacher-scoped endpoint (only their own sections) and the
/// principal-scoped one (every section in the school); both return the same
/// StudentLeaveResource shape, so this repository (and the list/detail pages
/// built on it) is shared by both roles.
class StudentLeaveRepository {
  final Dio _dio = DioClient().dio;
  final String basePath;

  StudentLeaveRepository({this.basePath = 'teacher/student-leaves'});

  Future<List<Map<String, dynamic>>> listLeaves({String? status}) async {
    final resp = await _dio.get(
      basePath,
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
    final resp = await _dio.get('$basePath/$id');
    final data = resp.data;
    if (data is Map<String, dynamic> && data['data'] is Map) {
      return Map<String, dynamic>.from(data['data']);
    }
    return Map<String, dynamic>.from(data);
  }

  Future<void> review(int id, {required String action, String? note}) async {
    await _dio.post(
      '$basePath/$id/review',
      data: {'action': action, if (note != null && note.isNotEmpty) 'note': note},
    );
  }
}
