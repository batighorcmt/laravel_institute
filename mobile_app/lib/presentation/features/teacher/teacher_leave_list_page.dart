import 'package:flutter/material.dart';
import '../../../data/teacher/teacher_leave_repository.dart';
import 'teacher_leave_apply_page.dart';

class TeacherLeaveListPage extends StatefulWidget {
  const TeacherLeaveListPage({super.key});

  @override
  State<TeacherLeaveListPage> createState() => _TeacherLeaveListPageState();
}

class _TeacherLeaveListPageState extends State<TeacherLeaveListPage> {
  final _repo = TeacherLeaveRepository();
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
        title: const Text('My Leaves'),
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.filter_list),
            onSelected: (v) {
              _statusFilter = v == 'all' ? null : v;
              _reload();
            },
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'all', child: Text('All')),
              PopupMenuItem(value: 'pending', child: Text('Pending')),
              PopupMenuItem(value: 'approved', child: Text('Approved')),
              PopupMenuItem(value: 'rejected', child: Text('Rejected')),
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
              return Center(child: Text('Error: ${snapshot.error}'));
            }
            final items = snapshot.data ?? [];
            if (items.isEmpty) {
              return const Center(child: Text('No leave applications found'));
            }
            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              itemCount: items.length,
              separatorBuilder: (_, __) => const Divider(height: 0),
              itemBuilder: (context, index) {
                final m = items[index];
                final start = (m['start_date'] ?? '').toString();
                final end = (m['end_date'] ?? '').toString();
                final type = (m['type'] ?? '—').toString();
                final status = (m['status'] ?? 'pending').toString();
                final reason = (m['reason'] ?? '').toString();
                return ListTile(
                  title: Text('$start → $end'),
                  subtitle: Text(reason.isEmpty ? type : '$type · $reason'),
                  trailing: _StatusChip(status: status),
                );
              },
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final changed = await Navigator.of(context).push<bool>(
            MaterialPageRoute(builder: (_) => const TeacherLeaveApplyPage()),
          );
          if (changed == true && mounted) {
            _reload();
          }
        },
        icon: const Icon(Icons.add),
        label: const Text('Apply'),
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
    switch (status) {
      case 'approved':
        color = Colors.green;
        break;
      case 'rejected':
        color = Colors.red;
        break;
      default:
        color = Colors.orange;
    }
    return Chip(
      visualDensity: VisualDensity.compact,
      side: BorderSide(color: color.withOpacity(0.5)),
      label: Text(status),
    );
  }
}
