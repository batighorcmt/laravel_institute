import 'package:dio/dio.dart';

class TeacherStudentsRepository {
  final Dio _dio;
  TeacherStudentsRepository(this._dio);

  Future<Map<String, dynamic>> fetchStudents({
    int page = 1,
    String? search,
    String? classId,
    String? sectionId,
    String? groupId,
    String? gender,
  }) async {
    final params = <String, dynamic>{'page': page};
    if (search != null && search.isNotEmpty) params['search'] = search;
    if (classId != null && classId.isNotEmpty) params['class_id'] = classId;
    if (sectionId != null && sectionId.isNotEmpty)
      params['section_id'] = sectionId;
    if (groupId != null && groupId.isNotEmpty) params['group_id'] = groupId;
    if (gender != null && gender.isNotEmpty) params['gender'] = gender;

    try {
      // Use relative path so DioClient base `/api/v1` isn't duplicated
      final res = await _dio.get('teacher/students', queryParameters: params);
      return res.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      final body = e.response?.data;
      throw Exception(
        'Students load failed (${status ?? 'n/a'}): ${body is Map ? body['message'] ?? body.toString() : body?.toString() ?? e.message}',
      );
    }
  }

  Future<Map<String, dynamic>> fetchStudentProfile(String studentId) async {
    try {
      final res = await _dio.get('teacher/students/$studentId');
      return res.data as Map<String, dynamic>;
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      final body = e.response?.data;
      throw Exception(
        'Profile load failed (${status ?? 'n/a'}): ${body is Map ? body['message'] ?? body.toString() : body?.toString() ?? e.message}',
      );
    }
  }

  Future<List<Map<String, dynamic>>> fetchClasses() async {
    try {
      final res = await _dio.get('classes');
      final data = res.data;
      if (data is List) {
        return data.cast<Map<String, dynamic>>();
      }
      if (data is Map<String, dynamic> && data['data'] is List) {
        return (data['data'] as List).cast<Map<String, dynamic>>();
      }
    } catch (_) {}
    return [];
  }

  Future<List<Map<String, dynamic>>> fetchSections({String? classId}) async {
    try {
      final res = await _dio.get(
        'sections',
        queryParameters: {if (classId != null) 'class_id': classId},
      );
      final data = res.data;
      if (data is List) {
        return data.cast<Map<String, dynamic>>();
      }
      if (data is Map<String, dynamic> && data['data'] is List) {
        return (data['data'] as List).cast<Map<String, dynamic>>();
      }
    } catch (_) {}
    return [];
  }

  Future<List<Map<String, dynamic>>> fetchGroups() async {
    try {
      final res = await _dio.get('groups');
      final data = res.data;
      if (data is List) {
        return data.cast<Map<String, dynamic>>();
      }
      if (data is Map<String, dynamic> && data['data'] is List) {
        return (data['data'] as List).cast<Map<String, dynamic>>();
      }
    } catch (_) {}
    return [];
  }
}
