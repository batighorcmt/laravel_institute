import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import '../../../core/utils/error_utils.dart';

class TeamMarkAttendancePage extends StatefulWidget {
  final int teamId;
  final String title;
  const TeamMarkAttendancePage({
    super.key,
    required this.teamId,
    required this.title,
  });

  @override
  State<TeamMarkAttendancePage> createState() =>
      _TeamMarkAttendancePageState();
}

class _TeamMarkAttendancePageState extends State<TeamMarkAttendancePage> {
  late final Dio _dio;
  bool _loading = true;
  String? _error;
  String _date = _formatDate(DateTime.now());
  List<_TeamStudentRow> _students = const [];
  bool _isToday = true;
  bool _anyMarkable = true;
  int _statTotal = 0, _statPresent = 0, _statAbsent = 0, _statLate = 0;
  bool _submitting = false;

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
        'teacher/students-attendance/team/teams/${widget.teamId}/students',
        queryParameters: {'date': _date},
      );
      final data = r.data as Map<String, dynamic>? ?? {};
      _date = (data['date'] as String?) ?? _date;
      _isToday = _date == _formatDate(DateTime.now());
      _anyMarkable = data['any_markable'] == true;

      final rawList = data['students'];
      final list = <Map<String, dynamic>>[];
      if (rawList is List) {
        for (final e in rawList) {
          if (e is Map) {
            try {
              list.add(Map<String, dynamic>.from(e));
            } catch (_) {}
          }
        }
      }
      final stats = (data['stats'] is Map)
          ? Map<String, dynamic>.from(data['stats'])
          : <String, dynamic>{};

      int toInt(dynamic v) {
        if (v == null) return 0;
        if (v is num) return v.toInt();
        return int.tryParse(v.toString()) ?? 0;
      }

      _students = list
          .map(
            (m) => _TeamStudentRow(
              id: (m['id'] is num)
                  ? (m['id'] as num).toInt()
                  : (int.tryParse(m['id']?.toString() ?? '') ?? 0),
              name: (m['name'] ?? '').toString(),
              roll: (m['roll'] is num)
                  ? (m['roll'] as num).toInt()
                  : (int.tryParse(m['roll']?.toString() ?? '') ?? 0),
              className: (m['class_name'] ?? '').toString(),
              sectionName: (m['section_name'] ?? '').toString(),
              photoUrl: (m['photo_url'] ?? '').toString(),
              classStatus: m['class_status']?.toString(),
              canMark: m['can_mark'] == true,
              status: _parseStatus(m['status']?.toString()),
            ),
          )
          .toList();

      _statTotal = toInt(stats['total']);
      _statPresent = toInt(stats['present']);
      _statAbsent = toInt(stats['absent']);
      _statLate = toInt(stats['late']);
    } catch (e) {
      _error = friendlyErrorMessage(e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final initial = _parseDate(_date) ?? now;
    final picked = await showDatePicker(
      context: context,
      initialDate: initial.isAfter(now) ? now : initial,
      firstDate: DateTime(now.year - 2, 1, 1),
      lastDate: now,
      helpText: 'তারিখ নির্বাচন করুন',
    );
    if (picked != null) {
      setState(() => _date = _formatDate(picked));
      await _load();
    }
  }

  DateTime? _parseDate(String s) {
    try {
      final p = s.split('-');
      if (p.length == 3) {
        return DateTime(int.parse(p[0]), int.parse(p[1]), int.parse(p[2]));
      }
    } catch (_) {}
    return null;
  }

  AttendanceStatus? _parseStatus(String? s) {
    switch (s) {
      case 'present':
        return AttendanceStatus.present;
      case 'absent':
        return AttendanceStatus.absent;
      case 'late':
        return AttendanceStatus.late;
    }
    return null;
  }

  bool get _isComplete {
    final markable = _students.where((s) => s.canMark);
    return markable.isNotEmpty && markable.every((e) => e.status != null);
  }

  Future<void> _submit() async {
    if (!_isComplete || _submitting) return;
    _submitting = true;
    setState(() {});
    try {
      final body = {
        'date': _date,
        'items': _students
            .where((s) => s.canMark)
            .map((s) => {'student_id': s.id, 'status': s.status!.name})
            .toList(),
      };
      final r = await _dio.post(
        'teacher/students-attendance/team/teams/${widget.teamId}/attendance',
        data: body,
      );
      if (!mounted) return;
      final data = r.data as Map<String, dynamic>? ?? {};
      final msg = data['message'] is String
          ? data['message'] as String
          : 'সফলভাবে সংরক্ষিত হয়েছে';
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(msg)));
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(friendlyErrorMessage(e))));
    }
    _submitting = false;
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text(_error!))
          : Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(12.0),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          'তারিখ: $_date',
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                      ),
                      TextButton.icon(
                        onPressed: _pickDate,
                        icon: const Icon(Icons.calendar_today),
                        label: const Text('তারিখ নির্বাচন'),
                      ),
                    ],
                  ),
                ),
                if (!_anyMarkable)
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.symmetric(horizontal: 12),
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.amber.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: Colors.amber.withValues(alpha: 0.5),
                      ),
                    ),
                    child: const Text(
                      'কোনো শিক্ষার্থীর শ্রেণি হাজিরা এখনো নেওয়া হয়নি। শ্রেণি হাজিরা সম্পন্ন হলে এখানে টিম হাজিরা নেওয়া যাবে।',
                      style: TextStyle(fontSize: 12.5),
                    ),
                  ),
                _TeamCountsBar(
                  total: _statTotal,
                  present: _statPresent,
                  absent: _statAbsent,
                  late: _statLate,
                ),
                const Divider(height: 1),
                Expanded(
                  child: _students.isEmpty
                      ? const Center(child: Text('কোনো সদস্য পাওয়া যায়নি'))
                      : RefreshIndicator(
                          onRefresh: _load,
                          child: ListView.separated(
                            padding: const EdgeInsets.all(12),
                            separatorBuilder: (_, i) =>
                                const SizedBox(height: 8),
                            itemCount: _students.length,
                            itemBuilder: (ctx, i) {
                              final s = _students[i];
                              return _TeamStudentRowWidget(
                                row: s,
                                enabled: _isToday && s.canMark,
                                onChanged: (st) {
                                  if (!_isToday || !s.canMark) return;
                                  setState(() {
                                    _students[i] = s.copyWith(status: st);
                                  });
                                },
                              );
                            },
                          ),
                        ),
                ),
                if (_isToday)
                  SafeArea(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                      child: SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          onPressed: (_isComplete && !_submitting)
                              ? _submit
                              : null,
                          icon: const Icon(Icons.save),
                          label: const Text('সাবমিট করুন'),
                        ),
                      ),
                    ),
                  ),
                if (!_isToday)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                    child: Text(
                      'পূর্বের তারিখে শুধু রেকর্ড দেখা যাবে। আজকের তারিখে হাজিরা রেকর্ড করা যাবে।',
                      style: Theme.of(
                        context,
                      ).textTheme.bodySmall?.copyWith(color: Colors.grey[700]),
                    ),
                  ),
              ],
            ),
    );
  }
}

enum AttendanceStatus { present, absent, late }

class _TeamStudentRow {
  final int id;
  final String name;
  final int roll;
  final String className;
  final String sectionName;
  final String photoUrl;
  final String? classStatus;
  final bool canMark;
  final AttendanceStatus? status;
  const _TeamStudentRow({
    required this.id,
    required this.name,
    required this.roll,
    required this.className,
    required this.sectionName,
    required this.photoUrl,
    required this.classStatus,
    required this.canMark,
    required this.status,
  });
  _TeamStudentRow copyWith({AttendanceStatus? status}) => _TeamStudentRow(
    id: id,
    name: name,
    roll: roll,
    className: className,
    sectionName: sectionName,
    photoUrl: photoUrl,
    classStatus: classStatus,
    canMark: canMark,
    status: status,
  );

  String get classLabel {
    if (className.isEmpty && sectionName.isEmpty) return '';
    if (sectionName.isEmpty) return className;
    if (className.isEmpty) return sectionName;
    return '$className - $sectionName';
  }
}

class _TeamStudentRowWidget extends StatelessWidget {
  final _TeamStudentRow row;
  final ValueChanged<AttendanceStatus> onChanged;
  final bool enabled;
  const _TeamStudentRowWidget({
    required this.row,
    required this.onChanged,
    required this.enabled,
  });

  @override
  Widget build(BuildContext context) {
    // A student whose class attendance hasn't been taken yet today can't
    // have team attendance recorded — the server enforces this too.
    if (!row.canMark) {
      return Card(
        elevation: 0,
        color: Colors.grey.shade100,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          child: Row(
            children: [
              _Avatar(photoUrl: row.photoUrl),
              const SizedBox(width: 12),
              SizedBox(
                width: 36,
                child: Text(
                  '${row.roll}',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(row.name),
                    if (row.classLabel.isNotEmpty)
                      Text(
                        row.classLabel,
                        style: TextStyle(fontSize: 11, color: Colors.grey[600]),
                      ),
                    const Text(
                      'শ্রেণি হাজিরা নেওয়া হয়নি',
                      style: TextStyle(fontSize: 11, color: Colors.orange),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    }

    final isForcedAbsent = row.classStatus == 'absent';
    final effectiveStatus = isForcedAbsent
        ? AttendanceStatus.absent
        : row.status;

    return Card(
      elevation: 0,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        child: Row(
          children: [
            _Avatar(photoUrl: row.photoUrl),
            const SizedBox(width: 12),
            SizedBox(
              width: 36,
              child: Text(
                '${row.roll}',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(row.name),
                  if (row.classLabel.isNotEmpty)
                    Text(
                      row.classLabel,
                      style: TextStyle(fontSize: 11, color: Colors.grey[600]),
                    ),
                  if (isForcedAbsent)
                    const Text(
                      'শ্রেণিতে অনুপস্থিত',
                      style: TextStyle(fontSize: 11, color: Colors.red),
                    ),
                ],
              ),
            ),
            _StatusButton(
              icon: Icons.check,
              tooltip: 'উপস্থিত',
              color: Colors.green,
              selected: effectiveStatus == AttendanceStatus.present,
              onTap: (enabled && !isForcedAbsent)
                  ? () => onChanged(AttendanceStatus.present)
                  : () {},
            ),
            const SizedBox(width: 6),
            _StatusButton(
              icon: Icons.close,
              tooltip: 'অনুপস্থিত',
              color: Colors.red,
              selected: effectiveStatus == AttendanceStatus.absent,
              onTap: (enabled && !isForcedAbsent)
                  ? () => onChanged(AttendanceStatus.absent)
                  : () {},
            ),
            const SizedBox(width: 6),
            _StatusButton(
              icon: Icons.access_time_filled,
              tooltip: 'দেরি',
              color: Colors.orange,
              selected: effectiveStatus == AttendanceStatus.late,
              onTap: (enabled && !isForcedAbsent)
                  ? () => onChanged(AttendanceStatus.late)
                  : () {},
            ),
          ],
        ),
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  final String photoUrl;
  const _Avatar({required this.photoUrl});
  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: Container(
        width: 36,
        height: 36,
        color: Colors.grey.shade200,
        child: photoUrl.isEmpty
            ? Icon(Icons.person, size: 20, color: Colors.grey[600])
            : Image.network(
                photoUrl,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) =>
                    Icon(Icons.person, size: 20, color: Colors.grey[600]),
              ),
      ),
    );
  }
}

class _StatusButton extends StatelessWidget {
  final IconData icon;
  final String tooltip;
  final Color color;
  final bool selected;
  final VoidCallback onTap;
  const _StatusButton({
    required this.icon,
    required this.tooltip,
    required this.color,
    required this.selected,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: InkResponse(
        onTap: onTap,
        radius: 22,
        child: Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: selected ? color : color.withValues(alpha: 0.12),
            shape: BoxShape.circle,
            border: Border.all(color: color.withValues(alpha: 0.6)),
          ),
          child: Icon(icon, size: 18, color: selected ? Colors.white : color),
        ),
      ),
    );
  }
}

class _TeamCountsBar extends StatelessWidget {
  final int total;
  final int present;
  final int absent;
  final int late;
  const _TeamCountsBar({
    required this.total,
    required this.present,
    required this.absent,
    required this.late,
  });
  @override
  Widget build(BuildContext context) {
    final style = Theme.of(context).textTheme.bodySmall;
    Widget chip(Color c, String label, String value) => Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: c.withValues(alpha: 0.08),
        border: Border.all(color: c.withValues(alpha: 0.4)),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Text(
            label,
            style: style?.copyWith(color: c, fontWeight: FontWeight.w600),
          ),
          const SizedBox(width: 6),
          Text(value, style: style),
        ],
      ),
    );
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
      child: SizedBox(
        height: 34,
        child: ListView(
          scrollDirection: Axis.horizontal,
          children: [
            chip(Colors.grey, 'মোট', '$total'),
            const SizedBox(width: 8),
            chip(Colors.green, 'উপস্থিত', '$present'),
            const SizedBox(width: 8),
            chip(Colors.red, 'অনুপস্থিত', '$absent'),
            const SizedBox(width: 8),
            chip(Colors.orange, 'দেরি', '$late'),
          ],
        ),
      ),
    );
  }
}

String _formatDate(DateTime d) {
  final m = d.month.toString().padLeft(2, '0');
  final day = d.day.toString().padLeft(2, '0');
  return '${d.year}-$m-$day';
}
