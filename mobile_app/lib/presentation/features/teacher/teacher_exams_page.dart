import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../widgets/animated_tile.dart';
import '../../../widgets/app_snack.dart';
import '../../../data/teacher/teacher_exam_repository.dart';
import 'mark_entry_selection_page.dart';
import 'exam_duty_page.dart';

class TeacherExamsPage extends ConsumerStatefulWidget {
  const TeacherExamsPage({super.key});

  @override
  ConsumerState<TeacherExamsPage> createState() => _TeacherExamsPageState();
}

class _TeacherExamsPageState extends ConsumerState<TeacherExamsPage> {
  bool _isExamController = false;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _checkStatus();
  }

  Future<void> _checkStatus() async {
    try {
      final repo = TeacherExamRepository();
      final status = await repo.getExamStatus();
      if (mounted) {
        setState(() {
          _isExamController = status['is_exam_controller'] ?? false;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'Status fetch failed');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Exams'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : GridView.count(
              padding: const EdgeInsets.all(16),
              crossAxisCount: 2,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              children: [
                AnimatedTile(
                  title: 'Exam Duty',
                  icon: Icons.assignment_ind_outlined,
                  background: const Color(0xFFF0F9FF),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const ExamDutyPage()),
                    );
                  },
                ),
                AnimatedTile(
                  title: 'Seat Finding',
                  icon: Icons.person_search_outlined,
                  background: const Color(0xFFFFF7ED),
                  onTap: () {
                    showAppSnack(context, message: 'Seat Find coming soon');
                  },
                ),
                AnimatedTile(
                  title: 'Mark Entry',
                  icon: Icons.edit_note_outlined,
                  background: const Color(0xFFF5F3FF),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const MarkEntrySelectionPage()),
                    );
                  },
                ),
                if (_isExamController) ...[
                  AnimatedTile(
                    title: 'Duty Allocation',
                    icon: Icons.admin_panel_settings_outlined,
                    background: const Color(0xFFF0FDF4),
                    onTap: () {
                      showAppSnack(context, message: 'Duty Allocation coming soon');
                    },
                  ),
                  AnimatedTile(
                    title: 'Attendance Report',
                    icon: Icons.summarize_outlined,
                    background: const Color(0xFFFFF1F2),
                    onTap: () {
                      showAppSnack(context, message: 'Attendance Report coming soon');
                    },
                  ),
                ],
              ],
            ),
    );
  }
}
