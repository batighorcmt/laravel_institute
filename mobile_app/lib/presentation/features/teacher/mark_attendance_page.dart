import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';

class ClassSectionMarkAttendancePage extends StatefulWidget {
  final int sectionId;
  final String title;
  const ClassSectionMarkAttendancePage({
    super.key,
    required this.sectionId,
    required this.title,
  });

  @override
  State<ClassSectionMarkAttendancePage> createState() =>
      _ClassSectionMarkAttendancePageState();
}

class _ClassSectionMarkAttendancePageState
    extends State<ClassSectionMarkAttendancePage> {
  late final Dio _dio;
  bool _loading = true;
  String? _error;
  String _date = _formatDate(DateTime.now());
  List<_StudentRow> _students = const [];
  bool _isToday = true;
  int _statTotal = 0, _statPresent = 0, _statAbsent = 0, _statLate = 0;

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
        'teacher/students-attendance/class/sections/${widget.sectionId}/students',
        queryParameters: {'date': _date},
      );
      final data = r.data as Map<String, dynamic>? ?? {};
      _date = (data['date'] as String?) ?? _date;
      _isToday = _date == _formatDate(DateTime.now());
      final list = (data['students'] as List? ?? []).cast<Map>();
      final stats = (data['stats'] as Map?) ?? const {};
      _students = list
          .map(
            (m) => _StudentRow(
              id: m['id'] as int,
              name: (m['name'] ?? '') as String,
              roll: (m['roll'] ?? 0) as int,
              photoUrl: (m['photo_url'] ?? '') as String,
              status: _parseStatus(m['status'] as String?),
            ),
          )
          .toList();
      _statTotal = (stats['total'] as num?)?.toInt() ?? 0;
      _statPresent = (stats['present'] as num?)?.toInt() ?? 0;
      _statAbsent = (stats['absent'] as num?)?.toInt() ?? 0;
      _statLate = (stats['late'] as num?)?.toInt() ?? 0;
    } catch (e) {
      _error = 'ডাটা লোড ব্যর্থ';
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
        });
      }
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
      setState(() {
        _date = _formatDate(picked);
      });
      await _load();
    }
  }

  DateTime? _parseDate(String s) {
    try {
      final p = s.split('-');
      if (p.length == 3)
        return DateTime(int.parse(p[0]), int.parse(p[1]), int.parse(p[2]));
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

  Future<void> _selectAll(AttendanceStatus st) async {
    setState(() {
      _students = _students.map((e) => e.copyWith(status: st)).toList();
    });
  }

  bool get _isComplete =>
      _students.isNotEmpty && _students.every((e) => e.status != null);

  Future<void> _submit() async {
    if (!_isComplete) return;
    try {
      final body = {
        'date': _date,
        'items': _students
            .map((s) => {'student_id': s.id, 'status': s.status!.name})
            .toList(),
      };
      final r = await _dio.post(
        'teacher/students-attendance/class/sections/${widget.sectionId}/attendance',
        data: body,
      );
      if (!mounted) return;
      final data = r.data as Map<String, dynamic>? ?? {};
      final msg = data['message'] is String
          ? data['message'] as String
          : 'সফলভাবে সংরক্ষিত হয়েছে';
      String extra = '';
      if (data['sms_report'] is Map) {
        final rpt = data['sms_report'] as Map;
        final queued = rpt['sent'] ?? rpt['queued_count'] ?? null;
        if (queued != null) {
          extra = ' SMS queued: $queued';
        }
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(msg + extra)));
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('সংরক্ষণ ব্যর্থ')));
    }
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
                _HeaderBar(
                  date: _date,
                  onPickDate: _pickDate,
                  onSelectAllPresent: _isToday
                      ? () => _selectAll(AttendanceStatus.present)
                      : null,
                  onSelectAllAbsent: _isToday
                      ? () => _selectAll(AttendanceStatus.absent)
                      : null,
                  onSelectAllLate: _isToday
                      ? () => _selectAll(AttendanceStatus.late)
                      : null,
                ),
                _CountsBar(
                  total: _statTotal,
                  present: _statPresent,
                  absent: _statAbsent,
                  late: _statLate,
                ),
                const Divider(height: 1),
                Expanded(
                  child: ListView.separated(
                    padding: const EdgeInsets.all(12),
                    separatorBuilder: (_, i) => const SizedBox(height: 8),
                    itemCount: _students.length,
                    itemBuilder: (ctx, i) {
                      final s = _students[i];
                      return _StudentRowWidget(
                        row: s,
                        enabled: _isToday,
                        onChanged: (st) {
                          if (!_isToday) return;
                          setState(() {
                            _students[i] = s.copyWith(status: st);
                          });
                        },
                      );
                    },
                  ),
                ),
                SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                    child: SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: _isToday && _isComplete ? _submit : null,
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

class _HeaderBar extends StatelessWidget {
  final String date;
  final VoidCallback onPickDate;
  final VoidCallback? onSelectAllPresent;
  final VoidCallback? onSelectAllAbsent;
  final VoidCallback? onSelectAllLate;
  const _HeaderBar({
    required this.date,
    required this.onPickDate,
    required this.onSelectAllPresent,
    required this.onSelectAllAbsent,
    required this.onSelectAllLate,
  });
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(12.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  'তারিখ: $date',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ),
              TextButton.icon(
                onPressed: onPickDate,
                icon: const Icon(Icons.calendar_today),
                label: const Text('তারিখ নির্বাচন'),
              ),
            ],
          ),
          const SizedBox(height: 8),
          SizedBox(
            height: 40,
            child: ListView(
              scrollDirection: Axis.horizontal,
              children: [
                _ActionChip(
                  icon: Icons.check_circle,
                  color: Colors.green,
                  label: 'সব উপস্থিত',
                  onTap: onSelectAllPresent ?? () {},
                ),
                const SizedBox(width: 8),
                _ActionChip(
                  icon: Icons.cancel,
                  color: Colors.red,
                  label: 'সব অনুপস্থিত',
                  onTap: onSelectAllAbsent ?? () {},
                ),
                const SizedBox(width: 8),
                _ActionChip(
                  icon: Icons.access_time_filled,
                  color: Colors.orange,
                  label: 'সব দেরি',
                  onTap: onSelectAllLate ?? () {},
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionChip extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String label;
  final VoidCallback onTap;
  const _ActionChip({
    required this.icon,
    required this.color,
    required this.label,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: color.withValues(alpha: 0.5)),
        ),
        child: Row(
          children: [
            Icon(icon, color: color, size: 18),
            const SizedBox(width: 4),
            Text(label),
          ],
        ),
      ),
    );
  }
}

enum AttendanceStatus { present, absent, late }

class _StudentRow {
  final int id;
  final String name;
  final int roll;
  final String photoUrl;
  final AttendanceStatus? status;
  const _StudentRow({
    required this.id,
    required this.name,
    required this.roll,
    required this.photoUrl,
    required this.status,
  });
  _StudentRow copyWith({AttendanceStatus? status}) => _StudentRow(
    id: id,
    name: name,
    roll: roll,
    photoUrl: photoUrl,
    status: status,
  );
}

class _StudentRowWidget extends StatelessWidget {
  final _StudentRow row;
  final ValueChanged<AttendanceStatus> onChanged;
  final bool enabled;
  const _StudentRowWidget({
    required this.row,
    required this.onChanged,
    required this.enabled,
  });
  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        child: Row(
          children: [
            CircleAvatar(
              radius: 16,
              backgroundColor: Colors.grey.shade200,
              backgroundImage: row.photoUrl.isEmpty
                  ? null
                  : NetworkImage(row.photoUrl),
            ),
            const SizedBox(width: 12),
            SizedBox(
              width: 36,
              child: Text(
                '${row.roll}',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(child: Text(row.name)),
            _StatusButton(
              icon: Icons.check,
              tooltip: 'উপস্থিত',
              color: Colors.green,
              selected: row.status == AttendanceStatus.present,
              onTap: enabled
                  ? () => onChanged(AttendanceStatus.present)
                  : () {},
            ),
            const SizedBox(width: 6),
            _StatusButton(
              icon: Icons.close,
              tooltip: 'অনুপস্থিত',
              color: Colors.red,
              selected: row.status == AttendanceStatus.absent,
              onTap: enabled ? () => onChanged(AttendanceStatus.absent) : () {},
            ),
            const SizedBox(width: 6),
            _StatusButton(
              icon: Icons.access_time_filled,
              tooltip: 'দেরি',
              color: Colors.orange,
              selected: row.status == AttendanceStatus.late,
              onTap: enabled ? () => onChanged(AttendanceStatus.late) : () {},
            ),
          ],
        ),
      ),
    );
  }
}

class _CountsBar extends StatelessWidget {
  final int total;
  final int present;
  final int absent;
  final int late;
  const _CountsBar({
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

String _formatDate(DateTime d) {
  final m = d.month.toString().padLeft(2, '0');
  final day = d.day.toString().padLeft(2, '0');
  return '${d.year}-$m-$day';
}
