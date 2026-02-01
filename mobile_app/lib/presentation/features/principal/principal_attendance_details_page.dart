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
  late DateTime _selectedDate;

  static const List<String> _months = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _selectedDate = widget.date != null
        ? (DateTime.tryParse(widget.date!) ?? DateTime.now())
        : DateTime.now();
    _future = _fetchDetails();
  }

  Future<Map<String, dynamic>> _fetchDetails() async {
    final q = <String, dynamic>{};
    q['date'] =
        "${_selectedDate.year.toString().padLeft(4, '0')}-${_selectedDate.month.toString().padLeft(2, '0')}-${_selectedDate.day.toString().padLeft(2, '0')}";
    final resp = await _dio.get(
      'principal/reports/attendance-details',
      queryParameters: q,
    );
    return resp.data as Map<String, dynamic>;
  }

  List<dynamic> _extractClassWise(Map<String, dynamic> json) {
    if (json['data'] is Map) {
      final d = Map<String, dynamic>.from(json['data'] as Map);
      if (d['class_wise'] is List) return List.from(d['class_wise'] as List);
      if (d['classWise'] is List) return List.from(d['classWise'] as List);
    }
    if (json['class_wise'] is List)
      return List.from(json['class_wise'] as List);
    if (json['classWise'] is List) return List.from(json['classWise'] as List);
    if (json['data'] is List) return List.from(json['data'] as List);
    return [];
  }

  int _toInt(dynamic v) {
    if (v == null) return 0;
    if (v is num) return v.toInt();
    return int.tryParse(v.toString()) ?? 0;
  }

  @override
  Widget build(BuildContext context) {
    final dayStr = _selectedDate.day.toString().padLeft(2, '0');
    final monthStr = _months[_selectedDate.month - 1];
    final yearStr = _selectedDate.year.toString();

    return Scaffold(
      appBar: AppBar(title: const Text('Attendance Details')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12.0),
            child: Row(
              children: [
                Expanded(child: Text('তারিখ: $dayStr $monthStr $yearStr')),
                IconButton(
                  tooltip: 'Pick date',
                  icon: const Icon(Icons.calendar_today_outlined),
                  onPressed: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _selectedDate,
                      firstDate: DateTime(2000),
                      lastDate: DateTime.now(),
                    );
                    if (picked != null) {
                      setState(() {
                        _selectedDate = picked;
                        _future = _fetchDetails();
                      });
                    }
                  },
                ),
                IconButton(
                  tooltip: 'Refresh',
                  icon: const Icon(Icons.refresh),
                  onPressed: () => setState(() => _future = _fetchDetails()),
                ),
              ],
            ),
          ),
          Expanded(
            child: FutureBuilder<Map<String, dynamic>>(
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
                if (classWise.isEmpty)
                  return const Center(child: Text('কোনও তথ্য নেই'));

                int grandTotal = 0;
                int grandMale = 0;
                int grandFemale = 0;
                int grandPresent = 0;
                int grandPresentMale = 0;
                int grandPresentFemale = 0;
                int grandAbsent = 0;
                int grandAbsentMale = 0;
                int grandAbsentFemale = 0;

                for (final cls in classWise) {
                  final sections = (cls['sections'] is List)
                      ? List.from(cls['sections'] as List)
                      : <dynamic>[];
                  if (sections.isEmpty) {
                    final ct = _toInt(cls['total'] ?? cls['students_total']);
                    final cm = _toInt(cls['total_male']);
                    final cf = _toInt(cls['total_female']);
                    final cp = _toInt(
                      cls['present_total'] ?? cls['present'] ?? 0,
                    );
                    final cpm = _toInt(cls['present_male']);
                    final cpf = _toInt(cls['present_female']);
                    final ca = _toInt(cls['absent_total'] ?? (ct - cp));
                    final cam = _toInt(cls['absent_male']);
                    final caf = _toInt(cls['absent_female']);
                    grandTotal += ct;
                    grandMale += cm;
                    grandFemale += cf;
                    grandPresent += cp;
                    grandPresentMale += cpm;
                    grandPresentFemale += cpf;
                    grandAbsent += ca;
                    grandAbsentMale += cam;
                    grandAbsentFemale += caf;
                  } else {
                    for (final s in sections) {
                      final st = _toInt(s['total'] ?? s['students_total'] ?? 0);
                      final stm = _toInt(s['total_male']);
                      final stf = _toInt(s['total_female']);
                      final spm = _toInt(s['present_male']);
                      final spf = _toInt(s['present_female']);
                      final sp = _toInt(s['present_total'] ?? (spm + spf));
                      final sa = _toInt(s['absent_total'] ?? (st - sp));
                      final sam = _toInt(s['absent_male']);
                      final saf = _toInt(s['absent_female']);
                      final attTakenRaw =
                          s['att_taken'] ??
                          s['att_taken_today'] ??
                          s['att_taken_flag'];
                      final bool attTaken = attTakenRaw == null
                          ? (s.containsKey('present_total') ||
                                s.containsKey('present_male') ||
                                s.containsKey('present_female'))
                          : (attTakenRaw is bool
                                ? attTakenRaw
                                : (attTakenRaw.toString() == '1' ||
                                      attTakenRaw.toString().toLowerCase() ==
                                          'true'));
                      if (!attTaken) {
                        grandTotal += st;
                        grandMale += stm;
                        grandFemale += stf;
                        grandPresent += 0;
                        grandPresentMale += 0;
                        grandPresentFemale += 0;
                        grandAbsent += st;
                        grandAbsentMale += stm;
                        grandAbsentFemale += stf;
                      } else {
                        grandTotal += st;
                        grandMale += stm;
                        grandFemale += stf;
                        grandPresent += sp;
                        grandPresentMale += spm;
                        grandPresentFemale += spf;
                        grandAbsent += sa;
                        grandAbsentMale += sam;
                        grandAbsentFemale += saf;
                      }
                    }
                  }
                }

                final presentPct = grandTotal > 0
                    ? (grandPresent / grandTotal * 100.0)
                    : 0.0;
                final absentPct = grandTotal > 0
                    ? (grandAbsent / grandTotal * 100.0)
                    : 0.0;

                return ListView.builder(
                  padding: const EdgeInsets.all(12),
                  itemCount: classWise.length + 1,
                  itemBuilder: (context, idx) {
                    if (idx == 0) {
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: Padding(
                          padding: const EdgeInsets.all(12.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'সারসংক্ষেপ',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'মোট: $grandTotal',
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          'ছেলে: ${grandMale > 0 ? grandMale : '—'}  •  মেয়ে: ${grandFemale > 0 ? grandFemale : '—'}',
                                          style: Theme.of(
                                            context,
                                          ).textTheme.bodySmall,
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'উপস্থিত: $grandPresent',
                                          style: const TextStyle(
                                            color: Colors.green,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          'M: $grandPresentMale • F: $grandPresentFemale',
                                          style: Theme.of(
                                            context,
                                          ).textTheme.bodySmall,
                                        ),
                                        const SizedBox(height: 6),
                                        LinearProgressIndicator(
                                          value: (presentPct / 100).clamp(
                                            0.0,
                                            1.0,
                                          ),
                                        ),
                                        const SizedBox(height: 6),
                                        Text(
                                          '${presentPct.toStringAsFixed(1)}%',
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'অনুপস্থিত: $grandAbsent',
                                          style: const TextStyle(
                                            color: Colors.red,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          'M: $grandAbsentMale • F: $grandAbsentFemale',
                                          style: Theme.of(
                                            context,
                                          ).textTheme.bodySmall,
                                        ),
                                        const SizedBox(height: 6),
                                        LinearProgressIndicator(
                                          value: (absentPct / 100).clamp(
                                            0.0,
                                            1.0,
                                          ),
                                          color: Colors.redAccent,
                                          backgroundColor: Colors.red[50],
                                        ),
                                        const SizedBox(height: 6),
                                        Text(
                                          '${absentPct.toStringAsFixed(1)}%',
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    final cls = classWise[idx - 1];
                    final className =
                        cls['class_name'] ??
                        cls['name'] ??
                        cls['className'] ??
                        '';
                    final sections = (cls['sections'] is List)
                        ? List.from(cls['sections'] as List)
                        : <dynamic>[];

                    int classTotal = 0;
                    int classMale = 0;
                    int classFemale = 0;
                    int classPresent = 0;
                    int classPresentMale = 0;
                    int classPresentFemale = 0;
                    int classAbsent = 0;
                    int classAbsentMale = 0;
                    int classAbsentFemale = 0;

                    final List<DataRow> rows = [];

                    for (final s in sections) {
                      final sName = s['section_name'] ?? s['name'] ?? '';
                      final st = _toInt(s['total'] ?? s['students_total'] ?? 0);
                      final stm = _toInt(s['total_male']);
                      final stf = _toInt(s['total_female']);
                      final spm = _toInt(s['present_male']);
                      final spf = _toInt(s['present_female']);
                      final sp = _toInt(s['present_total'] ?? (spm + spf));
                      final sa = _toInt(s['absent_total'] ?? (st - sp));
                      final sam = _toInt(s['absent_male']);
                      final saf = _toInt(s['absent_female']);
                      final attTakenRaw =
                          s['att_taken'] ??
                          s['att_taken_today'] ??
                          s['att_taken_flag'];
                      final bool attTaken = attTakenRaw == null
                          ? (s.containsKey('present_total') ||
                                s.containsKey('present_male') ||
                                s.containsKey('present_female'))
                          : (attTakenRaw is bool
                                ? attTakenRaw
                                : (attTakenRaw.toString() == '1' ||
                                      attTakenRaw.toString().toLowerCase() ==
                                          'true'));

                      int dispPresent = sp;
                      int dispPresentMale = spm;
                      int dispPresentFemale = spf;
                      int dispAbsent = sa;
                      int dispAbsentMale = sam;
                      int dispAbsentFemale = saf;

                      if (!attTaken) {
                        dispPresent = 0;
                        dispPresentMale = 0;
                        dispPresentFemale = 0;
                        dispAbsent = st;
                        dispAbsentMale = stm;
                        dispAbsentFemale = stf;
                      }

                      classTotal += st;
                      classMale += stm;
                      classFemale += stf;
                      classPresent += dispPresent;
                      classPresentMale += dispPresentMale;
                      classPresentFemale += dispPresentFemale;
                      classAbsent += dispAbsent;
                      classAbsentMale += dispAbsentMale;
                      classAbsentFemale += dispAbsentFemale;

                      final pct = st > 0
                          ? '${(dispPresent / st * 100).toStringAsFixed(1)}%'
                          : '—';
                      final displayName = attTaken
                          ? sName.toString()
                          : '${sName.toString()} (হাজিরা নেই)';

                      rows.add(
                        DataRow(
                          cells: [
                            DataCell(Text(displayName)),
                            DataCell(Text('$st')),
                            DataCell(Text('$stm')),
                            DataCell(Text('$stf')),
                            DataCell(Text('$dispPresentMale')),
                            DataCell(Text('$dispAbsentMale')),
                            DataCell(Text('$dispPresentFemale')),
                            DataCell(Text('$dispAbsentFemale')),
                            DataCell(Text('$dispPresent')),
                            DataCell(Text('$dispAbsent')),
                            DataCell(Text(pct)),
                          ],
                        ),
                      );
                    }

                    final classPct = classTotal > 0
                        ? '${(classPresent / classTotal * 100).toStringAsFixed(1)}%'
                        : '—';
                    rows.add(
                      DataRow(
                        cells: [
                          const DataCell(
                            Text(
                              'মোট',
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ),
                          DataCell(
                            Text(
                              '$classTotal',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          DataCell(
                            Text(
                              '$classMale',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          DataCell(
                            Text(
                              '$classFemale',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          DataCell(
                            Text(
                              '$classPresentMale',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          DataCell(
                            Text(
                              '$classAbsentMale',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          DataCell(
                            Text(
                              '$classPresentFemale',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          DataCell(
                            Text(
                              '$classAbsentFemale',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          DataCell(
                            Text(
                              '$classPresent',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          DataCell(
                            Text(
                              '$classAbsent',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          DataCell(
                            Text(
                              classPct,
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    );

                    return Card(
                      child: ExpansionTile(
                        title: Text(className.toString()),
                        subtitle: Text(
                          'মোট: $classTotal, উপস্থিত: $classPresent, অনুপস্থিত: $classAbsent',
                        ),
                        children: [
                          Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8.0,
                              vertical: 6.0,
                            ),
                            child: SingleChildScrollView(
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
                                rows: rows,
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
