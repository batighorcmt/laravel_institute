import 'package:flutter/material.dart';
import '../../../data/teacher/student_leave_repository.dart';
import 'student_leave_detail_page.dart';

class StudentLeaveListPage extends StatefulWidget {
  const StudentLeaveListPage({super.key});

  @override
  State<StudentLeaveListPage> createState() => _StudentLeaveListPageState();
}

class _StudentLeaveListPageState extends State<StudentLeaveListPage> {
  final _repo = StudentLeaveRepository();
  late Future<List<Map<String, dynamic>>> _future;
  String? _statusFilter;

  @override
  void initState() {
    super.initState();
    _future = _repo.listLeaves();
  }

  Future<void> _reload() async {
    setState(() {
      _future = _repo.listLeaves(status: _statusFilter);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('শিক্ষার্থীদের ছুটির আবেদন'),
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.filter_list),
            onSelected: (v) {
              _statusFilter = v == 'all' ? null : v;
              _reload();
            },
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'all', child: Text('সব')),
              PopupMenuItem(value: 'pending', child: Text('অপেক্ষমান')),
              PopupMenuItem(value: 'approved', child: Text('অনুমোদিত')),
              PopupMenuItem(value: 'rejected', child: Text('বাতিল')),
              PopupMenuItem(value: 'on_hold', child: Text('স্থগিত')),
            ],
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _reload,
        child: FutureBuilder<List<Map<String, dynamic>>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return Center(child: Text('ত্রুটি: ${snapshot.error}'));
            }
            final items = snapshot.data ?? [];
            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 120),
                  Center(child: Text('কোনো আবেদন নেই')),
                ],
              );
            }
            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              itemCount: items.length,
              separatorBuilder: (_, _) => const Divider(height: 0),
              itemBuilder: (context, index) {
                final m = items[index];
                final name = (m['student_name'] ?? '').toString();
                final className = (m['class_name'] ?? '').toString();
                final sectionName = (m['section_name'] ?? '').toString();
                final start = (m['start_date'] ?? '').toString();
                final end = (m['end_date'] ?? '').toString();
                final status = (m['status'] ?? 'pending').toString();
                return ListTile(
                  title: Text(name),
                  subtitle: Text(
                    '$className $sectionName • $start → $end',
                  ),
                  trailing: _StatusChip(status: status),
                  onTap: () async {
                    final changed = await Navigator.of(context).push<bool>(
                      MaterialPageRoute(
                        builder: (_) => StudentLeaveDetailPage(
                          leaveId: (m['id'] as num).toInt(),
                        ),
                      ),
                    );
                    if (changed == true && mounted) _reload();
                  },
                );
              },
            );
          },
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String status;
  const _StatusChip({required this.status});

  @override
  Widget build(BuildContext context) {
    Color color;
    String label;
    switch (status) {
      case 'approved':
        color = Colors.green;
        label = 'অনুমোদিত';
        break;
      case 'rejected':
        color = Colors.red;
        label = 'বাতিল';
        break;
      case 'on_hold':
        color = Colors.blueGrey;
        label = 'স্থগিত';
        break;
      default:
        color = Colors.orange;
        label = 'অপেক্ষমান';
    }
    return Chip(
      visualDensity: VisualDensity.compact,
      side: BorderSide(color: color.withValues(alpha: 0.5)),
      backgroundColor: color.withValues(alpha: 0.1),
      label: Text(label, style: TextStyle(color: color, fontSize: 12)),
    );
  }
}
