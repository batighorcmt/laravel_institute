import 'package:flutter/material.dart';
import '../../../core/services/module_access.dart';
import 'principal_attendance_details_page.dart';
import 'lesson_evaluation_report_page.dart';
import 'extra_class_attendance_report_page.dart';
import 'teacher_attendance_report_page.dart';
import 'staff_attendance_report_page.dart';

class PrincipalReportsPage extends StatefulWidget {
  const PrincipalReportsPage({super.key});

  @override
  State<PrincipalReportsPage> createState() => _PrincipalReportsPageState();
}

class _PrincipalReportsPageState extends State<PrincipalReportsPage> {
  Set<String>? _enabledModules;

  @override
  void initState() {
    super.initState();
    ModuleAccess.fetch().then((m) {
      if (mounted) setState(() => _enabledModules = m);
    });
  }

  bool _moduleOn(String slug) => ModuleAccess.isOn(_enabledModules, slug);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reports')),
      body: ListView(
        padding: const EdgeInsets.all(12),
        children: [
          if (_moduleOn('attendance')) ...[
            Card(
              child: ListTile(
                leading: const Icon(
                  Icons.bar_chart_outlined,
                  color: Colors.green,
                ),
                title: const Text('Class Attendance Report'),
                subtitle: const Text(
                  'Daily class attendance summaries and branch-wise reports',
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
            const SizedBox(height: 8),
          ],
          if (_moduleOn('extra_class')) ...[
            Card(
              child: ListTile(
                leading: const Icon(
                  Icons.insights_outlined,
                  color: Colors.orange,
                ),
                title: const Text('Extra Class Attendance Report'),
                subtitle: const Text(
                  'Daily extra class summaries and reports',
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const ExtraClassAttendanceReportPage(),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 8),
          ],
          if (_moduleOn('attendance')) ...[
            Card(
              child: ListTile(
                leading: const Icon(
                  Icons.how_to_reg_outlined,
                  color: Colors.indigo,
                ),
                title: const Text('Teacher Attendance Report'),
                subtitle: const Text(
                  'দৈনিক ও মাসিক শিক্ষক হাজিরা, উপস্থিতির হার ও র‍্যাংকিং',
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const TeacherAttendanceReportPage(),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 8),
            Card(
              child: ListTile(
                leading: const Icon(
                  Icons.badge_outlined,
                  color: Colors.teal,
                ),
                title: const Text('Staff Attendance Report'),
                subtitle: const Text(
                  'দৈনিক ও মাসিক স্টাফ হাজিরা, উপস্থিতির হার ও র‍্যাংকিং',
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const StaffAttendanceReportPage(),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 8),
          ],
          if (_moduleOn('lesson_evaluation')) ...[
            Card(
              child: ListTile(
                leading: const Icon(
                  Icons.rate_review_outlined,
                  color: Colors.blue,
                ),
                title: const Text('Lesson Evaluation Report'),
                subtitle: const Text(
                  'Reports by date, class, section and subject',
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const LessonEvaluationReportPage(),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 8),
          ],
          const SizedBox(height: 4),
          // Placeholder for future reports
          Card(
            child: ListTile(
              leading: const Icon(Icons.more_horiz),
              title: const Text('More reports (coming soon)'),
              subtitle: const Text('Additional reports will appear here'),
            ),
          ),
        ],
      ),
    );
  }
}
