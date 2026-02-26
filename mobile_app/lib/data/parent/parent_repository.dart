import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';

class ParentRepository {
  final Dio _dio = DioClient().dio;

  Future<List<dynamic>> getChildren() async {
    final resp = await _dio.get('parent/children');
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getHomework({String? date, int? studentId}) async {
    final params = <String, dynamic>{};
    if (date != null) params['date'] = date;
    if (studentId != null) params['student_id'] = studentId;
    final resp = await _dio.get('parent/homework', queryParameters: params);
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getAttendance({int? studentId, int? month, int? year}) async {
    final params = <String, dynamic>{};
    if (studentId != null) params['student_id'] = studentId;
    if (month != null) params['month'] = month;
    if (year != null) params['year'] = year;
    final resp = await _dio.get('parent/attendance', queryParameters: params);
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getRoutine({int? studentId}) async {
    final resp = await _dio.get('parent/routine', queryParameters: studentId != null ? {'student_id': studentId} : {});
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getLessonEvaluations({
    int? studentId,
    String? fromDate,
    String? toDate,
    int? subjectId,
    int? teacherId,
    String? status,
  }) async {
    final params = <String, dynamic>{};
    if (studentId != null) params['student_id'] = studentId;
    if (fromDate != null) params['from_date'] = fromDate;
    if (toDate != null) params['to_date'] = toDate;
    if (subjectId != null) params['subject_id'] = subjectId;
    if (teacherId != null) params['teacher_id'] = teacherId;
    if (status != null) params['status'] = status;

    final resp = await _dio.get('parent/lesson-evaluations', queryParameters: params);
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getLeaves() async {
    final resp = await _dio.get('parent/leaves');
    return _parseList(resp.data);
  }

  Future<Map<String, dynamic>> submitLeave(Map<String, dynamic> data) async {
    final resp = await _dio.post('parent/leaves', data: data);
    return resp.data;
  }

  Future<List<dynamic>> getNotices() async {
    final resp = await _dio.get('notices');
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getTeachers({int? studentId}) async {
    final resp = await _dio.get('parent/teachers', queryParameters: studentId != null ? {'student_id': studentId} : {});
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getSubjects({int? studentId}) async {
    final resp = await _dio.get('parent/subjects', queryParameters: studentId != null ? {'student_id': studentId} : {});
    return _parseList(resp.data);
  }

  Future<List<dynamic>> getFeedback() async {
    final resp = await _dio.get('parent/feedback');
    return _parseList(resp.data);
  }

  Future<Map<String, dynamic>> submitFeedback(Map<String, dynamic> data) async {
    final resp = await _dio.post('parent/feedback', data: data);
    return resp.data;
  }

  Future<Map<String, dynamic>> getProfile({int? studentId}) async {
    final resp = await _dio.get('parent/profile', queryParameters: studentId != null ? {'student_id': studentId} : {});
    return resp.data['data'] as Map<String, dynamic>;
  }

  Future<void> updatePhoto(String filePath) async {
    final formData = FormData.fromMap({
      'photo': await MultipartFile.fromFile(filePath, filename: 'photo.jpg'),
    });
    await _dio.post('parent/update-photo', data: formData);
  }

  Future<void> changePassword(String current, String novel, String confirm) async {
    await _dio.post('auth/change-password', data: {
      'current_password': current,
      'new_password': novel,
      'new_password_confirmation': confirm,
    });
  }

  List<dynamic> _parseList(dynamic data) {
    if (data is List) return data;
    if (data is Map<String, dynamic> && data['data'] is List) {
      return data['data'] as List<dynamic>;
    }
    return [];
  }
}
