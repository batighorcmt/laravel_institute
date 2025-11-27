import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';

class PrincipalDashboardPage extends StatefulWidget {
  const PrincipalDashboardPage({super.key});

  @override
  State<PrincipalDashboardPage> createState() => _PrincipalDashboardPageState();
}

class _PrincipalDashboardPageState extends State<PrincipalDashboardPage> {
  late final Dio _dio;
  late Future<Map<String, dynamic>> _attendanceSummaryFuture;
  late Future<Map<String, dynamic>> _examSummaryFuture;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _attendanceSummaryFuture = _fetchJson(
      'principal/reports/attendance-summary',
    );
    _examSummaryFuture = _fetchJson('principal/reports/exam-results-summary');
  }

  Future<Map<String, dynamic>> _fetchJson(String path) async {
    final resp = await _dio.get(path);
    return resp.data as Map<String, dynamic>;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Principal Dashboard')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text(
            'Attendance Summary',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          FutureBuilder<Map<String, dynamic>>(
            future: _attendanceSummaryFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Padding(
                  padding: EdgeInsets.all(12),
                  child: CircularProgressIndicator(),
                );
              }
              if (snapshot.hasError) {
                return Text('Error: ${snapshot.error}');
              }
              final data = snapshot.data ?? {};
              return _buildKeyValueList(data);
            },
          ),
          const SizedBox(height: 24),
          const Text(
            'Exam Results Summary',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          FutureBuilder<Map<String, dynamic>>(
            future: _examSummaryFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Padding(
                  padding: EdgeInsets.all(12),
                  child: CircularProgressIndicator(),
                );
              }
              if (snapshot.hasError) {
                return Text('Error: ${snapshot.error}');
              }
              final data = snapshot.data ?? {};
              return _buildKeyValueList(data);
            },
          ),
        ],
      ),
    );
  }

  Widget _buildKeyValueList(Map<String, dynamic> data) {
    if (data.isEmpty) {
      return const Text('No data');
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: data.entries
          .map(
            (e) => Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Text('${e.key}: ${e.value}'),
            ),
          )
          .toList(),
    );
  }
}
