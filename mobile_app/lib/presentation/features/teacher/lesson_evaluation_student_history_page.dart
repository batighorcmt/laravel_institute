import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/network/dio_client.dart';
import 'lesson_evaluation_theme.dart';

class LessonEvaluationStudentHistoryPage extends StatefulWidget {
  final int studentId;
  final String studentName;
  final int classId;
  final int sectionId;
  final int subjectId;
  const LessonEvaluationStudentHistoryPage({
    super.key,
    required this.studentId,
    required this.studentName,
    required this.classId,
    required this.sectionId,
    required this.subjectId,
  });

  @override
  State<LessonEvaluationStudentHistoryPage> createState() =>
      _LessonEvaluationStudentHistoryPageState();
}

class _LessonEvaluationStudentHistoryPageState
    extends State<LessonEvaluationStudentHistoryPage> {
  static const int _perPage = 10;

  late final Dio _dio;
  final ScrollController _scrollController = ScrollController();

  bool _loading = true;
  bool _loadingMore = false;
  bool _hasMore = true;
  String? _error;
  int _page = 1;

  Map<String, dynamic> _student = const {};
  String? _className;
  String? _sectionName;
  String? _subjectName;
  String? _academicYearName;
  Map<String, dynamic> _summary = const {};
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

  Future<void> _fetchPage(int page, {required bool replace}) async {
    try {
      final r = await _dio.get(
        'teacher/lesson-evaluations/student-history',
        queryParameters: {
          'student_id': widget.studentId,
          'class_id': widget.classId,
          'section_id': widget.sectionId,
          'subject_id': widget.subjectId,
          'page': page,
          'per_page': _perPage,
        },
      );
      final data = (r.data as Map<String, dynamic>?) ?? {};
      _student = (data['student'] as Map?)?.cast<String, dynamic>() ?? {};
      _className = data['class_name'] as String?;
      _sectionName = data['section_name'] as String?;
      _subjectName = data['subject_name'] as String?;
      _academicYearName = (data['academic_year'] as Map?)?['name']?.toString();
      _summary = (data['summary'] as Map?)?.cast<String, dynamic>() ?? {};
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
    final name = (_student['name'] as String?) ?? widget.studentName;
    final roll = _student['roll'];
    final photo = _student['photo_url'] as String?;
    final phone = _student['guardian_phone'] as String?;

    return Scaffold(
      backgroundColor: LeColors.bg,
      appBar: AppBar(title: Text(name)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text(_error!))
          : RefreshIndicator(
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
                  ),
                  const SizedBox(height: 16),
                  _buildSummaryCard(),
                  const SizedBox(height: 20),
                  Text(
                    'মূল্যায়নের তালিকা',
                    style: Theme.of(
                      context,
                    ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
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
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
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
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 30,
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
                      color: LeColors.brandDark,
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
                    if (_className != null) '$_className${_sectionName != null ? ' - $_sectionName' : ''}',
                  ].join(' • '),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 13,
                  ),
                ),
                if (_subjectName != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      'বিষয়: $_subjectName${_academicYearName != null ? ' • শিক্ষাবর্ষ $_academicYearName' : ''}',
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

  Widget _buildSummaryCard() {
    final total = (_summary['total'] as num?)?.toInt() ?? 0;
    final completed = (_summary['completed'] as num?)?.toInt() ?? 0;
    final partial = (_summary['partial'] as num?)?.toInt() ?? 0;
    final notDone = (_summary['not_done'] as num?)?.toInt() ?? 0;
    final absent = (_summary['absent'] as num?)?.toInt() ?? 0;
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
              'বর্তমান শিক্ষাবর্ষে সারসংক্ষেপ',
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
                _summaryChip('সম্পন্ন', completed, LeColors.completed),
                _summaryChip('আংশিক', partial, LeColors.partial),
                _summaryChip('হয়নি', notDone, LeColors.notDone),
                _summaryChip('অনুপস্থিত', absent, LeColors.absent),
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
                              date,
                              style: const TextStyle(
                                fontWeight: FontWeight.w600,
                              ),
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
        return ('সম্পন্ন', LeColors.completed, Icons.check_circle);
      case 'partial':
        return ('আংশিক', LeColors.partial, Icons.timelapse);
      case 'not_done':
        return ('হয়নি', LeColors.notDone, Icons.close);
      case 'absent':
        return ('অনুপস্থিত', LeColors.absent, Icons.person_off);
      default:
        return ('অজানা', Colors.grey, Icons.help_outline);
    }
  }
}
