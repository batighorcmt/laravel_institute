import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/utils/error_utils.dart';

/// Opens a modern bottom-sheet modal showing a student's photo and their
/// attendance summary for their own class (total working days so far this
/// academic year, present/absent/late/approved-leave counts, and a
/// highlighted current consecutive-absence streak). Used from both the
/// class-attendance and extra-class-attendance taking pages when a teacher
/// taps a student's name.
Future<void> showStudentStatsModal(
  BuildContext context, {
  required int studentId,
  required int classId,
  required int sectionId,
  required String fallbackName,
  required String fallbackPhotoUrl,
}) {
  return showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (ctx) => _StudentStatsSheet(
      studentId: studentId,
      classId: classId,
      sectionId: sectionId,
      fallbackName: fallbackName,
      fallbackPhotoUrl: fallbackPhotoUrl,
    ),
  );
}

class _StudentStatsSheet extends StatefulWidget {
  final int studentId;
  final int classId;
  final int sectionId;
  final String fallbackName;
  final String fallbackPhotoUrl;
  const _StudentStatsSheet({
    required this.studentId,
    required this.classId,
    required this.sectionId,
    required this.fallbackName,
    required this.fallbackPhotoUrl,
  });

  @override
  State<_StudentStatsSheet> createState() => _StudentStatsSheetState();
}

class _StudentStatsSheetState extends State<_StudentStatsSheet> {
  late final Dio _dio;
  bool _loading = true;
  String? _error;

  String _name = '';
  String _photoUrl = '';
  int _totalWorkingDays = 0;
  int _present = 0;
  int _absent = 0;
  int _late = 0;
  int _approvedLeave = 0;
  int _consecutiveAbsent = 0;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _name = widget.fallbackName;
    _photoUrl = widget.fallbackPhotoUrl;
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await _dio.get(
        'teacher/students-attendance/student/${widget.studentId}/stats',
        queryParameters: {'class_id': widget.classId, 'section_id': widget.sectionId},
      );
      final data = r.data as Map<String, dynamic>? ?? {};
      final student = (data['student'] is Map)
          ? Map<String, dynamic>.from(data['student'])
          : <String, dynamic>{};

      int toInt(dynamic v) {
        if (v == null) return 0;
        if (v is num) return v.toInt();
        return int.tryParse(v.toString()) ?? 0;
      }

      _name = (student['name'] ?? _name).toString();
      _photoUrl = (student['photo_url'] ?? _photoUrl).toString();
      _totalWorkingDays = toInt(data['total_working_days']);
      _present = toInt(data['present']);
      _absent = toInt(data['absent']);
      _late = toInt(data['late']);
      _approvedLeave = toInt(data['approved_leave']);
      _consecutiveAbsent = toInt(data['consecutive_absent']);
    } catch (e) {
      _error = friendlyErrorMessage(e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final mediaHeight = MediaQuery.of(context).size.height;
    return DraggableScrollableSheet(
      initialChildSize: 0.62,
      minChildSize: 0.4,
      maxChildSize: 0.9,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(_error!, textAlign: TextAlign.center),
                  ),
                )
              : ListView(
                  controller: scrollController,
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
                  children: [
                    Center(
                      child: Container(
                        width: 40,
                        height: 4,
                        margin: const EdgeInsets.only(bottom: 16),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade300,
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    Center(
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(20),
                        child: Container(
                          width: mediaHeight * 0.18,
                          height: mediaHeight * 0.18,
                          constraints: const BoxConstraints(
                            minWidth: 110,
                            minHeight: 110,
                            maxWidth: 160,
                            maxHeight: 160,
                          ),
                          color: Colors.grey.shade200,
                          child: _photoUrl.isEmpty
                              ? Icon(Icons.person, size: 64, color: Colors.grey[500])
                              : Image.network(
                                  _photoUrl,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, _, _) => Icon(
                                    Icons.person,
                                    size: 64,
                                    color: Colors.grey[500],
                                  ),
                                ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 14),
                    Text(
                      _name,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 20),
                    GridView.count(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      crossAxisCount: 2,
                      mainAxisSpacing: 10,
                      crossAxisSpacing: 10,
                      childAspectRatio: 2.3,
                      children: [
                        _StatCard(
                          label: 'মোট কর্মদিবস',
                          value: '$_totalWorkingDays',
                          color: Colors.blueGrey,
                          icon: Icons.event_available,
                        ),
                        _StatCard(
                          label: 'উপস্থিত',
                          value: '$_present',
                          color: Colors.green,
                          icon: Icons.check_circle,
                        ),
                        _StatCard(
                          label: 'অনুপস্থিত',
                          value: '$_absent',
                          color: Colors.red,
                          icon: Icons.cancel,
                        ),
                        _StatCard(
                          label: 'দেরি',
                          value: '$_late',
                          color: Colors.orange,
                          icon: Icons.access_time_filled,
                        ),
                        _StatCard(
                          label: 'অনুমোদিত ছুটি',
                          value: '$_approvedLeave',
                          color: Colors.blueGrey,
                          icon: Icons.event_busy,
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    _ConsecutiveAbsentHighlight(count: _consecutiveAbsent),
                  ],
                ),
        );
      },
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final IconData icon;
  const _StatCard({
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: color,
                  ),
                ),
                Text(
                  label,
                  style: const TextStyle(fontSize: 11.5, color: Colors.black54),
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ConsecutiveAbsentHighlight extends StatelessWidget {
  final int count;
  const _ConsecutiveAbsentHighlight({required this.count});

  @override
  Widget build(BuildContext context) {
    final bool warn = count > 0;
    final color = warn ? Colors.red : Colors.green;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.4), width: 1.2),
      ),
      child: Row(
        children: [
          Icon(
            warn ? Icons.warning_amber_rounded : Icons.verified,
            color: color,
            size: 30,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'ক্রমাগত অনুপস্থিতি',
                  style: TextStyle(
                    fontSize: 12.5,
                    color: color,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  warn ? '$count দিন' : 'নিয়মিত উপস্থিত',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: color,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
