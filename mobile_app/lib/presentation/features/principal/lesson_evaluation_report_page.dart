import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dropdown_search/dropdown_search.dart';
import '../../../core/network/dio_client.dart';
import '../../state/auth_state.dart';
import 'lesson_evaluation_details_page.dart';

class LessonEvaluationReportPage extends ConsumerStatefulWidget {
  const LessonEvaluationReportPage({super.key});

  @override
  ConsumerState<LessonEvaluationReportPage> createState() =>
      _LessonEvaluationReportPageState();
}

class _LessonEvaluationReportPageState
    extends ConsumerState<LessonEvaluationReportPage> {
  static const Color _brand = Color(0xFF00BF6D);
  static const Color _brandDark = Color(0xFF049655);
  static const Color _bg = Color(0xFFF5F7F9);
  static const Color _ink = Color(0xFF1A1D1F);
  static const Color _muted = Color(0xFF6B7280);

  static const List<String> _bnWeekday = [
    'সোমবার',
    'মঙ্গলবার',
    'বুধবার',
    'বৃহস্পতিবার',
    'শুক্রবার',
    'শনিবার',
    'রবিবার',
  ];
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

  DateTime _selectedDate = DateTime.now();
  int? _selectedClassId;
  int? _selectedSectionId;
  int? _selectedSubjectId;
  Map<String, dynamic>? _selectedTeacherObj;

  List<Map<String, dynamic>> _teachers = [];
  List<Map<String, dynamic>> _classes = [];
  List<Map<String, dynamic>> _sections = [];
  List<Map<String, dynamic>> _subjects = [];

  bool _loading = false;
  String? _error;
  List<dynamic> _items = [];

  int? _getSchoolId() {
    final userState = ref.read(authProvider);
    if (userState is AsyncData && userState.value != null) {
      final user = userState.value!;
      for (final role in user.roles) {
        if (role.schoolId != null) return role.schoolId;
      }
    }
    return null;
  }

  List<dynamic> _extractList(dynamic respData) {
    if (respData == null) return [];
    if (respData is List) return respData;
    if (respData is Map) {
      if (respData.containsKey('data')) {
        final d = respData['data'];
        if (d is List) return d;
      }
      final firstList = respData.values.firstWhere(
        (v) => v is List,
        orElse: () => null,
      );
      if (firstList is List) return firstList;
    }
    return [];
  }

  String _isoDate(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  bool get _isToday {
    final now = DateTime.now();
    return _selectedDate.year == now.year &&
        _selectedDate.month == now.month &&
        _selectedDate.day == now.day;
  }

  String get _formattedSelectedDate {
    final wd = _bnWeekday[_selectedDate.weekday - 1];
    final mo = _bnMonth[_selectedDate.month - 1];
    return '$wd, ${_selectedDate.day} $mo ${_selectedDate.year}';
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await _fetchClasses();
      await _fetchTeachers();
      await _fetchPeriods();
    });
  }

  Future<void> _changeDate(DateTime newDate) async {
    if (newDate.isAfter(DateTime.now())) return;
    setState(() => _selectedDate = newDate);
    await _fetchPeriods();
  }

  Future<void> _pickDate() async {
    final d = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: Theme.of(
            context,
          ).colorScheme.copyWith(primary: _brand),
        ),
        child: child!,
      ),
    );
    if (d != null) await _changeDate(d);
  }

  Future<void> _fetchPeriods() async {
    final schoolId = _getSchoolId();
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final dio = DioClient().dio;
      final params = <String, dynamic>{'date': _isoDate(_selectedDate)};
      if (schoolId != null) params['school_id'] = schoolId;
      if (_selectedClassId != null) params['class_id'] = _selectedClassId;
      if (_selectedSectionId != null) {
        params['section_id'] = _selectedSectionId;
      }
      if (_selectedSubjectId != null) {
        params['subject_id'] = _selectedSubjectId;
      }
      if (_selectedTeacherObj != null) {
        params['teacher_id'] = _selectedTeacherObj!['id'];
      }

      final resp = await dio.get(
        'principal/reports/lesson-evaluations/periods',
        queryParameters: params,
      );
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data['items']);
        setState(() => _items = data);
      } else {
        setState(() => _error = 'সার্ভার ত্রুটি: ${resp.statusCode}');
      }
    } catch (e) {
      setState(() => _error = 'তথ্য লোড করা যায়নি। আবার চেষ্টা করুন।');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _fetchClasses() async {
    final schoolId = _getSchoolId();
    try {
      final params = <String, dynamic>{};
      if (schoolId != null) params['school_id'] = schoolId;
      final resp = await DioClient().dio.get(
        'principal/students/filters/classes',
        queryParameters: params,
      );
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        final rawData = data
            .map(
              (e) => {
                'id': e['id'],
                'name': e['name'],
                'numeric_value': e['numeric_value'],
              },
            )
            .toList();
        rawData.sort((a, b) {
          final an = int.tryParse(a['numeric_value']?.toString() ?? '');
          final bn = int.tryParse(b['numeric_value']?.toString() ?? '');
          if (an != null && bn != null && an != bn) return an.compareTo(bn);
          return a['name'].toString().compareTo(b['name'].toString());
        });
        if (mounted) {
          setState(() => _classes = rawData.cast<Map<String, dynamic>>());
        }
      }
    } catch (_) {}
  }

  Future<void> _fetchSections(int classId) async {
    final schoolId = _getSchoolId();
    try {
      final params = <String, dynamic>{'class_id': classId};
      if (schoolId != null) params['school_id'] = schoolId;
      final resp = await DioClient().dio.get(
        'principal/students/filters/sections',
        queryParameters: params,
      );
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        final rawData = data
            .map((e) => {'id': e['id'], 'name': e['name']})
            .toList();
        rawData.sort((a, b) {
          final s1 = a['name'].toString();
          final s2 = b['name'].toString();
          final n1 = int.tryParse(s1);
          final n2 = int.tryParse(s2);
          if (n1 != null && n2 != null) return n1.compareTo(n2);
          return s1.compareTo(s2);
        });
        if (mounted) {
          setState(() => _sections = rawData.cast<Map<String, dynamic>>());
        }
      }
    } catch (_) {}
  }

  Future<void> _fetchSubjects() async {
    if (_selectedClassId == null) {
      setState(() => _subjects = []);
      return;
    }
    final schoolId = _getSchoolId();
    try {
      final params = <String, dynamic>{'class_id': _selectedClassId};
      if (_selectedSectionId != null) {
        params['section_id'] = _selectedSectionId;
      }
      if (schoolId != null) params['school_id'] = schoolId;
      final resp = await DioClient().dio.get(
        'principal/students/filters/subjects',
        queryParameters: params,
      );
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        if (mounted) {
          setState(() {
            _subjects = data
                .map(
                  (e) => <String, dynamic>{'id': e['id'], 'name': e['name']},
                )
                .toList();
          });
        }
      }
    } catch (_) {}
  }

  Future<void> _fetchTeachers() async {
    final schoolId = _getSchoolId();
    try {
      final params = <String, dynamic>{};
      if (schoolId != null) params['school_id'] = schoolId;
      final resp = await DioClient().dio.get(
        'meta/teachers',
        queryParameters: params,
      );
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        if (mounted) {
          setState(() {
            _teachers = data
                .map(
                  (e) => <String, dynamic>{
                    'id': e['id'],
                    'name': e['name'],
                    'designation': e['designation'],
                  },
                )
                .toList();
          });
        }
      }
    } catch (_) {}
  }

  int get _activeFilterCount {
    var n = 0;
    if (_selectedClassId != null) n++;
    if (_selectedSectionId != null) n++;
    if (_selectedSubjectId != null) n++;
    if (_selectedTeacherObj != null) n++;
    return n;
  }

  void _clearFilters() {
    setState(() {
      _selectedClassId = null;
      _selectedSectionId = null;
      _selectedSubjectId = null;
      _selectedTeacherObj = null;
      _sections = [];
      _subjects = [];
    });
    _fetchPeriods();
  }

  void _openFilterSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _FilterSheet(
        brand: _brand,
        classes: _classes,
        sections: _sections,
        subjects: _subjects,
        teachers: _teachers,
        selectedClassId: _selectedClassId,
        selectedSectionId: _selectedSectionId,
        selectedSubjectId: _selectedSubjectId,
        selectedTeacher: _selectedTeacherObj,
        onClassChanged: (v) async {
          setState(() {
            _selectedClassId = v;
            _selectedSectionId = null;
            _selectedSubjectId = null;
            _sections = [];
            _subjects = [];
          });
          if (v != null) await _fetchSections(v);
          await _fetchSubjects();
        },
        onSectionChanged: (v) async {
          setState(() {
            _selectedSectionId = v;
            _selectedSubjectId = null;
          });
          await _fetchSubjects();
        },
        onSubjectChanged: (v) => setState(() => _selectedSubjectId = v),
        onTeacherChanged: (m) => setState(() => _selectedTeacherObj = m),
        onApply: () {
          Navigator.of(ctx).pop();
          _fetchPeriods();
        },
        onClear: () {
          Navigator.of(ctx).pop();
          _clearFilters();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final doneCount = _items.where((e) => e['evaluated'] == true).length;
    final total = _items.length;

    return Scaffold(
      backgroundColor: _bg,
      appBar: AppBar(
        title: const Text('লেসন ইভ্যালুয়েশন রিপোর্ট'),
        backgroundColor: _brand,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            tooltip: 'ফিল্টার',
            onPressed: _openFilterSheet,
            icon: Badge(
              isLabelVisible: _activeFilterCount > 0,
              label: Text('$_activeFilterCount'),
              backgroundColor: Colors.white,
              textColor: _brandDark,
              child: const Icon(Icons.tune_rounded),
            ),
          ),
        ],
      ),
      body: RefreshIndicator(
        color: _brand,
        onRefresh: _fetchPeriods,
        child: Column(
          children: [
            _buildDateBar(),
            if (total > 0) _buildSummaryStrip(doneCount, total),
            Expanded(child: _buildBody()),
          ],
        ),
      ),
    );
  }

  Widget _buildDateBar() {
    return Container(
      color: _brand,
      padding: const EdgeInsets.fromLTRB(12, 0, 12, 16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: const [
            BoxShadow(
              color: Color(0x22000000),
              blurRadius: 12,
              offset: Offset(0, 6),
            ),
          ],
        ),
        child: Row(
          children: [
            IconButton(
              icon: const Icon(Icons.chevron_left_rounded, color: _muted),
              onPressed: () => _changeDate(
                _selectedDate.subtract(const Duration(days: 1)),
              ),
            ),
            Expanded(
              child: InkWell(
                borderRadius: BorderRadius.circular(12),
                onTap: _pickDate,
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(
                            Icons.calendar_today_rounded,
                            size: 14,
                            color: _brand,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            _isToday ? 'আজ' : _formattedSelectedDate,
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 15,
                              color: _ink,
                            ),
                          ),
                        ],
                      ),
                      if (_isToday) ...[
                        const SizedBox(height: 2),
                        Text(
                          _formattedSelectedDate,
                          style: const TextStyle(
                            fontSize: 11,
                            color: _muted,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
            IconButton(
              icon: Icon(
                Icons.chevron_right_rounded,
                color: _isToday ? Colors.grey.shade300 : _muted,
              ),
              onPressed: _isToday
                  ? null
                  : () => _changeDate(
                      _selectedDate.add(const Duration(days: 1)),
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryStrip(int done, int total) {
    final pct = total == 0 ? 0.0 : done / total;
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0F000000),
            blurRadius: 10,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          SizedBox(
            width: 40,
            height: 40,
            child: Stack(
              alignment: Alignment.center,
              children: [
                CircularProgressIndicator(
                  value: pct,
                  strokeWidth: 4,
                  backgroundColor: const Color(0xFFE5E7EB),
                  valueColor: const AlwaysStoppedAnimation<Color>(_brand),
                ),
                Text(
                  '${(pct * 100).round()}%',
                  style: const TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: _ink,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text.rich(
              TextSpan(
                style: const TextStyle(fontSize: 13, color: _muted),
                children: [
                  TextSpan(
                    text: '$done',
                    style: const TextStyle(
                      color: _brandDark,
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                    ),
                  ),
                  TextSpan(text: ' / $total পিরিয়ডে ইভ্যালুয়েশন সম্পন্ন'),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator(color: _brand));
    }
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wifi_off_rounded, color: Colors.redAccent, size: 32),
              const SizedBox(height: 12),
              Text(_error!, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: _fetchPeriods,
                style: ElevatedButton.styleFrom(backgroundColor: _brand, foregroundColor: Colors.white),
                child: const Text('আবার চেষ্টা করুন'),
              ),
            ],
          ),
        ),
      );
    }
    if (_items.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 80),
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: _brand.withValues(alpha: 0.08),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.event_busy_outlined,
                    color: _brand,
                    size: 32,
                  ),
                ),
                const SizedBox(height: 12),
                const Text(
                  'এই তারিখে কোনো ক্লাস পিরিয়ড নেই',
                  style: TextStyle(color: _muted),
                ),
              ],
            ),
          ),
        ],
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
      itemCount: _items.length,
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemBuilder: (context, i) {
        final it = _items[i] as Map<String, dynamic>;
        return _PeriodCard(
          item: it,
          brand: _brand,
          brandDark: _brandDark,
          ink: _ink,
          muted: _muted,
          onTap: () {
            if (it['evaluated'] == true) {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => LessonEvaluationDetailsPage(report: it),
                ),
              );
            } else {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('এই পিরিয়ডে এখনো লেসন ইভ্যালুয়েশন জমা দেওয়া হয়নি'),
                ),
              );
            }
          },
        );
      },
    );
  }
}

class _PeriodCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final Color brand;
  final Color brandDark;
  final Color ink;
  final Color muted;
  final VoidCallback onTap;

  const _PeriodCard({
    required this.item,
    required this.brand,
    required this.brandDark,
    required this.ink,
    required this.muted,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final evaluated = item['evaluated'] == true;
    final period = item['period_number']?.toString() ?? '-';
    final start = item['start_time']?.toString() ?? '';
    final end = item['end_time']?.toString() ?? '';
    final timeLabel = (start.isNotEmpty && end.isNotEmpty)
        ? '$start - $end'
        : (start.isNotEmpty ? start : '');
    final className = item['class_name']?.toString() ?? '';
    final sectionName = item['section_name']?.toString() ?? '';
    final subjectName = item['subject_name']?.toString() ?? '';
    final teacherName = item['teacher_name']?.toString() ?? '';
    final stats = item['stats'] as Map<String, dynamic>?;
    final total = stats?['total'] ?? 0;
    final completed = stats?['completed'] ?? 0;

    final statusColor = evaluated ? brand : const Color(0xFFE11D48);

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border(left: BorderSide(color: statusColor, width: 4)),
            boxShadow: const [
              BoxShadow(
                color: Color(0x0F000000),
                blurRadius: 10,
                offset: Offset(0, 4),
              ),
            ],
          ),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          child: Row(
            children: [
              Container(
                width: 42,
                height: 42,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: brand.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      period,
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                        color: brandDark,
                      ),
                    ),
                    Text(
                      'পিরিয়ড',
                      style: TextStyle(fontSize: 8, color: muted),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '$className${sectionName.isNotEmpty ? ' - $sectionName' : ''} • $subjectName',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                        color: ink,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        if (timeLabel.isNotEmpty) ...[
                          Icon(Icons.schedule, size: 12, color: muted),
                          const SizedBox(width: 3),
                          Text(
                            timeLabel,
                            style: TextStyle(fontSize: 12, color: muted),
                          ),
                          const SizedBox(width: 8),
                        ],
                        if (teacherName.isNotEmpty) ...[
                          Icon(Icons.person_outline, size: 12, color: muted),
                          const SizedBox(width: 3),
                          Expanded(
                            child: Text(
                              teacherName,
                              style: TextStyle(fontSize: 12, color: muted),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 28,
                    height: 28,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      evaluated ? Icons.check_rounded : Icons.close_rounded,
                      color: statusColor,
                      size: 18,
                    ),
                  ),
                  if (evaluated && total is int && total > 0) ...[
                    const SizedBox(height: 4),
                    Text(
                      '$completed/$total',
                      style: TextStyle(fontSize: 10, color: muted),
                    ),
                  ],
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _FilterSheet extends StatefulWidget {
  final Color brand;
  final List<Map<String, dynamic>> classes;
  final List<Map<String, dynamic>> sections;
  final List<Map<String, dynamic>> subjects;
  final List<Map<String, dynamic>> teachers;
  final int? selectedClassId;
  final int? selectedSectionId;
  final int? selectedSubjectId;
  final Map<String, dynamic>? selectedTeacher;
  final ValueChanged<int?> onClassChanged;
  final ValueChanged<int?> onSectionChanged;
  final ValueChanged<int?> onSubjectChanged;
  final ValueChanged<Map<String, dynamic>?> onTeacherChanged;
  final VoidCallback onApply;
  final VoidCallback onClear;

  const _FilterSheet({
    required this.brand,
    required this.classes,
    required this.sections,
    required this.subjects,
    required this.teachers,
    required this.selectedClassId,
    required this.selectedSectionId,
    required this.selectedSubjectId,
    required this.selectedTeacher,
    required this.onClassChanged,
    required this.onSectionChanged,
    required this.onSubjectChanged,
    required this.onTeacherChanged,
    required this.onApply,
    required this.onClear,
  });

  @override
  State<_FilterSheet> createState() => _FilterSheetState();
}

class _FilterSheetState extends State<_FilterSheet> {
  int? _classId;
  int? _sectionId;
  int? _subjectId;
  Map<String, dynamic>? _teacher;

  @override
  void initState() {
    super.initState();
    _classId = widget.selectedClassId;
    _sectionId = widget.selectedSectionId;
    _subjectId = widget.selectedSubjectId;
    _teacher = widget.selectedTeacher;
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 16,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
          ),
          const SizedBox(height: 16),
          const Text(
            'ফিল্টার করুন',
            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<int>(
            initialValue: _classId,
            decoration: const InputDecoration(
              labelText: 'শ্রেণি',
              isDense: true,
              border: OutlineInputBorder(),
            ),
            items: widget.classes
                .map(
                  (c) => DropdownMenuItem<int>(
                    value: c['id'] is int
                        ? c['id'] as int
                        : int.tryParse(c['id']?.toString() ?? ''),
                    child: Text((c['name'] ?? '').toString()),
                  ),
                )
                .where((it) => it.value != null)
                .toList(),
            onChanged: (v) {
              setState(() {
                _classId = v;
                _sectionId = null;
                _subjectId = null;
              });
              widget.onClassChanged(v);
            },
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<int>(
            initialValue: _sectionId,
            decoration: const InputDecoration(
              labelText: 'শাখা',
              isDense: true,
              border: OutlineInputBorder(),
            ),
            items: widget.sections
                .map(
                  (s) => DropdownMenuItem<int>(
                    value: s['id'] is int
                        ? s['id'] as int
                        : int.tryParse(s['id']?.toString() ?? ''),
                    child: Text((s['name'] ?? '').toString()),
                  ),
                )
                .where((it) => it.value != null)
                .toList(),
            onChanged: (v) {
              setState(() => _sectionId = v);
              widget.onSectionChanged(v);
            },
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<int>(
            initialValue: _subjectId,
            decoration: const InputDecoration(
              labelText: 'বিষয়',
              isDense: true,
              border: OutlineInputBorder(),
            ),
            items: widget.subjects
                .map(
                  (s) => DropdownMenuItem<int>(
                    value: s['id'] is int
                        ? s['id'] as int
                        : int.tryParse(s['id']?.toString() ?? ''),
                    child: Text((s['name'] ?? '').toString()),
                  ),
                )
                .where((it) => it.value != null)
                .toList(),
            onChanged: (v) {
              setState(() => _subjectId = v);
              widget.onSubjectChanged(v);
            },
          ),
          const SizedBox(height: 12),
          DropdownSearch<Map<String, dynamic>>(
            popupProps: const PopupProps.menu(showSearchBox: true),
            items: widget.teachers,
            itemAsString: (m) => (m['name'] ?? '').toString(),
            selectedItem: _teacher,
            dropdownDecoratorProps: const DropDownDecoratorProps(
              dropdownSearchDecoration: InputDecoration(
                labelText: 'শিক্ষক',
                isDense: true,
                border: OutlineInputBorder(),
              ),
            ),
            onChanged: (m) {
              setState(() => _teacher = m);
              widget.onTeacherChanged(m);
            },
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: widget.onClear,
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  child: const Text('মুছে ফেলুন'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton(
                  onPressed: widget.onApply,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: widget.brand,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  child: const Text('প্রয়োগ করুন'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
