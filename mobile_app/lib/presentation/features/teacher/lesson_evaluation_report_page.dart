import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import 'lesson_evaluation_theme.dart';

class LessonEvaluationReportPage extends StatefulWidget {
  const LessonEvaluationReportPage({super.key});

  @override
  State<LessonEvaluationReportPage> createState() =>
      _LessonEvaluationReportPageState();
}

class _LessonEvaluationReportPageState
    extends State<LessonEvaluationReportPage> {
  static const List<String> _bnMonth = [
    'জানুয়ারি',
    'ফেব্রুয়ারি',
    'মার্চ',
    'এপ্রিল',
    'মে',
    'জুন',
    'জুলাই',
    'আগস্ট',
    'সেপ্টেম্বর',
    'অক্টোবর',
    'নভেম্বর',
    'ডিসেম্বর',
  ];

  late final Dio _dio;
  bool _loading = true;
  String? _error;
  int _year = DateTime.now().year;
  int _month = DateTime.now().month;

  Map<String, dynamic> _monthly = const {};
  List<dynamic> _days = const [];

  bool get _isCurrentMonth {
    final now = DateTime.now();
    return _year == now.year && _month == now.month;
  }

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await _dio.get(
        'teacher/lesson-evaluations/report',
        queryParameters: {'year': _year, 'month': _month},
      );
      final data = r.data as Map<String, dynamic>? ?? {};
      _monthly = (data['monthly'] as Map?)?.cast<String, dynamic>() ?? {};
      _days = (data['days'] as List? ?? []).cast<Map>();
    } catch (e) {
      _error = 'লোড ব্যর্থ';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _changeMonth(int delta) {
    setState(() {
      var newMonth = _month + delta;
      var newYear = _year;
      if (newMonth < 1) {
        newMonth = 12;
        newYear -= 1;
      } else if (newMonth > 12) {
        newMonth = 1;
        newYear += 1;
      }
      final now = DateTime.now();
      // Don't allow navigating to a future month.
      if (newYear > now.year || (newYear == now.year && newMonth > now.month)) {
        return;
      }
      _year = newYear;
      _month = newMonth;
    });
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LeColors.bg,
      appBar: AppBar(title: const Text('লেসন ইভ্যালুয়েশন এন্ট্রি রিপোর্ট')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text(_error!))
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _buildMonthPicker(),
                  const SizedBox(height: 16),
                  _buildMonthlyCard(),
                  const SizedBox(height: 20),
                  Row(
                    children: [
                      Text(
                        'দৈনিক বিবরণ',
                        style: Theme.of(context).textTheme.titleMedium
                            ?.copyWith(fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        '(তারিখে ক্লিক করে বিস্তারিত দেখুন)',
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  ..._buildDailyList(),
                ],
              ),
            ),
    );
  }

  Widget _buildMonthPicker() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            onPressed: () => _changeMonth(-1),
            icon: const Icon(Icons.chevron_left, color: LeColors.brandDark),
          ),
          Text(
            '${_bnMonth[_month - 1]} $_year',
            style: Theme.of(
              context,
            ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
          ),
          IconButton(
            onPressed: _isCurrentMonth ? null : () => _changeMonth(1),
            icon: const Icon(Icons.chevron_right, color: LeColors.brandDark),
          ),
        ],
      ),
    );
  }

  Widget _buildMonthlyCard() {
    final expected = (_monthly['expected_total'] as num?)?.toInt() ?? 0;
    final done = (_monthly['done_total'] as num?)?.toInt() ?? 0;
    final notDone = (_monthly['not_done_total'] as num?)?.toInt() ?? 0;
    final rate = (_monthly['completion_rate'] as num?)?.toDouble() ?? 0;
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LeColors.brandGradient,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: LeColors.brand.withValues(alpha: 0.25),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'মাসিক হিসাব',
            style: TextStyle(
              color: Colors.white,
              fontSize: 15,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 14),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _statPill('ক্লাস ছিল', expected, LeColors.total),
              _statPill('সম্পন্ন', done, LeColors.completed),
              _statPill('বাকি', notDone, LeColors.notDone),
            ],
          ),
          const SizedBox(height: 16),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: (rate / 100).clamp(0, 1),
              minHeight: 8,
              backgroundColor: Colors.white.withValues(alpha: 0.25),
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'সম্পন্নের হার: $rate%',
            style: const TextStyle(color: Colors.white70, fontSize: 12),
          ),
        ],
      ),
    );
  }

  // Value color differs per stat (blue=total, green=done, red=remaining) so
  // the numbers read at a glance instead of blending into one uniform tone.
  Widget _statPill(String label, int value, Color color) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 52,
          height: 52,
          alignment: Alignment.center,
          decoration: const BoxDecoration(
            color: Colors.white,
            shape: BoxShape.circle,
          ),
          child: Text(
            '$value',
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.bold,
              fontSize: 18,
            ),
          ),
        ),
        const SizedBox(height: 6),
        Text(label, style: const TextStyle(color: Colors.white, fontSize: 11)),
      ],
    );
  }

  List<Widget> _buildDailyList() {
    // Only show days that actually had a class scheduled — an empty day
    // (no routine period, e.g. a weekly off-day) adds no useful signal here.
    final rows = _days
        .cast<Map>()
        .where((d) => ((d['expected'] as num?)?.toInt() ?? 0) > 0)
        .toList()
        .reversed
        .toList();
    if (rows.isEmpty) {
      return [
        Container(
          padding: const EdgeInsets.symmetric(vertical: 20),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: Text(
            'এই মাসে কোনো ক্লাস রুটিনে নেই',
            style: TextStyle(color: Colors.grey.shade600),
          ),
        ),
      ];
    }
    return rows.map((d) {
      final expected = (d['expected'] as num?)?.toInt() ?? 0;
      final done = (d['done'] as num?)?.toInt() ?? 0;
      final notDone = (d['not_done'] as num?)?.toInt() ?? 0;
      final complete = notDone == 0;
      final color = complete ? LeColors.completed : LeColors.partial;
      final date = '${d['date']}';
      return Padding(
        padding: const EdgeInsets.only(bottom: 8),
        child: Material(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          child: InkWell(
            borderRadius: BorderRadius.circular(14),
            // Hand the tapped date back to the list page — the actual
            // per-subject evaluated/not-evaluated detail lives there.
            onTap: () => Navigator.of(context).pop(date),
            child: Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Colors.grey.shade200),
              ),
              padding: const EdgeInsets.symmetric(
                horizontal: 14,
                vertical: 10,
              ),
              child: Row(
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(
                      complete ? Icons.check_circle : Icons.error_outline,
                      color: color,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      date,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 5,
                    ),
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      '$done/$expected সম্পন্ন',
                      style: TextStyle(
                        color: color,
                        fontWeight: FontWeight.w600,
                        fontSize: 12,
                      ),
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Icon(Icons.chevron_right, color: Colors.grey),
                ],
              ),
            ),
          ),
        ),
      );
    }).toList();
  }
}
