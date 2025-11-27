import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/dio_client.dart';
import '../../state/auth_state.dart';
import '../../../domain/auth/user_profile.dart';

class TeacherDashboardPage extends ConsumerStatefulWidget {
  const TeacherDashboardPage({super.key});

  @override
  ConsumerState<TeacherDashboardPage> createState() =>
      _TeacherDashboardPageState();
}

class _TeacherDashboardPageState extends ConsumerState<TeacherDashboardPage> {
  late final Dio _dio;
  late Future<List<dynamic>> _attendanceFuture;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _attendanceFuture = _fetchList('teacher/attendance');
  }

  Future<List<dynamic>> _fetchList(String path) async {
    final resp = await _dio.get(path);
    final data = resp.data;
    if (data is List) return data;
    if (data is Map<String, dynamic> && data['data'] is List) {
      return data['data'] as List<dynamic>;
    }
    return [];
  }

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(authProvider).asData?.value;
    final name = profile?.name ?? 'Teacher';
    final schoolName = _firstSchoolName(profile);
    final designation = 'Teacher';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Teacher Dashboard'),
        actions: [
          IconButton(
            tooltip: 'Notifications',
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Notifications coming soon')),
              );
            },
          ),
          IconButton(
            tooltip: 'Logout',
            icon: const Icon(Icons.logout, color: Colors.red),
            onPressed: () async {
              await ref.read(authProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _HeaderCard(
              name: name,
              designation: designation,
              schoolName: schoolName,
            ),
            const SizedBox(height: 16),
            _OperationsGrid(onTap: _onOperationTap),
            const SizedBox(height: 24),
            // Recent self attendance snapshot (optional)
            FutureBuilder<List<dynamic>>(
              future: _attendanceFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState != ConnectionState.done) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (snapshot.hasError) {
                  return Text('Attendance load error: ${snapshot.error}');
                }
                final items = snapshot.data ?? [];
                if (items.isEmpty) return const SizedBox.shrink();
                final item = (items.first as Map<String, dynamic>?) ?? {};
                final date = (item['date'] ?? item['checked_in_at'] ?? '')
                    .toString();
                final status = (item['status'] ?? 'present').toString();
                return Card(
                  elevation: 0,
                  color: Theme.of(context).colorScheme.surface,
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Recent Attendance',
                          style: TextStyle(fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 8),
                        Text('Date: $date'),
                        Text('Status: $status'),
                      ],
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  String? _firstSchoolName(UserProfile? profile) {
    final roles = profile?.roles ?? [];
    for (final r in roles) {
      if (r.schoolName != null && r.schoolName!.isNotEmpty) return r.schoolName;
    }
    return null;
  }

  void _onOperationTap(String key) {
    switch (key) {
      case 'self_attendance':
        context.push('/teacher/self-attendance');
        break;
      case 'students_attendance':
      case 'lesson_evaluation':
      case 'homework':
      case 'manage_leave':
      case 'teachers':
      case 'students':
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Coming soon: $key')));
        break;
    }
  }
}

class _HeaderCard extends StatelessWidget {
  final String name;
  final String designation;
  final String? schoolName;
  const _HeaderCard({
    required this.name,
    required this.designation,
    this.schoolName,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      color: Theme.of(context).colorScheme.surface,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            CircleAvatar(
              radius: 28,
              child: Text(
                name.isNotEmpty ? name[0].toUpperCase() : 'T',
                style: const TextStyle(fontSize: 24),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(designation, style: TextStyle(color: Colors.grey[700])),
                  if (schoolName != null) ...[
                    const SizedBox(height: 2),
                    Text(
                      schoolName!,
                      style: TextStyle(color: Colors.grey[700]),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OperationsGrid extends StatelessWidget {
  final void Function(String key) onTap;
  const _OperationsGrid({required this.onTap});

  @override
  Widget build(BuildContext context) {
    final items = <({String key, String title, IconData icon})>[
      (
        key: 'self_attendance',
        title: 'Self Attendance',
        icon: Icons.how_to_reg_outlined,
      ),
      (
        key: 'students_attendance',
        title: 'Students Attendance',
        icon: Icons.fact_check_outlined,
      ),
      (
        key: 'lesson_evaluation',
        title: 'Lesson Evaluation',
        icon: Icons.rate_review_outlined,
      ),
      (key: 'homework', title: 'Homework', icon: Icons.assignment_outlined),
      (
        key: 'manage_leave',
        title: 'Manage Leave',
        icon: Icons.event_busy_outlined,
      ),
      (key: 'teachers', title: 'Teachers', icon: Icons.people_alt_outlined),
      (key: 'students', title: 'Students', icon: Icons.school_outlined),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 1.25,
      children: [
        for (final item in items)
          InkWell(
            onTap: () => onTap(item.key),
            borderRadius: BorderRadius.circular(12),
            child: Card(
              elevation: 0,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      item.icon,
                      size: 36,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      item.title,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }
}
