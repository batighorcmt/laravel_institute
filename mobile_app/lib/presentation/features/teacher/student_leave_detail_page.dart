import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../data/teacher/student_leave_repository.dart';

class StudentLeaveDetailPage extends StatefulWidget {
  final int leaveId;
  final String basePath;
  const StudentLeaveDetailPage({
    super.key,
    required this.leaveId,
    this.basePath = 'teacher/student-leaves',
  });

  @override
  State<StudentLeaveDetailPage> createState() =>
      _StudentLeaveDetailPageState();
}

class _StudentLeaveDetailPageState extends State<StudentLeaveDetailPage> {
  late final _repo = StudentLeaveRepository(basePath: widget.basePath);
  late Future<Map<String, dynamic>> _future;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = _repo.getLeave(widget.leaveId);
  }

  Future<void> _act(String action) async {
    String? note;
    if (action == 'rejected' || action == 'on_hold') {
      note = await _askNote(action);
      if (note == null) return; // cancelled
    }
    setState(() => _busy = true);
    try {
      await _repo.review(widget.leaveId, action: action, note: note);
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('ত্রুটি: $e')));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<String?> _askNote(String action) async {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(action == 'rejected' ? 'বাতিলের কারণ' : 'স্থগিতের কারণ'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: const InputDecoration(hintText: 'মন্তব্য (ঐচ্ছিক)'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, null),
            child: const Text('বাতিল'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, controller.text),
            child: const Text('নিশ্চিত করুন'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('আবেদনের বিস্তারিত')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('ত্রুটি: ${snapshot.error}'));
          }
          final m = snapshot.data ?? {};
          final status = (m['status'] ?? 'pending').toString();
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _row('শিক্ষার্থীর নাম', m['student_name']),
              _row('রোল', m['roll_no']),
              _row('শ্রেণি', m['class_name']),
              _row('শাখা', m['section_name']),
              _phoneRow('অভিভাবকের মোবাইল', m['guardian_phone']),
              const Divider(height: 32),
              _row('শিরোনাম', m['title']),
              _row('ধরন', m['type']),
              _row('শুরু তারিখ', m['start_date']),
              _row('শেষ তারিখ', m['end_date']),
              _row('মোট দিন', m['total_days']),
              const SizedBox(height: 8),
              const Text(
                'আবেদনের বিষয়বস্তু',
                style: TextStyle(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 4),
              Text((m['reason'] ?? '').toString()),
              const Divider(height: 32),
              _row('অবস্থা', _statusLabel(status)),
              if (m['reviewed_by_name'] != null)
                _row('রিভিউ করেছেন', m['reviewed_by_name']),
              if (m['review_note'] != null &&
                  (m['review_note'] as String).isNotEmpty)
                _row('মন্তব্য', m['review_note']),
              const SizedBox(height: 24),
              if (status == 'pending' || status == 'on_hold') ...[
                if (_busy)
                  const Center(child: CircularProgressIndicator())
                else
                  Row(
                    children: [
                      Expanded(
                        child: FilledButton.icon(
                          onPressed: () => _act('approved'),
                          icon: const Icon(Icons.check),
                          label: const Text('অনুমোদন'),
                          style: FilledButton.styleFrom(
                            backgroundColor: Colors.green,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _act('on_hold'),
                          icon: const Icon(Icons.pause_circle_outline),
                          label: const Text('স্থগিত'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _act('rejected'),
                          icon: const Icon(Icons.close),
                          label: const Text('বাতিল'),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.red,
                          ),
                        ),
                      ),
                    ],
                  ),
              ],
            ],
          );
        },
      ),
    );
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'approved':
        return 'অনুমোদিত';
      case 'rejected':
        return 'বাতিল';
      case 'on_hold':
        return 'স্থগিত';
      default:
        return 'অপেক্ষমান';
    }
  }

  Widget _phoneRow(String label, dynamic value) {
    final phone = (value ?? '').toString().trim();
    if (phone.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(
              label,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                color: Colors.grey,
              ),
            ),
          ),
          Expanded(
            child: InkWell(
              onTap: () async {
                final uri = Uri(scheme: 'tel', path: phone);
                if (await canLaunchUrl(uri)) {
                  await launchUrl(uri);
                }
              },
              child: Row(
                children: [
                  Text(
                    phone,
                    style: const TextStyle(
                      color: Colors.blue,
                      decoration: TextDecoration.underline,
                    ),
                  ),
                  const SizedBox(width: 6),
                  const Icon(Icons.call, size: 16, color: Colors.blue),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _row(String label, dynamic value) {
    if (value == null || value.toString().isEmpty) {
      return const SizedBox.shrink();
    }
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(
              label,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                color: Colors.grey,
              ),
            ),
          ),
          Expanded(child: Text(value.toString())),
        ],
      ),
    );
  }
}
