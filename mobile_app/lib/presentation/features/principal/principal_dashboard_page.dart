import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../state/auth_state.dart';
import '../teacher/lesson_evaluation_list_page.dart';
import '../teacher/homework_list_page.dart';
import '../teacher/teacher_leave_list_page.dart';
import '../teacher/teacher_directory_page.dart';
import '../teacher/teacher_students_list_page.dart';
import '../teacher/teacher_profile_page.dart';
import '../../../core/network/dio_client.dart';
import '../../../data/auth/auth_repository.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../widgets/app_snack.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../widgets/animated_tile.dart';
import '../../../widgets/rive_icon_registry.dart';
import 'principal_attendance_details_page.dart';
import 'principal_reports_page.dart';
import 'notice_list_page.dart';
// theme toggle removed for principal toolbar

class PrincipalDashboardPage extends ConsumerStatefulWidget {
  const PrincipalDashboardPage({super.key});

  @override
  ConsumerState<PrincipalDashboardPage> createState() =>
      _PrincipalDashboardPageState();
}

class _PrincipalDashboardPageState
    extends ConsumerState<PrincipalDashboardPage> {
  late final Dio _dio;
  String? _overridePhoto;
  String? _overrideDesignation;
  bool _fetchedExtra = false;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
  }

  Future<Map<String, dynamic>> _fetchJson(String path) async {
    final resp = await _dio.get(path);
    return resp.data as Map<String, dynamic>;
  }

  Future<void> _ensureOverrideSchoolFromProfile(dynamic profile) async {
    try {
      if (profile == null) return;
      int? sid;
      try {
        for (final r in profile.roles) {
          if (r.schoolId != null) {
            sid = r.schoolId as int?;
            break;
          }
        }
      } catch (_) {}
      if (sid == null) return;
      final prefs = await SharedPreferences.getInstance();
      final current = prefs.getInt('override_school_id');
      if (current != sid) {
        await prefs.setInt('override_school_id', sid);
        // optional: quick notify
        showAppSnack(
          context,
          message: 'Using school id $sid for teacher flows',
        );
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    // Helper to determine whether the current profile should be allowed
    // to access teacher flows. Principals also perform teacher actions,
    // so treat roles containing 'teacher' or 'principal' as allowed.
    bool hasTeachingRole(profile) {
      if (profile == null) return false;
      try {
        return profile.roles.any((r) {
          final role = (r.role ?? '').toString().toLowerCase();
          return role.contains('teacher') ||
              role.contains('principal') ||
              role.contains('head');
        });
      } catch (_) {
        return false;
      }
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Principal Dashboard'),
        actions: [
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
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Header showing current user's name, designation, photo and school
          Builder(
            builder: (ctx) {
              final profile = ref.watch(authProvider).asData?.value;
              final name = profile?.name ?? '';
              var photo = _overridePhoto ?? profile?.photoUrl;
              var designation =
                  _overrideDesignation ?? profile?.teacherDesignation ?? '';
              // If not yet fetched, try to fetch raw /me for richer teacher fields once.
              if (!_fetchedExtra) {
                _fetchedExtra = true;
                () async {
                  try {
                    final raw = await AuthRepository().me();
                    if (raw is Map) {
                      final t = raw['teacher'];
                      if (t is Map) {
                        final tp = t['photo_url'] ?? t['photo'];
                        final td = t['designation'];
                        if ((photo == null || (photo?.isEmpty ?? true)) &&
                            (tp?.toString().isNotEmpty == true)) {
                          setState(() => _overridePhoto = tp.toString());
                        }
                        if ((designation == null ||
                                (designation?.isEmpty ?? true)) &&
                            (td?.toString().isNotEmpty == true)) {
                          setState(() => _overrideDesignation = td.toString());
                        }
                      } else {
                        final rawRoles = raw['roles'];
                        if (rawRoles is List) {
                          for (final e in rawRoles) {
                            if (e is Map) {
                              final tp = e['photo_url'] ?? e['photo'];
                              final td =
                                  e['designation'] ??
                                  e['position'] ??
                                  e['title'];
                              if ((photo == null || (photo?.isEmpty ?? true)) &&
                                  (tp?.toString().isNotEmpty == true)) {
                                setState(() => _overridePhoto = tp.toString());
                                break;
                              }
                              if ((designation == null ||
                                      (designation?.isEmpty ?? true)) &&
                                  (td?.toString().isNotEmpty == true)) {
                                setState(
                                  () => _overrideDesignation = td.toString(),
                                );
                              }
                            }
                          }
                        }
                      }
                    }
                  } catch (_) {}
                }();
              }
              String? schoolName;
              if (profile != null) {
                for (final r in profile.roles) {
                  if (r.schoolName != null && r.schoolName!.isNotEmpty) {
                    schoolName = r.schoolName;
                    break;
                  }
                }
              }
              return Card(
                elevation: 1,
                clipBehavior: Clip.antiAlias,
                child: InkWell(
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => const TeacherProfilePage(),
                      ),
                    );
                  },
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 28,
                          backgroundColor: const Color(0xFFE6F5EE),
                          backgroundImage: (photo != null && photo.isNotEmpty)
                              ? NetworkImage(photo)
                              : null,
                          child: (photo == null || photo.isEmpty)
                              ? Text(
                                  name.isNotEmpty ? name[0].toUpperCase() : 'U',
                                  style: const TextStyle(
                                    fontSize: 24,
                                    color: Color(0xFF1A1D1F),
                                  ),
                                )
                              : null,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                name,
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 4),
                              if (designation.isNotEmpty)
                                Text(
                                  designation,
                                  style: const TextStyle(
                                    color: Color(0xFF4B5563),
                                  ),
                                ),
                              if (schoolName != null) ...[
                                const SizedBox(height: 2),
                                Text(
                                  schoolName,
                                  style: const TextStyle(
                                    color: Color(0xFF4B5563),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        const Icon(Icons.chevron_right, color: Colors.grey),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),

          const SizedBox(height: 12),

          GridView.count(
            crossAxisCount: 3,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: 4,
            mainAxisSpacing: 4,
            childAspectRatio: 1.15,
            children: [
              AnimatedTile(
                title: 'Self Attendance',
                titleFontSize: 9,
                icon: Icons.how_to_reg_outlined,
                background: const Color(0xFFF0F9FF),
                onTap: () async {
                  final profile = ref.read(authProvider).asData?.value;
                  await _ensureOverrideSchoolFromProfile(profile);
                  if (!mounted) return;
                  if (!hasTeachingRole(profile)) {
                    showAppSnack(
                      context,
                      message:
                          'No teacher role in profile. Refresh or contact admin.',
                    );
                    return;
                  }
                  context.push('/teacher/self-attendance');
                },
              ),
              AnimatedTile(
                title: 'Students Attendance',
                titleFontSize: 9,
                icon: Icons.fact_check_outlined,
                background: const Color(0xFFFFF7ED),
                onTap: () async {
                  final profile = ref.read(authProvider).asData?.value;
                  await _ensureOverrideSchoolFromProfile(profile);
                  if (!mounted) return;
                  if (!hasTeachingRole(profile)) {
                    showAppSnack(
                      context,
                      message:
                          'No teacher role in profile. Refresh or contact admin.',
                    );
                    return;
                  }
                  context.push('/teacher/students-attendance');
                },
              ),
              AnimatedTile(
                title: 'Lesson Evaluation',
                titleFontSize: 9,
                icon: Icons.rate_review_outlined,
                background: const Color(0xFFF5F3FF),
                onTap: () async {
                  final profile = ref.read(authProvider).asData?.value;
                  await _ensureOverrideSchoolFromProfile(profile);
                  if (!mounted) return;
                  if (!hasTeachingRole(profile)) {
                    showAppSnack(
                      context,
                      message:
                          'No teacher role in profile. Refresh or contact admin.',
                    );
                    return;
                  }
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const LessonEvaluationListPage(),
                    ),
                  );
                },
              ),
              AnimatedTile(
                title: 'Homework',
                titleFontSize: 9,
                icon: Icons.assignment_outlined,
                background: const Color(0xFFF0FDF4),
                onTap: () async {
                  final profile = ref.read(authProvider).asData?.value;
                  await _ensureOverrideSchoolFromProfile(profile);
                  if (!mounted) return;
                  if (!hasTeachingRole(profile)) {
                    showAppSnack(
                      context,
                      message:
                          'No teacher role in profile. Refresh or contact admin.',
                    );
                    return;
                  }
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const TeacherHomeworkListPage(),
                    ),
                  );
                },
              ),
              // Attendance Report tile removed from grid (kept at page bottom)
              AnimatedTile(
                title: 'Exams',
                titleFontSize: 9,
                icon: Icons.assessment_outlined,
                background: const Color(0xFFF7FBFF),
                onTap: () async {
                  await showAppSnack(
                    context,
                    message: 'Scroll for Exam Summary',
                  );
                },
              ),
              AnimatedTile(
                title: 'Manage Leave',
                titleFontSize: 9,
                icon: Icons.event_busy_outlined,
                background: const Color(0xFFFFF1F2),
                onTap: () async {
                  final profile = ref.read(authProvider).asData?.value;
                  await _ensureOverrideSchoolFromProfile(profile);
                  if (!mounted) return;
                  if (!hasTeachingRole(profile)) {
                    showAppSnack(
                      context,
                      message:
                          'No teacher role in profile. Refresh or contact admin.',
                    );
                    return;
                  }
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const TeacherLeaveListPage(),
                    ),
                  );
                },
              ),
              AnimatedTile(
                title: 'Teachers',
                titleFontSize: 9,
                icon: Icons.people_alt_outlined,
                background: const Color(0xFFECFEFF),
                onTap: () async {
                  final profile = ref.read(authProvider).asData?.value;
                  await _ensureOverrideSchoolFromProfile(profile);
                  if (!mounted) return;
                  if (!hasTeachingRole(profile)) {
                    showAppSnack(
                      context,
                      message:
                          'No teacher role in profile. Refresh or contact admin.',
                    );
                    return;
                  }
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const TeacherDirectoryPage(),
                    ),
                  );
                },
              ),
              AnimatedTile(
                title: 'Students',
                titleFontSize: 9,
                icon: Icons.school_outlined,
                background: const Color(0xFFFFFBEB),
                onTap: () async {
                  final profile = ref.read(authProvider).asData?.value;
                  await _ensureOverrideSchoolFromProfile(profile);
                  if (!mounted) return;
                  if (!hasTeachingRole(profile)) {
                    showAppSnack(
                      context,
                      message:
                          'No teacher role in profile. Refresh or contact admin.',
                    );
                    return;
                  }
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const TeacherStudentsListPage(),
                    ),
                  );
                },
              ),
              // Notice tile inserted after Students
              AnimatedTile(
                title: 'Notice',
                titleFontSize: 9,
                icon: Icons.campaign_outlined,
                background: const Color(0xFFFFF3E0),
                onTap: () async {
                  final profile = ref.read(authProvider).asData?.value;
                  await _ensureOverrideSchoolFromProfile(profile);
                  if (!mounted) return;
                  if (!hasTeachingRole(profile)) {
                    showAppSnack(
                      context,
                      message:
                          'No teacher role in profile. Refresh or contact admin.',
                    );
                    return;
                  }
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const NoticeListPage()),
                  );
                },
              ),
            ],
          ),

          const SizedBox(height: 12),

          // Attendance Report card restored at page bottom
          Card(
            elevation: 1,
            child: ListTile(
              leading: const Icon(
                Icons.bar_chart_outlined,
                color: Colors.green,
              ),
              title: const Text('Attendance Report'),
              subtitle: const Text(
                'Daily attendance summaries and class-wise reports',
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () {
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => const PrincipalAttendanceDetailsPage(),
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 12),

          // Reports card (opens a page containing multiple reports)
          Card(
            elevation: 1,
            child: InkWell(
              onTap: () {
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => const PrincipalReportsPage(),
                  ),
                );
              },
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    const Icon(Icons.folder_open_outlined, size: 28),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: const [
                          Text(
                            'Reports',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(height: 6),
                          Text('View attendance and evaluation reports'),
                        ],
                      ),
                    ),
                    const Icon(Icons.chevron_right),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildKeyValueList(Map<String, dynamic> data) {
    if (data.isEmpty) {
      return const Text('No data');
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: data.entries
          .map(
            (e) => Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Text('${e.key}: ${e.value}'),
            ),
          )
          .toList(),
    );
  }
}
