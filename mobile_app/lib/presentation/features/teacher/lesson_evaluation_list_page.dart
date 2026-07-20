import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import 'lesson_evaluation_mark_page.dart';
import 'lesson_evaluation_report_page.dart';
import 'lesson_evaluation_theme.dart';

class LessonEvaluationListPage extends StatefulWidget {
  const LessonEvaluationListPage({super.key});
  @override
  State<LessonEvaluationListPage> createState() =>
      _LessonEvaluationListPageState();
}

class _LessonEvaluationListPageState extends State<LessonEvaluationListPage> {
  late final Dio _dio;
  final ScrollController _scrollController = ScrollController();
  final GlobalKey _pastSectionKey = GlobalKey();

  bool _loadingToday = true;
  String? _errorToday;
  List<dynamic> _todayItems = const [];
  String _todayDate = '';
  String? _todayHoliday;

  bool _loadingPast = true;
  String? _errorPast;
  List<dynamic> _pastItems = const [];
  String _pastDate = '';
  String? _pastHoliday;

  int get _todayTotal => _todayItems.length;
  int get _todayDone =>
      _todayItems.where((m) => (m['evaluated'] as bool?) ?? false).length;
  int get _todayNotDone => _todayTotal - _todayDone;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    final yesterday = DateTime.now().subtract(const Duration(days: 1));
    _pastDate = _isoDate(yesterday);
    _loadToday();
    _loadPast();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  String _isoDate(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _loadToday() async {
    setState(() {
      _loadingToday = true;
      _errorToday = null;
    });
    try {
      final r = await _dio.get('teacher/lesson-evaluations/today-routine');
      final data = r.data as Map<String, dynamic>? ?? {};
      _todayItems = (data['items'] as List? ?? []).cast<Map>();
      _todayDate = (data['date'] as String?) ?? '';
      _todayHoliday = (data['is_holiday'] as bool?) == true
          ? ((data['holiday_label'] as String?) ?? 'ছুটির দিন')
          : null;
    } catch (e) {
      _errorToday = 'লোড ব্যর্থ';
    } finally {
      if (mounted) setState(() => _loadingToday = false);
    }
  }

  Future<void> _loadPast() async {
    setState(() {
      _loadingPast = true;
      _errorPast = null;
    });
    try {
      final r = await _dio.get(
        'teacher/lesson-evaluations/routine-for-date',
        queryParameters: {'date': _pastDate},
      );
      final data = r.data as Map<String, dynamic>? ?? {};
      _pastItems = (data['items'] as List? ?? []).cast<Map>();
      _pastDate = (data['date'] as String?) ?? _pastDate;
      _pastHoliday = (data['is_holiday'] as bool?) == true
          ? ((data['holiday_label'] as String?) ?? 'ছুটির দিন')
          : null;
    } catch (e) {
      _errorPast = 'লোড ব্যর্থ';
    } finally {
      if (mounted) setState(() => _loadingPast = false);
    }
  }

  Future<void> _pickPastDate() async {
    if (_loadingPast) return;
    final yesterday = DateTime.now().subtract(const Duration(days: 1));
    final initial = DateTime.tryParse(_pastDate) ?? yesterday;
    final picked = await showDatePicker(
      context: context,
      initialDate: initial.isAfter(yesterday) ? yesterday : initial,
      firstDate: DateTime(yesterday.year - 1, 1, 1),
      lastDate: yesterday,
    );
    if (picked == null) return;
    final formatted = _isoDate(picked);
    if (formatted == _pastDate) return;
    setState(() => _pastDate = formatted);
    await _loadPast();
  }

  Future<void> _openReport() async {
    // The report page's monthly daily-list lets the teacher tap a date to
    // jump back here with that date preselected in "বিগত ক্লাস সমূহ" — it
    // returns that date (yyyy-MM-dd) via pop() instead of navigating deeper
    // itself, since the actual per-subject detail lives on this page.
    final result = await Navigator.of(context).push<String>(
      MaterialPageRoute(builder: (_) => const LessonEvaluationReportPage()),
    );
    if (result == null || !mounted) return;
    setState(() => _pastDate = result);
    await _loadPast();
    final ctx = _pastSectionKey.currentContext;
    if (ctx != null && ctx.mounted) {
      await Scrollable.ensureVisible(
        ctx,
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeInOut,
      );
    }
  }

  // Read-only vs. editable is decided server-side by comparing `date` to
  // today, so the caller only needs to pass which date to open.
  void _openEvaluation(Map<String, dynamic> m, {required String date}) {
    final rid = (m['routine_entry_id'] as num?)?.toInt() ?? 0;
    if (rid <= 0) return;
    Navigator.of(context)
        .push(
          MaterialPageRoute(
            builder: (_) => LessonEvaluationMarkPage(
              routineEntryId: rid,
              headerTitle: '${m['subject_name'] ?? 'Evaluation'}',
              classId: (m['class_id'] as num?)?.toInt() ?? 0,
              sectionId: (m['section_id'] as num?)?.toInt() ?? 0,
              subjectId: (m['subject_id'] as num?)?.toInt() ?? 0,
              initialDate: date,
            ),
          ),
        )
        .then((_) {
          _loadToday();
          _loadPast();
        });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LeColors.bg,
      appBar: AppBar(title: const Text('Lesson Evaluation')),
      body: RefreshIndicator(
        onRefresh: () async {
          await Future.wait([_loadToday(), _loadPast()]);
        },
        child: ListView(
          controller: _scrollController,
          padding: const EdgeInsets.all(16),
          children: [
            _buildTodayStatsCard(),
            const SizedBox(height: 20),
            _SectionHeader(
              icon: Icons.today_rounded,
              title: 'আজকের ক্লাস সমূহ',
              subtitle: _todayDate.isEmpty ? null : 'তারিখ: $_todayDate',
            ),
            const SizedBox(height: 10),
            ..._buildRoutineList(
              loading: _loadingToday,
              error: _errorToday,
              items: _todayItems,
              emptyText: 'আজ কোনো ক্লাস রুটিনে নেই',
              holidayLabel: _todayHoliday,
              readOnly: false,
              date: _todayDate,
            ),
            const SizedBox(height: 24),
            Container(key: _pastSectionKey),
            _SectionHeader(
              icon: Icons.history_rounded,
              title: 'বিগত ক্লাস সমূহ',
              subtitle: 'পূর্ববর্তী দিনের রেকর্ড শুধু দেখা যাবে',
              trailing: OutlinedButton.icon(
                onPressed: _pickPastDate,
                icon: const Icon(Icons.calendar_today, size: 16),
                label: Text(_pastDate.isEmpty ? 'তারিখ' : _pastDate),
                style: OutlinedButton.styleFrom(
                  foregroundColor: LeColors.brandDark,
                  side: const BorderSide(color: LeColors.brand),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 10),
            ..._buildRoutineList(
              loading: _loadingPast,
              error: _errorPast,
              items: _pastItems,
              emptyText: 'এই দিনে কোনো ক্লাস রুটিনে নেই',
              holidayLabel: _pastHoliday,
              readOnly: true,
              date: _pastDate,
            ),
            const SizedBox(height: 24),
            _buildReportMenu(),
          ],
        ),
      ),
    );
  }

  Widget _buildTodayStatsCard() {
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
            'আজকের ক্লাস পরিসংখ্যান',
            style: TextStyle(
              color: Colors.white,
              fontSize: 15,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            _todayDate.isEmpty ? '—' : _todayDate,
            style: const TextStyle(color: Colors.white70, fontSize: 12),
          ),
          const SizedBox(height: 16),
          _loadingToday
              ? const SizedBox(
                  height: 40,
                  child: Center(
                    child: SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    ),
                  ),
                )
              : Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _statPill('ক্লাস ছিল', _todayTotal, LeColors.total),
                    _statPill('সম্পন্ন', _todayDone, LeColors.completed),
                    _statPill('বাকি', _todayNotDone, LeColors.notDone),
                  ],
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

  Widget _buildReportMenu() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 6,
        ),
        leading: Container(
          width: 44,
          height: 44,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: LeColors.accentSoft,
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Icon(Icons.bar_chart_rounded, color: LeColors.accent),
        ),
        title: const Text(
          'লেসন ইভ্যালুয়েশন এন্ট্রি রিপোর্ট',
          style: TextStyle(fontWeight: FontWeight.w600),
        ),
        subtitle: const Text('দৈনিক ও মাসিক হিসাব দেখুন'),
        trailing: const Icon(Icons.chevron_right),
        onTap: _openReport,
      ),
    );
  }

  List<Widget> _buildRoutineList({
    required bool loading,
    required String? error,
    required List<dynamic> items,
    required String emptyText,
    required bool readOnly,
    required String date,
    String? holidayLabel,
  }) {
    if (loading) {
      return const [
        Padding(
          padding: EdgeInsets.symmetric(vertical: 24),
          child: Center(child: CircularProgressIndicator()),
        ),
      ];
    }
    if (error != null) {
      return [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Center(child: Text(error)),
        ),
      ];
    }
    if (holidayLabel != null) {
      // Routine rows can exist for a holiday's weekday/date, but no class
      // actually happens then — the backend already excludes them, so make
      // that explicit instead of showing a generic "no classes" message.
      return [
        Container(
          padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
          decoration: BoxDecoration(
            color: LeColors.accentSoft,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: LeColors.accent.withValues(alpha: 0.3)),
          ),
          child: Row(
            children: [
              const Icon(Icons.beach_access_rounded, color: LeColors.accent),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  '$holidayLabel — এই দিনে ইভ্যালুয়েশন প্রযোজ্য নয়',
                  style: const TextStyle(
                    color: LeColors.accent,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        ),
      ];
    }
    if (items.isEmpty) {
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
            emptyText,
            style: TextStyle(color: Colors.grey.shade600),
          ),
        ),
      ];
    }
    return items.map((it) {
      final m = it as Map<String, dynamic>;
      final evaluated = (m['evaluated'] as bool?) ?? false;
      final color = evaluated ? LeColors.completed : LeColors.partial;
      final title = '${m['class_name'] ?? ''} ${m['section_name'] ?? ''}'
          .trim();
      final sub =
          '${m['subject_name'] ?? ''} • Period ${m['period_number'] ?? ''}';
      return Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Material(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          child: InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () => _openEvaluation(m, date: date),
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
                    width: 42,
                    height: 42,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(11),
                    ),
                    child: Icon(
                      evaluated
                          ? Icons.check_circle
                          : (readOnly
                                ? Icons.remove_circle_outline
                                : Icons.edit_note_rounded),
                      color: color,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title.isEmpty ? 'Class' : title,
                          style: const TextStyle(fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          sub,
                          style: TextStyle(
                            color: Colors.grey.shade600,
                            fontSize: 12.5,
                          ),
                        ),
                      ],
                    ),
                  ),
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

class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  const _SectionHeader({
    required this.icon,
    required this.title,
    this.subtitle,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 34,
          height: 34,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: LeColors.brandSoft,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, size: 18, color: LeColors.brandDark),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: Theme.of(
                  context,
                ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              ),
              if (subtitle != null)
                Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: Text(
                    subtitle!,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Colors.grey.shade600,
                    ),
                  ),
                ),
            ],
          ),
        ),
        if (trailing != null) trailing!,
      ],
    );
  }
}
