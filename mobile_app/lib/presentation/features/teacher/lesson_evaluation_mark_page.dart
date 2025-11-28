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
        'teacher/lesson-evaluations/form',
        queryParameters: {'routine_entry_id': widget.routineEntryId},
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
      _rows.isNotEmpty && _rows.every((r) => r.status != null);

  void _markAll(EvalStatus st) {
    setState(() {
      _rows = _rows.map((e) => e.copyWith(status: st)).toList();
    });
  }

  Future<void> _submit() async {
    if (!_isComplete) return;
    try {
      final body = {
        'routine_entry_id': widget.routineEntryId,
        'class_id': widget.classId,
        'section_id': widget.sectionId,
        'subject_id': widget.subjectId,
        'evaluation_date': _date,
        'student_ids': _rows.map((e) => e.id).toList(),
        'statuses': _rows.map((e) => e.status!.api).toList(),
      };
      final r = await _dio.post('teacher/lesson-evaluations', data: body);
      if (!mounted) return;
      final msg = (r.data is Map && r.data['message'] is String)
          ? r.data['message'] as String
          : 'সফলভাবে সংরক্ষিত হয়েছে';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
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
      appBar: AppBar(title: Text(widget.headerTitle)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text(_error!))
          : Column(
              children: [
                _Header(
                  date: _date,
                  onAllCompleted: () => _markAll(EvalStatus.completed),
                  onAllPartial: () => _markAll(EvalStatus.partial),
                  onAllNotDone: () => _markAll(EvalStatus.notDone),
                  onAllAbsent: () => _markAll(EvalStatus.absent),
                ),
                const Divider(height: 1),
                Expanded(
                  child: ListView.separated(
                    padding: const EdgeInsets.all(12),
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemCount: _rows.length,
                    itemBuilder: (ctx, i) {
                      final s = _rows[i];
                      return _RowWidget(
                        row: s,
                        onChanged: (st) {
                          setState(() {
                            _rows[i] = s.copyWith(status: st);
                          });
                        },
                      );
                    },
                  ),
                ),
                _StatsBar(
                  completed: _rows
                      .where((r) => r.status == EvalStatus.completed)
                      .length,
                  partial: _rows
                      .where((r) => r.status == EvalStatus.partial)
                      .length,
                  notDone: _rows
                      .where((r) => r.status == EvalStatus.notDone)
                      .length,
                  absent: _rows
                      .where((r) => r.status == EvalStatus.absent)
                      .length,
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

class _Header extends StatelessWidget {
  final String date;
  final VoidCallback onAllCompleted;
  final VoidCallback onAllPartial;
  final VoidCallback onAllNotDone;
  final VoidCallback onAllAbsent;
  const _Header({
    required this.date,
    required this.onAllCompleted,
    required this.onAllPartial,
    required this.onAllNotDone,
    required this.onAllAbsent,
  });
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(12.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('তারিখ: $date'),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _Chip(
                icon: Icons.check_circle,
                color: Colors.green,
                label: 'সব সম্পন্ন',
                onTap: onAllCompleted,
              ),
              _Chip(
                icon: Icons.timelapse,
                color: Colors.orange,
                label: 'সব আংশিক',
                onTap: onAllPartial,
              ),
              _Chip(
                icon: Icons.close,
                color: Colors.red,
                label: 'সব হয়নি',
                onTap: onAllNotDone,
              ),
              _Chip(
                icon: Icons.person_off,
                color: Colors.grey,
                label: 'সব অনুপস্থিত',
                onTap: onAllAbsent,
              ),
            ],
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
  final VoidCallback onTap;
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
  const _RowWidget({required this.row, required this.onChanged});
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
              onTap: () => onChanged(EvalStatus.completed),
            ),
            const SizedBox(width: 6),
            _StatusBtn(
              icon: Icons.timelapse,
              color: Colors.orange,
              selected: row.status == EvalStatus.partial,
              onTap: () => onChanged(EvalStatus.partial),
            ),
            const SizedBox(width: 6),
            _StatusBtn(
              icon: Icons.close,
              color: Colors.red,
              selected: row.status == EvalStatus.notDone,
              onTap: () => onChanged(EvalStatus.notDone),
            ),
            const SizedBox(width: 6),
            _StatusBtn(
              icon: Icons.person_off,
              color: Colors.grey,
              selected: row.status == EvalStatus.absent,
              onTap: () => onChanged(EvalStatus.absent),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatsBar extends StatelessWidget {
  final int completed;
  final int partial;
  final int notDone;
  final int absent;
  const _StatsBar({
    required this.completed,
    required this.partial,
    required this.notDone,
    required this.absent,
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
      padding: const EdgeInsets.fromLTRB(12, 6, 12, 6),
      child: SizedBox(
        height: 34,
        child: ListView(
          scrollDirection: Axis.horizontal,
          children: [
            chip(Colors.green, 'সম্পন্ন', '$completed'),
            const SizedBox(width: 8),
            chip(Colors.orange, 'আংশিক', '$partial'),
            const SizedBox(width: 8),
            chip(Colors.red, 'হয়নি', '$notDone'),
            const SizedBox(width: 8),
            chip(Colors.grey, 'অনুপস্থিত', '$absent'),
          ],
        ),
      ),
    );
  }
}

class _StatusBtn extends StatelessWidget {
  final IconData icon;
  final Color color;
  final bool selected;
  final VoidCallback onTap;
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
