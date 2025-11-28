import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';

class TeacherLeaveRepository {
  final Dio _dio = DioClient().dio;

  Future<List<Map<String, dynamic>>> listLeaves({String? status}) async {
    final resp = await _dio.get(
      'teacher/leaves',
      queryParameters: status != null && status.isNotEmpty
          ? {'status': status}
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

  Future<Map<String, dynamic>> applyLeave({
    required String startDate,
    required String endDate,
    required String reason,
    String? type,
  }) async {
    final resp = await _dio.post(
      'teacher/leaves',
      data: {
        'start_date': startDate,
        'end_date': endDate,
        'reason': reason,
        if (type != null && type.isNotEmpty) 'type': type,
      },
    );
    if (resp.data is Map<String, dynamic>) return resp.data;
    return {'message': 'Saved'};
  }
}
