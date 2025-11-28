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

    final res = await _dio.get('/v1/teacher/students', queryParameters: params);
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> fetchStudentProfile(String studentId) async {
    final res = await _dio.get('/v1/teacher/students/$studentId');
    return res.data as Map<String, dynamic>;
  }
}
