import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';

class TeacherDirectoryRepository {
  final Dio _dio = DioClient().dio;

  Future<List<Map<String, dynamic>>> fetchTeachers({String? search}) async {
    final resp = await _dio.get(
      'teachers',
      queryParameters: (search != null && search.isNotEmpty)
          ? {'search': search}
          : null,
    );
    final data = resp.data;
    if (data is List) {
      return data.cast<Map<String, dynamic>>();
    }
    if (data is Map<String, dynamic> && data['data'] is List) {
      return (data['data'] as List).cast<Map<String, dynamic>>();
    }
    return [];
  }
}
