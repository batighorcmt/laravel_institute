import 'package:flutter/material.dart';
import 'package:flutter/src/widgets/framework.dart';
import 'package:flutter/src/widgets/placeholder.dart';
import 'principal_attendance_details_page.dart';
import 'lesson_evaluation_report_page.dart';

class PrincipalReportsPage extends StatelessWidget {
  const PrincipalReportsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reports')),
      body: ListView(
        padding: const EdgeInsets.all(12),
        children: [
          Card(
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
          const SizedBox(height: 8),
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
          const SizedBox(height: 12),
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
