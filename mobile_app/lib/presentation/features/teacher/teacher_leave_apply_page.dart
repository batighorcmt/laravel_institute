import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../data/teacher/teacher_leave_repository.dart';

class TeacherLeaveApplyPage extends StatefulWidget {
  const TeacherLeaveApplyPage({super.key});

  @override
  State<TeacherLeaveApplyPage> createState() => _TeacherLeaveApplyPageState();
}

class _TeacherLeaveApplyPageState extends State<TeacherLeaveApplyPage> {
  final _repo = TeacherLeaveRepository();
  final _reasonCtl = TextEditingController();
  String? _type;
  DateTime? _start;
  DateTime? _end;
  bool _saving = false;

  final _types = const ['sick', 'casual', 'emergency', 'other'];
  final _fmt = DateFormat('yyyy-MM-dd');

  @override
  void dispose() {
    _reasonCtl.dispose();
    super.dispose();
  }

  Future<void> _pickStart() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _start ?? now,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 1),
    );
    if (picked != null) {
      setState(() {
        _start = picked;
        if (_end != null && _end!.isBefore(_start!)) {
          _end = _start;
        }
      });
    }
  }

  Future<void> _pickEnd() async {
    final base = _start ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _end ?? base,
      firstDate: base,
      lastDate: DateTime(base.year + 1),
    );
    if (picked != null) {
      setState(() => _end = picked);
    }
  }

  Future<void> _submit() async {
    final reason = _reasonCtl.text.trim();
    if (_start == null || _end == null || reason.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select dates and enter reason')),
      );
      return;
    }
    setState(() => _saving = true);
    try {
      await _repo.applyLeave(
        startDate: _fmt.format(_start!),
        endDate: _fmt.format(_end!),
        reason: reason,
        type: _type,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Leave request submitted')));
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Error: $e')));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Apply for Leave')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: _DateField(
                    label: 'Start Date',
                    value: _start == null ? '' : _fmt.format(_start!),
                    onTap: _pickStart,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _DateField(
                    label: 'End Date',
                    value: _end == null ? '' : _fmt.format(_end!),
                    onTap: _pickEnd,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: _type,
              items: _types
                  .map((t) => DropdownMenuItem(value: t, child: Text(t)))
                  .toList(),
              onChanged: (v) => setState(() => _type = v),
              decoration: const InputDecoration(
                labelText: 'Type (optional)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _reasonCtl,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'Reason',
                border: OutlineInputBorder(),
              ),
            ),
            const Spacer(),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _saving ? null : _submit,
                icon: _saving
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.send),
                label: const Text('Submit'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  final String label;
  final String value;
  final VoidCallback onTap;
  const _DateField({
    required this.label,
    required this.value,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
        ),
        child: Text(value.isEmpty ? 'Select' : value),
      ),
    );
  }
}
