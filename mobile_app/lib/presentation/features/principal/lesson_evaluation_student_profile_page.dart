import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/network/dio_client.dart';

/// Principal-facing lesson-evaluation profile for a student — spans every
/// subject/teacher for the current academic year (unlike the teacher's own
/// per-subject student-history page): photo/name/class/section/group/roll,
/// a subject-wise stat breakdown, and a date-wise list optionally filtered
/// to one subject.
class LessonEvaluationStudentProfilePage extends StatefulWidget {
  final int studentId;
  final String? studentName;
  const LessonEvaluationStudentProfilePage({
    super.key,
    required this.studentId,
    this.studentName,
  });

  @override
  State<LessonEvaluationStudentProfilePage> createState() =>
      _LessonEvaluationStudentProfilePageState();
}

class _LessonEvaluationStudentProfilePageState
    extends State<LessonEvaluationStudentProfilePage> {
  static const Color _brand = Color(0xFF00BF6D);
  static const Color _brandDark = Color(0xFF049655);
  static const Color _bg = Color(0xFFF5F7F9);
  static const Color _ink = Color(0xFF1A1D1F);
  static const Color _muted = Color(0xFF6B7280);
  static const Color _completed = Color(0xFF16A34A);
  static const Color _partial = Color(0xFFD97706);
  static const Color _notDone = Color(0xFFDC2626);
  static const Color _absent = Color(0xFF64748B);

  static const int _perPage = 15;

  late final Dio _dio;
  final ScrollController _scrollController = ScrollController();

  bool _loading = true;
  bool _loadingMore = false;
  bool _hasMore = true;
  String? _error;
  int _page = 1;
  int? _subjectId;

  Map<String, dynamic> _student = const {};
  String? _academicYearName;
  List<Map<String, dynamic>> _subjects = [];
  List<Map<String, dynamic>> _subjectStats = [];
  Map<String, dynamic> _overall = const {};
  final List<Map<String, dynamic>> _entries = [];

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _scrollController.addListener(_onScroll);
    _load();
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_hasMore || _loadingMore || _loading) return;
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      _loadMore();
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
      _page = 1;
      _entries.clear();
      _hasMore = true;
    });
    await _fetchPage(1, replace: true);
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _loadMore() async {
    if (!_hasMore || _loadingMore) return;
    setState(() => _loadingMore = true);
    await _fetchPage(_page + 1, replace: false);
    if (mounted) setState(() => _loadingMore = false);
  }

  Future<void> _onSubjectFilterChanged(int? subjectId) async {
    setState(() => _subjectId = subjectId);
    await _load();
  }

  Future<void> _fetchPage(int page, {required bool replace}) async {
    try {
      final r = await _dio.get(
        'principal/reports/lesson-evaluations/student/${widget.studentId}',
        queryParameters: {
          if (_subjectId != null) 'subject_id': _subjectId,
          'page': page,
          'per_page': _perPage,
        },
      );
      final data = (r.data as Map<String, dynamic>?) ?? {};
      _student = (data['student'] as Map?)?.cast<String, dynamic>() ?? {};
      _academicYearName = (data['academic_year'] as Map?)?['name']?.toString();
      _subjects = ((data['subjects'] as List?) ?? [])
          .map((e) => (e as Map).cast<String, dynamic>())
          .toList();
      _subjectStats = ((data['subject_stats'] as List?) ?? [])
          .map((e) => (e as Map).cast<String, dynamic>())
          .toList();
      _overall = (data['overall_summary'] as Map?)?.cast<String, dynamic>() ?? {};
      final list = (data['entries'] as List? ?? []).cast<Map>();
      _entries.addAll(list.map((e) => e.cast<String, dynamic>()));
      _hasMore = (data['has_more'] as bool?) ?? false;
      _page = page;
    } catch (e) {
      if (replace) _error = 'লোড ব্যর্থ';
    }
  }

  Future<void> _callPhone(String? number) async {
    if (number == null || number.trim().isEmpty) return;
    final uri = Uri(scheme: 'tel', path: number.trim());
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri);
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final name = (_student['name'] as String?) ?? widget.studentName ?? '';
    final roll = _student['roll'];
    final photo = _student['photo_url'] as String?;
    final phone = _student['guardian_phone'] as String?;
    final className = _student['class_name'] as String?;
    final sectionName = _student['section_name'] as String?;
    final groupName = _student['group_name'] as String?;

    return Scaffold(
      backgroundColor: _bg,
      appBar: AppBar(
        title: Text(name.isNotEmpty ? name : 'শিক্ষার্থী প্রোফাইল'),
        backgroundColor: _brand,
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _brand))
          : _error != null
          ? Center(child: Text(_error!))
          : RefreshIndicator(
              color: _brand,
              onRefresh: _load,
              child: ListView(
                controller: _scrollController,
                padding: const EdgeInsets.all(16),
                children: [
                  _buildStudentCard(
                    name: name,
                    roll: roll,
                    photo: photo,
                    phone: phone,
                    className: className,
                    sectionName: sectionName,
                    groupName: groupName,
                  ),
                  const SizedBox(height: 16),
                  _buildOverallSummary(),
                  const SizedBox(height: 16),
                  if (_subjectStats.isNotEmpty) ...[
                    const Text(
                      'বিষয়ভিত্তিক পরিসংখ্যান (বর্তমান শিক্ষাবর্ষ)',
                      style: TextStyle(fontWeight: FontWeight.bold, color: _ink),
                    ),
                    const SizedBox(height: 8),
                    _buildSubjectStatsTable(),
                    const SizedBox(height: 20),
                  ],
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'দৈনিক ভিত্তিক তালিকা',
                        style: TextStyle(fontWeight: FontWeight.bold, color: _ink),
                      ),
                      _buildSubjectFilterDropdown(),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ..._buildEntryList(),
                  if (_loadingMore)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 16),
                      child: Center(
                        child: SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(strokeWidth: 2.5),
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }

  Widget _buildStudentCard({
    required String name,
    required dynamic roll,
    required String? photo,
    required String? phone,
    required String? className,
    required String? sectionName,
    required String? groupName,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [_brand, _brandDark],
        ),
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: _brand.withValues(alpha: 0.25),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 32,
            backgroundColor: Colors.white,
            backgroundImage: (photo != null && photo.isNotEmpty)
                ? NetworkImage(photo)
                : null,
            child: (photo == null || photo.isEmpty)
                ? Text(
                    name.isNotEmpty ? name[0].toUpperCase() : '?',
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: _brandDark,
                    ),
                  )
                : null,
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  [
                    if (roll != null) 'রোল: $roll',
                    if (className != null)
                      '$className${sectionName != null ? ' - $sectionName' : ''}',
                    if (groupName != null) groupName,
                  ].join(' • '),
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                ),
                if (_academicYearName != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      'শিক্ষাবর্ষ: $_academicYearName',
                      style: const TextStyle(color: Colors.white70, fontSize: 12),
                    ),
                  ),
                if (phone != null && phone.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 10),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(20),
                      onTap: () => _callPhone(phone),
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.phone, size: 14, color: Colors.white),
                            const SizedBox(width: 6),
                            Text(
                              phone,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildOverallSummary() {
    final total = (_overall['total'] as num?)?.toInt() ?? 0;
    final completed = (_overall['completed'] as num?)?.toInt() ?? 0;
    final partial = (_overall['partial'] as num?)?.toInt() ?? 0;
    final notDone = (_overall['not_done'] as num?)?.toInt() ?? 0;
    final absent = (_overall['absent'] as num?)?.toInt() ?? 0;
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.grey.shade200),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'বর্তমান শিক্ষাবর্ষে সার্বিক সারসংক্ষেপ',
              style: Theme.of(
                context,
              ).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 4),
            Text(
              'মোট ক্লাস: $total',
              style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _summaryChip('সম্পন্ন', completed, _completed),
                _summaryChip('আংশিক', partial, _partial),
                _summaryChip('হয়নি', notDone, _notDone),
                _summaryChip('অনুপস্থিত', absent, _absent),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _summaryChip(String label, int value, Color color) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 44,
          height: 44,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: Text(
            '$value',
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.bold,
              fontSize: 16,
            ),
          ),
        ),
        const SizedBox(height: 6),
        Text(label, style: const TextStyle(fontSize: 11)),
      ],
    );
  }

  Widget _buildSubjectStatsTable() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            child: Row(
              children: const [
                Expanded(flex: 3, child: Text('বিষয়', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: _muted))),
                Expanded(child: Text('মোট', textAlign: TextAlign.center, style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: _muted))),
                Expanded(child: Text('সম্পন্ন', textAlign: TextAlign.center, style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: _completed))),
                Expanded(child: Text('আংশিক', textAlign: TextAlign.center, style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: _partial))),
                Expanded(child: Text('হয়নি', textAlign: TextAlign.center, style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: _notDone))),
              ],
            ),
          ),
          const Divider(height: 1),
          ..._subjectStats.map((s) {
            return Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              child: Row(
                children: [
                  Expanded(
                    flex: 3,
                    child: Text(
                      (s['subject_name'] ?? '').toString(),
                      style: const TextStyle(fontSize: 13, color: _ink),
                    ),
                  ),
                  Expanded(child: Text('${s['total'] ?? 0}', textAlign: TextAlign.center)),
                  Expanded(child: Text('${s['completed'] ?? 0}', textAlign: TextAlign.center, style: const TextStyle(color: _completed, fontWeight: FontWeight.w600))),
                  Expanded(child: Text('${s['partial'] ?? 0}', textAlign: TextAlign.center, style: const TextStyle(color: _partial, fontWeight: FontWeight.w600))),
                  Expanded(child: Text('${s['not_done'] ?? 0}', textAlign: TextAlign.center, style: const TextStyle(color: _notDone, fontWeight: FontWeight.w600))),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildSubjectFilterDropdown() {
    return SizedBox(
      width: 150,
      child: DropdownButtonFormField<int>(
        initialValue: _subjectId,
        isDense: true,
        decoration: InputDecoration(
          isDense: true,
          contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
          hintText: 'সব বিষয়',
        ),
        items: [
          const DropdownMenuItem<int>(value: null, child: Text('সব বিষয়')),
          ..._subjects.map(
            (s) => DropdownMenuItem<int>(
              value: s['id'] is int ? s['id'] as int : int.tryParse(s['id'].toString()),
              child: Text(
                (s['name'] ?? '').toString(),
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
        ],
        onChanged: _onSubjectFilterChanged,
      ),
    );
  }

  List<Widget> _buildEntryList() {
    if (_entries.isEmpty) {
      return [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 24),
          child: Center(
            child: Text(
              'কোনো মূল্যায়ন রেকর্ড নেই',
              style: TextStyle(color: Colors.grey.shade600),
            ),
          ),
        ),
      ];
    }
    return _entries.map((e) {
      final date = (e['date'] as String?) ?? '';
      final subjectName = (e['subject_name'] as String?) ?? '';
      final teacherName = (e['teacher_name'] as String?) ?? '';
      final status = e['status'] as String?;
      final notes = e['notes'] as String?;
      final style = _statusStyle(status);
      return Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Card(
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
            side: BorderSide(color: Colors.grey.shade200),
          ),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 40,
                  height: 40,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: style.$2.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(style.$3, color: style.$2, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              '$date${subjectName.isNotEmpty ? ' • $subjectName' : ''}',
                              style: const TextStyle(fontWeight: FontWeight.w600),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              color: style.$2.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              style.$1,
                              style: TextStyle(
                                color: style.$2,
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                      if (teacherName.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Text(
                            teacherName,
                            style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                          ),
                        ),
                      if (notes != null && notes.trim().isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Text(
                            notes,
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 12.5,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }).toList();
  }

  (String, Color, IconData) _statusStyle(String? status) {
    switch (status) {
      case 'completed':
        return ('সম্পন্ন', _completed, Icons.check_circle);
      case 'partial':
        return ('আংশিক', _partial, Icons.timelapse);
      case 'not_done':
        return ('হয়নি', _notDone, Icons.close);
      case 'absent':
        return ('অনুপস্থিত', _absent, Icons.person_off);
      default:
        return ('অজানা', Colors.grey, Icons.help_outline);
    }
  }
}
