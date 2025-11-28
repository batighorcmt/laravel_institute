import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';

class TeacherDirectoryRepository {
  final Dio _dio = DioClient().dio;

  Future<Map<String, dynamic>> fetchTeachersPage({
    int page = 1,
    String? search,
    String? designation,
  }) async {
    final qp = <String, dynamic>{'page': page};
    if (search != null && search.isNotEmpty) qp['search'] = search;
    if (designation != null && designation.isNotEmpty)
      qp['designation'] = designation;
    final resp = await _dio.get('teachers', queryParameters: qp);
    final data = resp.data;
    if (data is Map<String, dynamic>) {
      final items = (data['data'] is List)
          ? (data['data'] as List).cast<Map<String, dynamic>>()
          : <Map<String, dynamic>>[];
      final meta = (data['meta'] is Map<String, dynamic>)
          ? Map<String, dynamic>.from(data['meta'])
          : <String, dynamic>{};
      final designations = (data['designations'] is List)
          ? (data['designations'] as List).cast<String>()
          : <String>[];
      return {'items': items, 'meta': meta, 'designations': designations};
    }
    if (data is List) {
      return {
        'items': data.cast<Map<String, dynamic>>(),
        'meta': {},
        'designations': <String>[],
      };
    }
    return {
      'items': <Map<String, dynamic>>[],
      'meta': {},
      'designations': <String>[],
    };
  }
}
