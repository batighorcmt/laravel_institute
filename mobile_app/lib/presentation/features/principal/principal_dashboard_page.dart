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
import '../teacher/teacher_exams_page.dart';
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
  Map<String, dynamic>? _summaryData;
  bool _summaryLoading = false;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _fetchSummary();
  }
  
  Future<void> _fetchSummary() async {
    if (_summaryLoading) return;
    setState(() => _summaryLoading = true);
    try {
      final res = await _dio.get('principal/reports/attendance-summary');
      if (mounted) {
        setState(() {
          _summaryData = res.data['data'];
          _summaryLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _summaryLoading = false);
    }
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
            tooltip: 'Reload',
            icon: const Icon(Icons.refresh),
            onPressed: () {
              _fetchSummary();
              setState(() {
                 _fetchedExtra = false;
              });
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

              // If not yet fetched, try to fetch rich profile once.
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

              final mobile = profile?.mobile ?? profile?.teacherPhone ?? '';
              final attendance = _summaryData?['class_attendance'];
              final presentStr = attendance != null 
                ? 'Present: ${attendance['present']}/${attendance['total']} (${attendance['percentage']}%)' 
                : 'Attendance: Loading...';

              return Card(
                elevation: 2,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: InkWell(
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => const TeacherProfilePage(),
                      ),
                    );
                  },
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CircleAvatar(
                          radius: 32,
                          backgroundColor: const Color(0xFFE6F5EE),
                          backgroundImage: (photo != null && photo.isNotEmpty)
                              ? NetworkImage(photo)
                              : null,
                          child: (photo == null || photo.isEmpty)
                              ? Text(
                                  name.isNotEmpty ? name[0].toUpperCase() : 'U',
                                  style: const TextStyle(
                                    fontSize: 28,
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
                                ),
                              ),
                              if (designation.isNotEmpty) ...[
                                const SizedBox(height: 2),
                                Text(
                                  designation,
                                  style: TextStyle(
                                    color: Colors.grey[700],
                                    fontSize: 14,
                                  ),
                                ),
                              ],
                              if (mobile.isNotEmpty) ...[
                                const SizedBox(height: 2),
                                Text(
                                  mobile,
                                  style: TextStyle(
                                    color: Colors.blue[800],
                                    fontSize: 13,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                              const Divider(height: 16),
                              if (schoolName != null)
                                Text(
                                  schoolName,
                                  style: const TextStyle(
                                    color: Color(0xFF1F2937),
                                    fontWeight: FontWeight.w600,
                                    fontSize: 14,
                                  ),
                                ),
                              const SizedBox(height: 4),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: Colors.green[50],
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  presentStr,
                                  style: TextStyle(
                                    color: Colors.green[800],
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
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
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const TeacherExamsPage(), // Principal has full access
                    ),
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

          // Attendance & Lesson Summary Sections
          if (_summaryData != null) ...[
            _statSection(
              title: 'Attendance Overview',
              icon: Icons.pie_chart_outline,
              color: Colors.blue,
              items: [
                _statRow('Class Attendance', _summaryData!['class_attendance']),
                _statRow('Extra Class Attendance', _summaryData!['extra_class_attendance']),
              ],
            ),
            const SizedBox(height: 12),
            _statSection(
              title: 'Lesson Evaluation',
              icon: Icons.menu_book_outlined,
              color: Colors.purple,
              items: [
                _statInfo('Total Routine Classes', _summaryData!['lesson_evaluation']['total_expected'].toString()),
                _statInfo('Evaluations Completed', _summaryData!['lesson_evaluation']['completed'].toString(), color: Colors.green),
                _statInfo('Evaluations Pending', _summaryData!['lesson_evaluation']['not_done'].toString(), color: Colors.orange),
              ],
            ),
            const SizedBox(height: 12),
          ],

          // Reports card
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
                    const Icon(Icons.folder_open_outlined, size: 28, color: Colors.indigo),
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

  Widget _statSection({required String title, required IconData icon, required Color color, required List<Widget> items}) {
    return Card(
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: const BorderRadius.only(topLeft: Radius.circular(8), topRight: Radius.circular(8)),
            ),
            child: Row(
              children: [
                Icon(icon, size: 18, color: color),
                const SizedBox(width: 8),
                Text(title, style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: color)),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(12),
            child: Column(children: items),
          ),
        ],
      ),
    );
  }

  Widget _statRow(String label, dynamic data) {
    if (data == null) return const SizedBox();
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 13)),
          Text(
            '${data['present']}/${data['total']} (${data['percentage']}%)',
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _statInfo(String label, String value, {Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 13)),
          Text(
            value,
            style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: color),
          ),
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
