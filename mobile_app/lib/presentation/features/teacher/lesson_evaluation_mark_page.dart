import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';

class LessonEvaluationMarkPage extends StatefulWidget {
  final int routineEntryId;
  final String headerTitle;
  final int classId;
  final int sectionId;
  final int subjectId;
  const LessonEvaluationMarkPage({
    super.key,
    required this.routineEntryId,
    required this.headerTitle,
    required this.classId,
    required this.sectionId,
    required this.subjectId,
  });

  @override
  State<LessonEvaluationMarkPage> createState() =>
      _LessonEvaluationMarkPageState();
}

class _LessonEvaluationMarkPageState extends State<LessonEvaluationMarkPage> {
  late final Dio _dio;
  bool _loading = true;
  String? _error;
  String _date = '';
  List<_Row> _rows = const [];
  bool _readOnly = false;
  Map<String, int> _stats = const {
    'total': 0,
    'completed': 0,
    'partial': 0,
    'not_done': 0,
    'absent': 0,
  };

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _load();
  }

  Future<void> _pickDate() async {
    if (_loading) return;
    final now = DateTime.now();
    final initial = DateTime.tryParse(_date) ?? now;
    final picked = await showDatePicker(
      context: context,
      initialDate: initial.isAfter(now) ? now : initial,
      firstDate: DateTime(now.year - 1, 1, 1),
      lastDate: now,
    );
    if (picked == null) return;
    final formatted =
        '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    if (formatted == _date) return;
    setState(() {
      _date = formatted;
    });
    await _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await _dio.get(
        'teacher/lesson-evaluations/form',
        queryParameters: {
          'routine_entry_id': widget.routineEntryId,
          'date': _date.isEmpty ? null : _date,
        },
      );
      final data = (r.data as Map<String, dynamic>?) ?? {};
      _date = (data['date'] as String?) ?? '';
      final list = (data['students'] as List? ?? []).cast<Map>();
      _rows = list
          .map(
            (m) => _Row(
              id: (m['id'] as num).toInt(),
              name: (m['name'] ?? '') as String,
              roll: (m['roll'] ?? 0) as int,
              status: _parse(m['status'] as String?),
            ),
          )
          .toList();
      _readOnly = (data['read_only'] as bool?) ?? false;
      final stats = (data['stats'] as Map?) ?? {};
      _stats = {
        'total': (stats['total'] as num?)?.toInt() ?? 0,
        'completed': (stats['completed'] as num?)?.toInt() ?? 0,
        'partial': (stats['partial'] as num?)?.toInt() ?? 0,
        'not_done': (stats['not_done'] as num?)?.toInt() ?? 0,
        'absent': (stats['absent'] as num?)?.toInt() ?? 0,
      };
    } catch (e) {
      _error = 'ডাটা লোড ব্যর্থ';
    } finally {
      if (mounted)
        setState(() {
          _loading = false;
        });
    }
  }

  EvalStatus? _parse(String? s) {
    switch (s) {
      case 'completed':
        return EvalStatus.completed;
      case 'partial':
        return EvalStatus.partial;
      case 'not_done':
        return EvalStatus.notDone;
      case 'absent':
        return EvalStatus.absent;
    }
    return null;
  }

  bool get _isComplete =>
      !_readOnly && _rows.isNotEmpty && _rows.every((r) => r.status != null);

  void _markAll(EvalStatus st) {
    if (_readOnly) return;
    setState(() {
      _rows = _rows.map((e) => e.copyWith(status: st)).toList();
    });
  }

  Future<void> _submit() async {
    if (!_isComplete) return;
    try {
      final now = DateTime.now();
      final hh = now.hour.toString().padLeft(2, '0');
      final mm = now.minute.toString().padLeft(2, '0');
      // Backend expects format H:i (hours:minutes)
      final time = '$hh:$mm';
      final body = {
        'routine_entry_id': widget.routineEntryId,
        'class_id': widget.classId,
        'section_id': widget.sectionId,
        'subject_id': widget.subjectId,
        'evaluation_date': _date,
        'evaluation_time': time,
        // Some backends may expect 'time' or 'date' keys as well
        'time': time,
        // Some APIs may expect 'date' instead of 'evaluation_date'; include both.
        'date': _date,
        'student_ids': _rows.map((e) => e.id).toList(),
        'statuses': _rows.map((e) => e.status!.api).toList(),
      };
      final r = await _dio.post('teacher/lesson-evaluations', data: body);
      if (!mounted) return;
      final msg = (r.data is Map && r.data['message'] is String)
          ? r.data['message'] as String
          : 'সফলভাবে সংরক্ষিত হয়েছে';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
      await _load();
    } catch (e) {
      if (!mounted) return;
      String message = 'সংরক্ষণ ব্যর্থ';
      if (e is DioException) {
        final res = e.response;
        if (res?.data is Map) {
          final m = res!.data as Map;
          if (m['message'] is String && (m['message'] as String).isNotEmpty) {
            message = m['message'] as String;
          } else if (m['errors'] is Map) {
            final errs = m['errors'] as Map;
            final first = errs.values.cast<List?>().firstWhere(
              (v) => v != null && v.isNotEmpty,
              orElse: () => null,
            );
            if (first != null && first.isNotEmpty && first.first is String) {
              message = first.first as String;
            }
          }
        }
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.headerTitle)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text(_error!))
          : Column(
              children: [
                _TopBar(
                  date: _date,
                  readOnly: _readOnly,
                  onPickDate: _pickDate,
                  onAllCompleted: () => _markAll(EvalStatus.completed),
                  onAllPartial: () => _markAll(EvalStatus.partial),
                  onAllNotDone: () => _markAll(EvalStatus.notDone),
                  onAllAbsent: () => _markAll(EvalStatus.absent),
                ),
                const Divider(height: 1),
                _StatsRow(stats: _stats),
                Expanded(
                  child: ListView.separated(
                    padding: const EdgeInsets.all(12),
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemCount: _rows.length,
                    itemBuilder: (ctx, i) {
                      final s = _rows[i];
                      return _RowWidget(
                        row: s,
                        readOnly: _readOnly,
                        onChanged: (st) {
                          if (_readOnly) return;
                          setState(() {
                            _rows[i] = s.copyWith(status: st);
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
                        onPressed: _isComplete ? _submit : null,
                        icon: const Icon(Icons.save),
                        label: const Text('সাবমিট করুন'),
                      ),
                    ),
                  ),
                ),
              ],
            ),
    );
  }
}

class _TopBar extends StatelessWidget {
  final String date;
  final bool readOnly;
  final VoidCallback onPickDate;
  final VoidCallback onAllCompleted;
  final VoidCallback onAllPartial;
  final VoidCallback onAllNotDone;
  final VoidCallback onAllAbsent;
  const _TopBar({
    required this.date,
    required this.readOnly,
    required this.onPickDate,
    required this.onAllCompleted,
    required this.onAllPartial,
    required this.onAllNotDone,
    required this.onAllAbsent,
  });
  @override
  Widget build(BuildContext context) {
    final disabledStyle = Theme.of(
      context,
    ).textTheme.bodySmall?.copyWith(color: Colors.grey[600]);
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
                label: const Text('তারিখ'),
              ),
            ],
          ),
          if (readOnly)
            Padding(
              padding: const EdgeInsets.only(top: 6.0),
              child: Text(
                'পূর্বের রেকর্ড শুধু দেখা যাবে',
                style: disabledStyle,
              ),
            ),
          const SizedBox(height: 8),
          SizedBox(
            height: 40,
            child: ListView(
              scrollDirection: Axis.horizontal,
              children: [
                _Chip(
                  icon: Icons.check_circle,
                  color: Colors.green,
                  label: 'সব সম্পন্ন',
                  onTap: readOnly ? null : onAllCompleted,
                ),
                const SizedBox(width: 8),
                _Chip(
                  icon: Icons.timelapse,
                  color: Colors.orange,
                  label: 'সব আংশিক',
                  onTap: readOnly ? null : onAllPartial,
                ),
                const SizedBox(width: 8),
                _Chip(
                  icon: Icons.close,
                  color: Colors.red,
                  label: 'সব হয়নি',
                  onTap: readOnly ? null : onAllNotDone,
                ),
                const SizedBox(width: 8),
                _Chip(
                  icon: Icons.person_off,
                  color: Colors.grey,
                  label: 'সব অনুপস্থিত',
                  onTap: readOnly ? null : onAllAbsent,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String label;
  final VoidCallback? onTap;
  const _Chip({
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
          border: Border.all(color: color.withValues(alpha: 0.5)),
          borderRadius: BorderRadius.circular(20),
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

enum EvalStatus { completed, partial, notDone, absent }

extension on EvalStatus {
  String get api {
    switch (this) {
      case EvalStatus.completed:
        return 'completed';
      case EvalStatus.partial:
        return 'partial';
      case EvalStatus.notDone:
        return 'not_done';
      case EvalStatus.absent:
        return 'absent';
    }
  }
}

class _Row {
  final int id;
  final String name;
  final int roll;
  final EvalStatus? status;
  const _Row({
    required this.id,
    required this.name,
    required this.roll,
    required this.status,
  });
  _Row copyWith({EvalStatus? status}) =>
      _Row(id: id, name: name, roll: roll, status: status);
}

class _RowWidget extends StatelessWidget {
  final _Row row;
  final ValueChanged<EvalStatus> onChanged;
  final bool readOnly;
  const _RowWidget({
    required this.row,
    required this.onChanged,
    required this.readOnly,
  });
  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        child: Row(
          children: [
            SizedBox(
              width: 36,
              child: Text(
                '${row.roll}',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(child: Text(row.name)),
            _StatusBtn(
              icon: Icons.check,
              color: Colors.green,
              selected: row.status == EvalStatus.completed,
              onTap: readOnly ? null : () => onChanged(EvalStatus.completed),
            ),
            const SizedBox(width: 6),
            _StatusBtn(
              icon: Icons.timelapse,
              color: Colors.orange,
              selected: row.status == EvalStatus.partial,
              onTap: readOnly ? null : () => onChanged(EvalStatus.partial),
            ),
            const SizedBox(width: 6),
            _StatusBtn(
              icon: Icons.close,
              color: Colors.red,
              selected: row.status == EvalStatus.notDone,
              onTap: readOnly ? null : () => onChanged(EvalStatus.notDone),
            ),
            const SizedBox(width: 6),
            _StatusBtn(
              icon: Icons.person_off,
              color: Colors.grey,
              selected: row.status == EvalStatus.absent,
              onTap: readOnly ? null : () => onChanged(EvalStatus.absent),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatsRow extends StatelessWidget {
  final Map<String, int> stats;
  const _StatsRow({required this.stats});
  @override
  Widget build(BuildContext context) {
    Widget item(String label, String key, Color color) => Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          (stats[key] ?? 0).toString(),
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            color: color,
            fontWeight: FontWeight.bold,
          ),
        ),
        Text(label, style: Theme.of(context).textTheme.bodySmall),
      ],
    );
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          item('মোট', 'total', Colors.blue),
          item('সম্পন্ন', 'completed', Colors.green),
          item('আংশিক', 'partial', Colors.orange),
          item('হয়নি', 'not_done', Colors.red),
          item('অনুপস্থিত', 'absent', Colors.grey),
        ],
      ),
    );
  }
}

class _StatusBtn extends StatelessWidget {
  final IconData icon;
  final Color color;
  final bool selected;
  final VoidCallback? onTap;
  const _StatusBtn({
    required this.icon,
    required this.color,
    required this.selected,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) {
    return InkResponse(
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
    );
  }
}
