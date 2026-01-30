import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import '../../../widgets/app_snack.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../widgets/animated_tile.dart';
import '../../../widgets/rive_icon_registry.dart';
import '../../../theme/theme_mode_provider.dart';

class PrincipalDashboardPage extends ConsumerStatefulWidget {
  const PrincipalDashboardPage({super.key});

  @override
  ConsumerState<PrincipalDashboardPage> createState() =>
      _PrincipalDashboardPageState();
}

class _PrincipalDashboardPageState
    extends ConsumerState<PrincipalDashboardPage> {
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
      appBar: AppBar(
        title: const Text('Principal Dashboard'),
        actions: [
          IconButton(
            tooltip: 'Toggle theme',
            icon: const Icon(Icons.dark_mode_outlined),
            onPressed: () {
              ref.read(themeModeProvider.notifier).toggle();
            },
          ),
          IconButton(
            icon: const Icon(Icons.info_outline),
            tooltip: 'Summary refreshed',
            onPressed: () async {
              await showAppSnack(context, message: 'Refreshed', success: true);
              setState(() {
                _attendanceSummaryFuture = _fetchJson(
                  'principal/reports/attendance-summary',
                );
                _examSummaryFuture = _fetchJson(
                  'principal/reports/exam-results-summary',
                );
              });
            },
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 1.2,
            children: [
              AnimatedTile(
                title: 'Attendance',
                icon: Icons.bar_chart_outlined,
                riveIcon: RiveIconRegistry.artboardFor('attendance'),
                onTap: () async {
                  await showAppSnack(
                    context,
                    message: 'Scroll for Attendance Summary',
                  );
                },
              ),
              AnimatedTile(
                title: 'Exam Results',
                icon: Icons.assessment_outlined,
                riveIcon: RiveIconRegistry.artboardFor('exam_results'),
                onTap: () async {
                  await showAppSnack(
                    context,
                    message: 'Scroll for Exam Summary',
                  );
                },
              ),
            ],
          ),
          const SizedBox(height: 24),
          const SizedBox(height: 24),
          // Attendance report card (compact) shown on homepage
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
              return _AttendanceReportCard(data: data);
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

class _AttendanceReportCard extends StatelessWidget {
  final Map<String, dynamic> data;
  const _AttendanceReportCard({required this.data});

  @override
  Widget build(BuildContext context) {
    final d = data['data'] is Map
        ? Map<String, dynamic>.from(data['data'])
        : data;
    final meta = data['meta'] is Map
        ? Map<String, dynamic>.from(data['meta'])
        : {};
    double presentPct = 0;
    double absentPct = 0;
    try {
      presentPct = (d['present_percentage'] is num)
          ? (d['present_percentage'] as num).toDouble()
          : double.tryParse('${d['present_percentage']}') ?? 0.0;
      absentPct = (d['absent_percentage'] is num)
          ? (d['absent_percentage'] as num).toDouble()
          : double.tryParse('${d['absent_percentage']}') ?? 0.0;
    } catch (_) {}

    final message = meta['message'] ?? '';

    return Card(
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Attendance Report Card',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Present',
                        style: TextStyle(color: Colors.green),
                      ),
                      const SizedBox(height: 4),
                      LinearProgressIndicator(
                        value: (presentPct / 100).clamp(0.0, 1.0),
                      ),
                      const SizedBox(height: 4),
                      Text('${presentPct.toStringAsFixed(1)}%'),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Absent', style: TextStyle(color: Colors.red)),
                      const SizedBox(height: 4),
                      LinearProgressIndicator(
                        value: (absentPct / 100).clamp(0.0, 1.0),
                        color: Colors.redAccent,
                        backgroundColor: Colors.red[50],
                      ),
                      const SizedBox(height: 4),
                      Text('${absentPct.toStringAsFixed(1)}%'),
                    ],
                  ),
                ),
              ],
            ),
            if ((message as String).isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                message.toString(),
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
