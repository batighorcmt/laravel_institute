import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';

class TeacherDashboardPage extends StatefulWidget {
  const TeacherDashboardPage({super.key});

  @override
  State<TeacherDashboardPage> createState() => _TeacherDashboardPageState();
}

class _TeacherDashboardPageState extends State<TeacherDashboardPage> {
  late final Dio _dio;
  late Future<List<dynamic>> _attendanceFuture;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _attendanceFuture = _fetchList('teacher/attendance');
  }

  Future<List<dynamic>> _fetchList(String path) async {
    final resp = await _dio.get(path);
    final data = resp.data;
    if (data is List) return data;
    if (data is Map<String, dynamic> && data['data'] is List) {
      return data['data'] as List<dynamic>;
    }
    return [];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Teacher Dashboard')),
      body: FutureBuilder<List<dynamic>>(
        future: _attendanceFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          }
          final items = snapshot.data ?? [];
          if (items.isEmpty) {
            return const Center(child: Text('No attendance records'));
          }
          return ListView.separated(
            itemCount: items.length,
            separatorBuilder: (context, index) => const Divider(height: 1),
            itemBuilder: (context, index) {
              final item = items[index] as Map<String, dynamic>? ?? {};
              final date = item['date'] ?? item['checked_in_at'] ?? 'N/A';
              final status = item['status'] ?? 'Present';
              return ListTile(
                title: Text('Date: $date'),
                subtitle: Text('Status: $status'),
              );
            },
          );
        },
      ),
    );
  }
}
