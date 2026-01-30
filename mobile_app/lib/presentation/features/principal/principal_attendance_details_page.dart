import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';

class PrincipalAttendanceDetailsPage extends StatefulWidget {
  final String? date; // ISO yyyy-MM-dd
  const PrincipalAttendanceDetailsPage({Key? key, this.date}) : super(key: key);

  @override
  State<PrincipalAttendanceDetailsPage> createState() =>
      _PrincipalAttendanceDetailsPageState();
}

class _PrincipalAttendanceDetailsPageState
    extends State<PrincipalAttendanceDetailsPage> {
  late final Dio _dio;
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _future = _fetchDetails();
  }

  Future<Map<String, dynamic>> _fetchDetails() async {
    final q = <String, dynamic>{};
    if (widget.date != null) q['date'] = widget.date;
    final resp = await _dio.get(
      'principal/reports/attendance-details',
      queryParameters: q,
    );
    return resp.data as Map<String, dynamic>;
  }

  List<dynamic> _extractClassWise(Map<String, dynamic> json) {
    // Try several common shapes: data.class_wise, data.classWise, class_wise
    if (json['data'] is Map) {
      final d = Map<String, dynamic>.from(json['data']);
      if (d['class_wise'] is List) return List.from(d['class_wise']);
      if (d['classWise'] is List) return List.from(d['classWise']);
    }
    if (json['class_wise'] is List) return List.from(json['class_wise']);
    if (json['classWise'] is List) return List.from(json['classWise']);
    // fallback: some APIs return directly an array under data
    if (json['data'] is List) return List.from(json['data']);
    return [];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Attendance Details')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          }
          final json = snapshot.data ?? {};
          final classWise = _extractClassWise(json);
          if (classWise.isEmpty) {
            return const Center(child: Text('কোনও তথ্য নেই'));
          }

          // Build list of ExpansionTiles, one per class
          return ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: classWise.length + 1,
            itemBuilder: (context, idx) {
              if (idx == 0) {
                // optional summary header
                final grandTotal =
                    (json['data'] is Map && json['data']['grand_total'] != null)
                    ? json['data']['grand_total'].toString()
                    : (json['grand_total']?.toString() ?? '');
                return Card(
                  child: ListTile(
                    title: const Text('সারসংক্ষেপ'),
                    subtitle: Text(
                      'মোট শিক্ষার্থী: ${grandTotal.isNotEmpty ? grandTotal : '—'}',
                    ),
                  ),
                );
              }
              final cls = classWise[idx - 1];
              final className =
                  cls['class_name'] ?? cls['name'] ?? cls['className'] ?? '';
              final total = cls['total'] ?? cls['students_total'] ?? 0;
              final totalMale = cls['total_male'] ?? 0;
              final totalFemale = cls['total_female'] ?? 0;
              final presentTotal =
                  cls['present_total'] ?? cls['present_total'] ?? 0;
              final presentMale = cls['present_male'] ?? 0;
              final presentFemale = cls['present_female'] ?? 0;
              final absentTotal = cls['absent_total'] ?? 0;
              final sections = cls['sections'] is List
                  ? List.from(cls['sections'])
                  : <dynamic>[];

              return Card(
                child: ExpansionTile(
                  title: Text(className.toString()),
                  subtitle: Text(
                    'মোট: $total, উপস্থিত: $presentTotal, অনুপস্থিত: $absentTotal',
                  ),
                  children: [
                    Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8.0,
                        vertical: 6.0,
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          SingleChildScrollView(
                            scrollDirection: Axis.horizontal,
                            child: DataTable(
                              columns: const [
                                DataColumn(label: Text('শাখা')),
                                DataColumn(label: Text('মোট')),
                                DataColumn(label: Text('ছেলে')),
                                DataColumn(label: Text('মেয়ে')),
                                DataColumn(label: Text('উপস্থিত ছেলে')),
                                DataColumn(label: Text('অনুপস্থিত ছেলে')),
                                DataColumn(label: Text('উপস্থিত মেয়ে')),
                                DataColumn(label: Text('অনুপস্থিত মেয়ে')),
                                DataColumn(label: Text('মোট উপস্থিত')),
                                DataColumn(label: Text('মোট অনুপস্থিত')),
                                DataColumn(label: Text('%')),
                              ],
                              rows: sections.map((s) {
                                final sectionName =
                                    s['section_name'] ?? s['name'] ?? '';
                                final sTotal = s['total'] ?? 0;
                                final sTotalMale = s['total_male'] ?? 0;
                                final sTotalFemale = s['total_female'] ?? 0;
                                final sPresentMale = s['present_male'] ?? 0;
                                final sAbsentMale = s['absent_male'] ?? 0;
                                final sPresentFemale = s['present_female'] ?? 0;
                                final sAbsentFemale = s['absent_female'] ?? 0;
                                final sPresentTotal =
                                    s['present_total'] ??
                                    (sPresentMale + sPresentFemale);
                                final sAbsentTotal =
                                    s['absent_total'] ??
                                    (sTotal - sPresentTotal);
                                String pct = '—';
                                try {
                                  final tt = (sTotal is num)
                                      ? sTotal.toDouble()
                                      : double.tryParse('$sTotal') ?? 0.0;
                                  final pt = (sPresentTotal is num)
                                      ? (sPresentTotal as num).toDouble()
                                      : double.tryParse('$sPresentTotal') ??
                                            0.0;
                                  if (tt > 0)
                                    pct =
                                        (pt / tt * 100).toStringAsFixed(1) +
                                        '%';
                                } catch (_) {}
                                return DataRow(
                                  cells: [
                                    DataCell(Text(sectionName.toString())),
                                    DataCell(Text('$sTotal')),
                                    DataCell(Text('$sTotalMale')),
                                    DataCell(Text('$sTotalFemale')),
                                    DataCell(Text('$sPresentMale')),
                                    DataCell(Text('$sAbsentMale')),
                                    DataCell(Text('$sPresentFemale')),
                                    DataCell(Text('$sAbsentFemale')),
                                    DataCell(Text('$sPresentTotal')),
                                    DataCell(Text('$sAbsentTotal')),
                                    DataCell(Text(pct)),
                                  ],
                                );
                              }).toList(),
                            ),
                          ),
                          const SizedBox(height: 8),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}
