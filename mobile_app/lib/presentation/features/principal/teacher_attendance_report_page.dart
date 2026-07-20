import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import 'widgets/monthly_report_widgets.dart';

class TeacherAttendanceReportPage extends StatefulWidget {
  const TeacherAttendanceReportPage({super.key});

  @override
  State<TeacherAttendanceReportPage> createState() =>
      _TeacherAttendanceReportPageState();
}

class _TeacherAttendanceReportPageState
    extends State<TeacherAttendanceReportPage>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('শিক্ষক হাজিরা রিপোর্ট'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'দৈনিক', icon: Icon(Icons.today_outlined)),
            Tab(text: 'মাসিক', icon: Icon(Icons.calendar_view_month_outlined)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: const [
          _DailyTeacherAttendanceTab(),
          _MonthlyTeacherAttendanceTab(),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------
// Daily tab
// ---------------------------------------------------------------------

class _DailyTeacherAttendanceTab extends StatefulWidget {
  const _DailyTeacherAttendanceTab();

  @override
  State<_DailyTeacherAttendanceTab> createState() =>
      _DailyTeacherAttendanceTabState();
}

class _DailyTeacherAttendanceTabState
    extends State<_DailyTeacherAttendanceTab> {
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
    _selectedDate = DateTime.now();
    _future = _fetch();
  }

  Future<Map<String, dynamic>> _fetch() async {
    final ymd =
        '${_selectedDate.year.toString().padLeft(4, '0')}-${_selectedDate.month.toString().padLeft(2, '0')}-${_selectedDate.day.toString().padLeft(2, '0')}';
    final resp = await _dio.get(
      'principal/reports/teacher-attendance-details',
      queryParameters: {'date': ymd},
    );
    return Map<String, dynamic>.from(resp.data['data'] as Map);
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'present':
        return Colors.green;
      case 'late':
        return Colors.orange;
      case 'absent':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'present':
        return 'উপস্থিত';
      case 'late':
        return 'বিলম্ব';
      case 'absent':
        return 'অনুপস্থিত';
      default:
        return 'হাজিরা নেই';
    }
  }

  @override
  Widget build(BuildContext context) {
    final dayStr = _selectedDate.day.toString().padLeft(2, '0');
    final monthStr = _months[_selectedDate.month - 1];
    final yearStr = _selectedDate.year.toString();

    return Column(
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
                      _future = _fetch();
                    });
                  }
                },
              ),
              IconButton(
                tooltip: 'Refresh',
                icon: const Icon(Icons.refresh),
                onPressed: () => setState(() => _future = _fetch()),
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
              final data = snapshot.data ?? {};
              final summary = Map<String, dynamic>.from(
                (data['summary'] as Map?) ?? {},
              );
              final teachers = List<dynamic>.from(
                (data['teachers'] as List?) ?? [],
              );

              final total = toInt(summary['total']);
              final present = toInt(summary['present']);
              final late_ = toInt(summary['late']);
              final absent = toInt(summary['absent']);
              final notMarked = toInt(summary['not_marked']);
              final pct = toPct(summary['percentage']);

              return ListView(
                padding: const EdgeInsets.only(bottom: 16),
                children: [
                  Card(
                    margin: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'আজকের সারসংক্ষেপ',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 20,
                            runSpacing: 12,
                            children: [
                              MonthlyStatTile(
                                label: 'মোট শিক্ষক',
                                value: '$total',
                              ),
                              MonthlyStatTile(
                                label: 'উপস্থিত',
                                value: '$present',
                                color: Colors.green,
                              ),
                              MonthlyStatTile(
                                label: 'বিলম্ব',
                                value: '$late_',
                                color: Colors.orange,
                              ),
                              MonthlyStatTile(
                                label: 'অনুপস্থিত',
                                value: '$absent',
                                color: Colors.red,
                              ),
                              if (notMarked > 0)
                                MonthlyStatTile(
                                  label: 'হাজিরা দেওয়া হয়নি',
                                  value: '$notMarked',
                                  color: Colors.grey,
                                ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(6),
                                  child: LinearProgressIndicator(
                                    value: pct == null
                                        ? 0
                                        : (pct / 100).clamp(0.0, 1.0),
                                    minHeight: 8,
                                    backgroundColor: Colors.grey[200],
                                    color: Colors.green,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Text(
                                pct == null
                                    ? '—'
                                    : '${pct.toStringAsFixed(1)}%',
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Colors.green,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                  if (teachers.isEmpty)
                    const MonthlyEmptyState(
                      message: 'কোনো শিক্ষক পাওয়া যায়নি',
                    )
                  else
                    ...teachers.map((raw) {
                      final t = Map<String, dynamic>.from(raw as Map);
                      final status = (t['status'] ?? 'not_marked').toString();
                      final photo = t['photo_url']?.toString();
                      final name = (t['name'] ?? '').toString();
                      final initials = (t['initials'] ?? '').toString();
                      final designation = (t['designation'] ?? '').toString();
                      final checkIn = t['check_in_time']?.toString();
                      final checkOut = t['check_out_time']?.toString();

                      return Card(
                        margin: const EdgeInsets.fromLTRB(12, 0, 12, 8),
                        child: ListTile(
                          leading: CircleAvatar(
                            radius: 22,
                            backgroundColor: const Color(0xFFE6F5EE),
                            backgroundImage: (photo != null && photo.isNotEmpty)
                                ? NetworkImage(photo)
                                : null,
                            child: (photo == null || photo.isEmpty)
                                ? Text(
                                    initials.isNotEmpty
                                        ? initials
                                        : (name.isNotEmpty
                                              ? name[0].toUpperCase()
                                              : '?'),
                                    style: const TextStyle(fontSize: 12),
                                  )
                                : null,
                          ),
                          title: Text(
                            name,
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          subtitle: Text(
                            [
                              if (designation.isNotEmpty) designation,
                              if (checkIn != null) 'ইন: $checkIn',
                              if (checkOut != null) 'আউট: $checkOut',
                            ].join(' • '),
                            style: const TextStyle(fontSize: 12),
                          ),
                          trailing: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 5,
                            ),
                            decoration: BoxDecoration(
                              color: _statusColor(
                                status,
                              ).withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              _statusLabel(status),
                              style: TextStyle(
                                color: _statusColor(status),
                                fontWeight: FontWeight.bold,
                                fontSize: 12,
                              ),
                            ),
                          ),
                        ),
                      );
                    }),
                ],
              );
            },
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------
// Monthly tab
// ---------------------------------------------------------------------

class _MonthlyTeacherAttendanceTab extends StatefulWidget {
  const _MonthlyTeacherAttendanceTab();

  @override
  State<_MonthlyTeacherAttendanceTab> createState() =>
      _MonthlyTeacherAttendanceTabState();
}

class _MonthlyTeacherAttendanceTabState
    extends State<_MonthlyTeacherAttendanceTab> {
  late final Dio _dio;
  late Future<Map<String, dynamic>> _future;
  int _year = DateTime.now().year;
  int _month = DateTime.now().month;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _future = _fetch();
  }

  Future<Map<String, dynamic>> _fetch() async {
    final resp = await _dio.get(
      'principal/reports/teacher-attendance-monthly',
      queryParameters: {'year': _year, 'month': _month},
    );
    return Map<String, dynamic>.from(resp.data['data'] as Map);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        MonthPickerRow(
          year: _year,
          month: _month,
          onRefresh: () => setState(() => _future = _fetch()),
          onPick: () async {
            final picked = await pickYearMonth(
              context,
              initialYear: _year,
              initialMonth: _month,
            );
            if (picked != null) {
              setState(() {
                _year = picked.year;
                _month = picked.month;
                _future = _fetch();
              });
            }
          },
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
              final data = snapshot.data ?? {};
              final workingDays = toInt(data['working_days']);
              final totalDays = toInt(data['total_days_in_month']);
              final daysTaken = toInt(data['days_attendance_taken']);
              final totalTeachers = toInt(data['total_teachers']);
              final overall = Map<String, dynamic>.from(
                (data['overall'] as Map?) ?? {},
              );
              final overallPct = toPct(overall['percentage']);
              final teachers = List<dynamic>.from(
                (data['teachers'] as List?) ?? [],
              );

              return ListView(
                children: [
                  MonthlySummaryCard(
                    workingDays: workingDays,
                    totalDaysInMonth: totalDays,
                    daysAttendanceTaken: daysTaken,
                    overallPercentage: overallPct,
                    extraTiles: [
                      MonthlyStatTile(
                        label: 'মোট শিক্ষক',
                        value: '$totalTeachers',
                      ),
                    ],
                  ),
                  if (teachers.isEmpty)
                    const MonthlyEmptyState()
                  else ...[
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
                      child: Text(
                        'শিক্ষক-ভিত্তিক হাজিরার হার (অবস্থান অনুসারে)',
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: Colors.grey[800],
                        ),
                      ),
                    ),
                    ...teachers.map((raw) {
                      final t = Map<String, dynamic>.from(raw as Map);
                      final rank = toInt(t['rank']);
                      final name = (t['name'] ?? '').toString();
                      final initials = (t['initials'] ?? '').toString();
                      final designation = (t['designation'] ?? '').toString();
                      final photo = t['photo_url']?.toString();
                      final present = toInt(t['present_days']);
                      final late_ = toInt(t['late_days']);
                      final absent = toInt(t['absent_days']);
                      final pct = toPct(t['percentage']);
                      final pctColor = pct == null
                          ? Colors.grey
                          : (pct >= 80
                                ? Colors.green
                                : (pct >= 60 ? Colors.orange : Colors.red));

                      return Card(
                        margin: const EdgeInsets.fromLTRB(12, 0, 12, 8),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 6,
                          ),
                          child: Row(
                            children: [
                              RankBadge(rank: rank),
                              const SizedBox(width: 10),
                              CircleAvatar(
                                radius: 20,
                                backgroundColor: const Color(0xFFE6F5EE),
                                backgroundImage:
                                    (photo != null && photo.isNotEmpty)
                                    ? NetworkImage(photo)
                                    : null,
                                child: (photo == null || photo.isEmpty)
                                    ? Text(
                                        initials.isNotEmpty
                                            ? initials
                                            : (name.isNotEmpty
                                                  ? name[0].toUpperCase()
                                                  : '?'),
                                        style: const TextStyle(fontSize: 11),
                                      )
                                    : null,
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      name,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w600,
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    Text(
                                      [
                                        if (designation.isNotEmpty) designation,
                                        'উ:$present বি:$late_ অ:$absent',
                                      ].join(' • '),
                                      style: TextStyle(
                                        fontSize: 11.5,
                                        color: Colors.grey[600],
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 8),
                              Text(
                                pct == null
                                    ? '—'
                                    : '${pct.toStringAsFixed(0)}%',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: pctColor,
                                  fontSize: 15,
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                  ],
                  const SizedBox(height: 16),
                ],
              );
            },
          ),
        ),
      ],
    );
  }
}
