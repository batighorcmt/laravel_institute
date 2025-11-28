import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import 'lesson_evaluation_mark_page.dart';

class LessonEvaluationListPage extends StatefulWidget {
  const LessonEvaluationListPage({super.key});
  @override
  State<LessonEvaluationListPage> createState() =>
      _LessonEvaluationListPageState();
}

class _LessonEvaluationListPageState extends State<LessonEvaluationListPage> {
  late final Dio _dio;
  bool _loading = true;
  String? _error;
  List<dynamic> _items = const [];
  String _date = '';

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await _dio.get('teacher/lesson-evaluations/today-routine');
      final data = r.data as Map<String, dynamic>? ?? {};
      _items = (data['items'] as List? ?? []).cast<Map>();
      _date = (data['date'] as String?) ?? '';
    } catch (e) {
      _error = 'লোড ব্যর্থ';
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lesson Evaluation')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text(_error!))
          : ListView.separated(
              padding: const EdgeInsets.all(12),
              itemCount: _items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (ctx, i) {
                if (i == 0) {
                  return Text(
                    'তারিখ: ' + (_date.isEmpty ? '-' : _date),
                    style: Theme.of(context).textTheme.bodySmall,
                  );
                }
                final idx = i - 1;
                final m = _items[idx] as Map<String, dynamic>;
                final evaluated = (m['evaluated'] as bool?) ?? false;
                final title =
                    '${m['class_name'] ?? ''} ${m['section_name'] ?? ''}'
                        .trim();
                final sub =
                    '${m['subject_name'] ?? ''} • Period ${m['period_number'] ?? ''}';
                return Card(
                  color: evaluated
                      ? Colors.green.withValues(alpha: 0.12)
                      : Colors.orange.withValues(alpha: 0.08),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: ListTile(
                    title: Text(title.isEmpty ? 'Class' : title),
                    subtitle: Text(sub),
                    trailing: Icon(
                      evaluated ? Icons.check_circle : Icons.edit,
                      color: evaluated ? Colors.green : Colors.orange,
                    ),
                    onTap: () {
                      final rid = (m['routine_entry_id'] as num?)?.toInt() ?? 0;
                      if (rid > 0) {
                        Navigator.of(context)
                            .push(
                              MaterialPageRoute(
                                builder: (_) => LessonEvaluationMarkPage(
                                  routineEntryId: rid,
                                  headerTitle:
                                      '${m['subject_name'] ?? 'Evaluation'}',
                                  classId:
                                      (m['class_id'] as num?)?.toInt() ?? 0,
                                  sectionId:
                                      (m['section_id'] as num?)?.toInt() ?? 0,
                                  subjectId:
                                      (m['subject_id'] as num?)?.toInt() ?? 0,
                                ),
                              ),
                            )
                            .then((_) => _load());
                      }
                    },
                  ),
                );
              },
            ),
    );
  }
}
