import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';

class TeacherExamRepository {
  final Dio _dio = DioClient().dio;

  Future<Map<String, dynamic>> getExamStatus() async {
    final resp = await _dio.get('teacher/exams');
    return resp.data;
  }

  Future<List<dynamic>> getTodaysDuty({int? planId, String? date}) async {
    final resp = await _dio.get(
      'teacher/exams/todays-duty',
      queryParameters: {
        if (planId != null) 'plan_id': planId,
        if (date != null) 'date': date,
      },
    );
    return resp.data;
  }

  Future<Map<String, dynamic>> getDutyMeta({int? planId}) async {
    final resp = await _dio.get(
      'teacher/exams/duty-meta',
      queryParameters: {if (planId != null) 'plan_id': planId},
    );
    return resp.data;
  }

  Future<Map<String, dynamic>> findSeat(String find, int? planId) async {
    final resp = await _dio.get(
      'teacher/exams/find-seat',
      queryParameters: {'find': find, 'plan_id': planId},
    );
    return resp.data;
  }

  Future<Map<String, dynamic>> getMarkEntryMeta() async {
    final resp = await _dio.get('teacher/exams/mark-entry/meta');
    return resp.data;
  }

  Future<List<dynamic>> getExams(int academicYearId, int classId, {String? status}) async {
    final resp = await _dio.get(
      'teacher/exams/mark-entry/exams',
      queryParameters: {
        'academic_year_id': academicYearId,
        'class_id': classId,
        if (status != null) 'status': status,
      },
    );
    return resp.data;
  }

  Future<List<dynamic>> getSubjects(int examId) async {
    final resp = await _dio.get(
      'teacher/exams/mark-entry/subjects',
      queryParameters: {'exam_id': examId},
    );
    return resp.data;
  }

  Future<Map<String, dynamic>> getStudentsForMarkEntry({
    required int examId,
    required int subjectId,
    required int classId,
  }) async {
    final resp = await _dio.get(
      'teacher/exams/mark-entry/students',
      queryParameters: {
        'exam_id': examId,
        'subject_id': subjectId,
        'class_id': classId,
      },
    );
    return resp.data;
  }

  Future<Map<String, dynamic>?> saveMarkResult({
    required int examId,
    required int examSubjectId,
    required int studentId,
    double? creative,
    double? mcq,
    double? practical,
    bool isAbsent = false,
    String? remarks,
  }) async {
    final resp = await _dio.post(
      'teacher/exams/mark-entry/save-mark',
      data: {
        'exam_id': examId,
        'exam_subject_id': examSubjectId,
        'student_id': studentId,
        'creative_marks': creative,
        'mcq_marks': mcq,
        'practical_marks': practical,
        'is_absent': isAbsent,
        'remarks': remarks,
      },
    );
    return resp.data;
  }

  Future<Map<String, dynamic>> getAttendanceReport(int? planId, String? date) async {
    final resp = await _dio.get(
      'teacher/exams/attendance-report',
      queryParameters: {
        if (planId != null) 'plan_id': planId,
        if (date != null) 'date': date,
      },
    );
    return resp.data;
  }

  Future<Map<String, dynamic>> getRoomAttendance({
    required int planId,
    required int roomId,
    String? date,
  }) async {
    final resp = await _dio.get(
      'teacher/exams/room-attendance',
      queryParameters: {
        'plan_id': planId,
        'room_id': roomId,
        if (date != null) 'date': date,
      },
    );
    return resp.data;
  }

  Future<Map<String, dynamic>> submitRoomAttendance({
    required int planId,
    required int roomId,
    required String date,
    required int studentId,
    required String status,
  }) async {
    final resp = await _dio.post(
      'teacher/exams/submit-room-attendance',
      data: {
        'plan_id': planId,
        'room_id': roomId,
        'date': date,
        'student_id': studentId,
        'status': status,
      },
    );
    return resp.data;
  }

  Future<Map<String, dynamic>> bulkSubmitRoomAttendance({
    required int planId,
    required int roomId,
    required String date,
    required String status,
  }) async {
    final resp = await _dio.post(
      'teacher/exams/bulk-submit-room-attendance',
      data: {
        'plan_id': planId,
        'room_id': roomId,
        'date': date,
        'status': status,
      },
    );
    return resp.data;
  }

  Future<List<dynamic>> getTeachersList() async {
    final resp = await _dio.get('teacher/exams/teachers');
    return resp.data;
  }

  Future<Map<String, dynamic>> assignDuty({
    required int planId,
    required String date,
    required List<Map<String, dynamic>> allocations,
  }) async {
    final resp = await _dio.post(
      'teacher/exams/assign-duty',
      data: {
        'plan_id': planId,
        'date': date,
        'allocations': allocations,
      },
    );
    return resp.data;
  }
}
