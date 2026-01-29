import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../widgets/animated_tile.dart';
import '../../../widgets/app_snack.dart';
import 'lesson_evaluation_list_page.dart';
import 'homework_list_page.dart';
import 'teacher_leave_list_page.dart';
import 'teacher_directory_page.dart';
import 'teacher_students_list_page.dart';
import '../../state/auth_state.dart';
import '../../../domain/auth/user_profile.dart';

class TeacherDashboardPage extends ConsumerStatefulWidget {
  const TeacherDashboardPage({super.key});

  @override
  ConsumerState<TeacherDashboardPage> createState() =>
      _TeacherDashboardPageState();
}

class _TeacherDashboardPageState extends ConsumerState<TeacherDashboardPage> {
  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(authProvider).asData?.value;
    final name = profile?.name ?? 'Teacher';
    final photoUrl = profile?.photoUrl;
    final schoolName = _firstSchoolName(profile);
    final designation = profile?.teacherDesignation ?? 'Teacher';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Teacher Dashboard'),
        actions: [
          IconButton(
            tooltip: 'Notifications',
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () async {
              await showAppSnack(context, message: 'Notifications coming soon');
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
              photoUrl: photoUrl,
            ),
            const SizedBox(height: 16),
            _OperationsGrid(onTap: _onOperationTap),
            const SizedBox(height: 24),
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
        context.push('/teacher/students-attendance');
        break;
      case 'lesson_evaluation':
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => const LessonEvaluationListPage()),
        );
        break;
      case 'homework':
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => const TeacherHomeworkListPage()),
        );
        break;
      case 'manage_leave':
        Navigator.of(
          context,
        ).push(MaterialPageRoute(builder: (_) => const TeacherLeaveListPage()));
        break;
      case 'teachers':
        Navigator.of(
          context,
        ).push(MaterialPageRoute(builder: (_) => const TeacherDirectoryPage()));
        break;
      case 'students':
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => const TeacherStudentsListPage()),
        );
        break;
    }
  }
}

class _HeaderCard extends StatelessWidget {
  final String name;
  final String designation;
  final String? schoolName;
  final String? photoUrl;
  const _HeaderCard({
    required this.name,
    required this.designation,
    this.schoolName,
    this.photoUrl,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 1,
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            CircleAvatar(
              radius: 28,
              backgroundColor: const Color(0xFFE6F5EE),
              backgroundImage: (photoUrl != null && photoUrl!.isNotEmpty)
                  ? NetworkImage(photoUrl!)
                  : null,
              child: (photoUrl == null || photoUrl!.isEmpty)
                  ? Text(
                      name.isNotEmpty ? name[0].toUpperCase() : 'T',
                      style: const TextStyle(
                        fontSize: 24,
                        color: Color(0xFF1A1D1F),
                      ),
                    )
                  : null,
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
                      color: Color(0xFF1A1D1F),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    designation,
                    style: const TextStyle(color: Color(0xFF4B5563)),
                  ),
                  if (schoolName != null) ...[
                    const SizedBox(height: 2),
                    Text(
                      schoolName!,
                      style: const TextStyle(color: Color(0xFF4B5563)),
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

class _OpItem {
  final String key;
  final String title;
  final IconData icon;
  final Color background;
  const _OpItem(this.key, this.title, this.icon, this.background);
}

class _OperationsGrid extends StatelessWidget {
  final void Function(String key) onTap;
  const _OperationsGrid({required this.onTap});

  @override
  Widget build(BuildContext context) {
    final items = <_OpItem>[
      const _OpItem(
        'self_attendance',
        'Self Attendance',
        Icons.how_to_reg_outlined,
        Color(0xFFF0F9FF),
      ),
      const _OpItem(
        'students_attendance',
        'Students Attendance',
        Icons.fact_check_outlined,
        Color(0xFFFFF7ED),
      ),
      const _OpItem(
        'lesson_evaluation',
        'Lesson Evaluation',
        Icons.rate_review_outlined,
        Color(0xFFF5F3FF),
      ),
      const _OpItem(
        'homework',
        'Homework',
        Icons.assignment_outlined,
        Color(0xFFF0FDF4),
      ),
      const _OpItem(
        'manage_leave',
        'Manage Leave',
        Icons.event_busy_outlined,
        Color(0xFFFFF1F2),
      ),
      const _OpItem(
        'teachers',
        'Teachers',
        Icons.people_alt_outlined,
        Color(0xFFECFEFF),
      ),
      const _OpItem(
        'students',
        'Students',
        Icons.school_outlined,
        Color(0xFFFFFBEB),
      ),
    ];
    return LayoutBuilder(
      builder: (context, constraints) {
        final width = constraints.maxWidth;
        final proposed = (width / 140).floor();
        final cols = proposed.clamp(2, 4);
        return GridView.count(
          crossAxisCount: cols,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
          childAspectRatio: 1.0,
          children: [
            for (final item in items)
              AnimatedTile(
                title: item.title,
                icon: item.icon,
                background: item.background,
                onTap: () => onTap(item.key),
              ),
          ],
        );
      },
    );
  }
}
