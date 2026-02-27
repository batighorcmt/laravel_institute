import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:dio/dio.dart';
import '../../../core/network/dio_client.dart';
import '../../../widgets/animated_tile.dart';
import '../../../widgets/app_snack.dart';
import 'lesson_evaluation_list_page.dart';
import 'homework_list_page.dart';
import 'teacher_leave_list_page.dart';
import 'teacher_directory_page.dart';
import 'teacher_students_list_page.dart';
import 'teacher_exams_page.dart';
import 'teacher_profile_page.dart';
import '../../state/auth_state.dart';
import '../../../domain/auth/user_profile.dart';

class TeacherDashboardPage extends ConsumerStatefulWidget {
  const TeacherDashboardPage({super.key});

  @override
  ConsumerState<TeacherDashboardPage> createState() =>
      _TeacherDashboardPageState();
}

class _TeacherDashboardPageState extends ConsumerState<TeacherDashboardPage> {
  final Dio _dio = DioClient().dio;
  Map<String, dynamic>? _todayRecord;

  @override
  void initState() {
    super.initState();
    _fetchTodayAttendance();
  }

  Future<void> _fetchTodayAttendance() async {
    try {
      final today = DateTime.now();
      final ymd =
          '${today.year}-${today.month.toString().padLeft(2, '0')}-${today.day.toString().padLeft(2, '0')}';
      final resp = await _dio.get('teacher/attendance');
      final data = resp.data;
      List list = [];
      if (data is List) list = data;
      if (data is Map && data['data'] is List) list = data['data'];
      Map<String, dynamic>? todayRec;
      for (final raw in list) {
        if (raw is Map<String, dynamic>) {
          final dateField =
              (raw['date'] ?? raw['attendance_date'] ?? raw['day'] ?? '')
                  .toString();
          if (dateField.startsWith(ymd)) {
            todayRec = raw;
            break;
          }
        }
      }
      if (mounted) setState(() => _todayRecord = todayRec);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(authProvider).asData?.value;
    final name = profile?.name ?? 'Teacher';
    final photoUrl = profile?.photoUrl;
    final schoolName = _firstSchoolName(profile);
    final designation = profile?.teacherDesignation ?? 'Teacher';
    final mobile = profile?.mobile;

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
      body: RefreshIndicator(
        onRefresh: _fetchTodayAttendance,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _HeaderCard(
                name: name,
                designation: designation,
                schoolName: schoolName,
                photoUrl: photoUrl,
                mobile: mobile,
                todayRecord: _todayRecord,
                onProfileTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                        builder: (_) => const TeacherProfilePage()),
                  );
                },
              ),
              const SizedBox(height: 16),
              _OperationsGrid(onTap: _onOperationTap),
              const SizedBox(height: 24),
            ],
          ),
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
      case 'exams':
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => const TeacherExamsPage()),
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
  final String? mobile;
  final Map<String, dynamic>? todayRecord;
  final VoidCallback onProfileTap;

  const _HeaderCard({
    required this.name,
    required this.designation,
    this.schoolName,
    this.photoUrl,
    this.mobile,
    this.todayRecord,
    required this.onProfileTap,
  });

  String _statusLabel(String? status) {
    switch ((status ?? '').toLowerCase()) {
      case 'present':
        return 'উপস্থিত';
      case 'late':
        return 'বিলম্ব';
      case 'absent':
        return 'অনুপস্থিত';
      default:
        return status ?? 'অজানা';
    }
  }

  Color _statusColor(String? status) {
    switch ((status ?? '').toLowerCase()) {
      case 'present':
        return Colors.green;
      case 'late':
        return Colors.orange;
      case 'absent':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final checkIn = todayRecord?['check_in_time']?.toString() ?? '-';
    final checkOut = todayRecord?['check_out_time']?.toString() ?? '-';
    final status = todayRecord?['status']?.toString();
    final hasRecord = todayRecord != null;

    return GestureDetector(
      onTap: onProfileTap,
      child: Card(
        elevation: 1,
        color: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: const Color(0xFFE6F5EE),
                    backgroundImage:
                        (photoUrl != null && photoUrl!.isNotEmpty)
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
                        const SizedBox(height: 2),
                        Text(
                          '$designation${(mobile != null && mobile!.isNotEmpty) ? " ($mobile)" : ""}',
                          style: const TextStyle(color: Color(0xFF4B5563)),
                        ),
                        if (schoolName != null) ...[
                          const SizedBox(height: 2),
                          Text(
                            schoolName!,
                            style: const TextStyle(
                                color: Color(0xFF4B5563), fontSize: 12),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right, color: Colors.grey),
                ],
              ),
              if (hasRecord) ...[
                const Divider(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _AttStat(
                        label: 'আজকের স্ট্যাটাস',
                        value: _statusLabel(status),
                        color: _statusColor(status)),
                    _AttStat(label: 'চেক ইন', value: checkIn,
                        color: Colors.green),
                    _AttStat(label: 'চেক আউট', value: checkOut,
                        color: Colors.red),
                  ],
                ),
              ] else ...[
                const Divider(height: 20),
                const Text(
                  'আজকের হাজিরা দেওয়া হয়নি',
                  style: TextStyle(color: Colors.grey, fontSize: 13),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _AttStat extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  const _AttStat({required this.label, required this.value, required this.color});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(label,
            style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280))),
        const SizedBox(height: 2),
        Text(value,
            style: TextStyle(
                fontSize: 13, fontWeight: FontWeight.bold, color: color)),
      ],
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
        'exams',
        'Exams',
        Icons.description_outlined,
        Color(0xFFEFF6FF),
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
    return GridView.count(
      crossAxisCount: 3,
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
            titleFontSize: 14,
            onTap: () => onTap(item.key),
          ),
      ],
    );
  }
}
