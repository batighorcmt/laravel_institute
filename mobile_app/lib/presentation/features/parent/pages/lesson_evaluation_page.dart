import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../state/parent_state.dart';

class LessonEvaluationPage extends ConsumerWidget {
  const LessonEvaluationPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final evaluationsAsync = ref.watch(parentEvaluationsProvider);
    final subjectsAsync = ref.watch(parentSubjectsProvider);
    final teachersAsync = ref.watch(parentTeachersProvider);

    final fromDate = ref.watch(evalFromDateFilterProvider);
    final toDate = ref.watch(evalToDateFilterProvider);
    final selectedSubjectId = ref.watch(evalSubjectFilterProvider);
    final selectedTeacherId = ref.watch(evalTeacherFilterProvider);
    final selectedStatus = ref.watch(evalStatusFilterProvider);

    return Column(
      children: [
        // Filters Section
        Container(
          padding: const EdgeInsets.all(12),
          color: Colors.white,
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: fromDate ?? DateTime.now(),
                          firstDate: DateTime(2024),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          ref.read(evalFromDateFilterProvider.notifier).state = picked;
                        }
                      },
                      icon: const Icon(Icons.calendar_today, size: 14),
                      label: Text(
                        fromDate == null ? 'হতে' : "${fromDate.day}/${fromDate.month}/${fromDate.year}",
                        style: const TextStyle(fontSize: 11),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: toDate ?? DateTime.now(),
                          firstDate: DateTime(2024),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          ref.read(evalToDateFilterProvider.notifier).state = picked;
                        }
                      },
                      icon: const Icon(Icons.calendar_today, size: 14),
                      label: Text(
                        toDate == null ? 'পর্যন্ত' : "${toDate.day}/${toDate.month}/${toDate.year}",
                        style: const TextStyle(fontSize: 11),
                      ),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.refresh, size: 20),
                    onPressed: () {
                      ref.read(evalFromDateFilterProvider.notifier).state = DateTime.now();
                      ref.read(evalToDateFilterProvider.notifier).state = DateTime.now();
                      ref.read(evalSubjectFilterProvider.notifier).state = null;
                      ref.read(evalTeacherFilterProvider.notifier).state = null;
                      ref.read(evalStatusFilterProvider.notifier).state = null;
                    },
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: subjectsAsync.when(
                      data: (items) => DropdownButtonFormField<int>(
                        value: selectedSubjectId,
                        isExpanded: true,
                        decoration: _filterDecoration('বিষয়'),
                        style: const TextStyle(fontSize: 11, color: Colors.black),
                        items: [
                          const DropdownMenuItem(value: null, child: Text('সব বিষয়', style: TextStyle(fontSize: 11))),
                          ...items.map((e) => DropdownMenuItem(
                                value: e['id'] as int,
                                child: Text(e['name'] ?? '', style: const TextStyle(fontSize: 11), overflow: TextOverflow.ellipsis),
                              )),
                        ],
                        onChanged: (val) => ref.read(evalSubjectFilterProvider.notifier).state = val,
                      ),
                      loading: () => const Text('...', style: TextStyle(fontSize: 11)),
                      error: (_, __) => const Text('!', style: TextStyle(fontSize: 11)),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: teachersAsync.when(
                      data: (items) => DropdownButtonFormField<int>(
                        value: selectedTeacherId,
                        isExpanded: true,
                        decoration: _filterDecoration('শিক্ষক'),
                        style: const TextStyle(fontSize: 11, color: Colors.black),
                        items: [
                          const DropdownMenuItem(value: null, child: Text('সব শিক্ষক', style: TextStyle(fontSize: 11))),
                          ...items.map((e) => DropdownMenuItem(
                                value: e['id'] as int,
                                child: Text(e['name'] ?? '', style: const TextStyle(fontSize: 11), overflow: TextOverflow.ellipsis),
                              )),
                        ],
                        onChanged: (val) => ref.read(evalTeacherFilterProvider.notifier).state = val,
                      ),
                      loading: () => const Text('...', style: TextStyle(fontSize: 11)),
                      error: (_, __) => const Text('!', style: TextStyle(fontSize: 11)),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: selectedStatus,
                      isExpanded: true,
                      decoration: _filterDecoration('অবস্থা'),
                      style: const TextStyle(fontSize: 11, color: Colors.black),
                      items: const [
                        DropdownMenuItem(value: null, child: Text('সব', style: TextStyle(fontSize: 11))),
                        DropdownMenuItem(value: 'completed', child: Text('পড়া হয়েছে', style: TextStyle(fontSize: 11))),
                        DropdownMenuItem(value: 'partial', child: Text('আংশিক', style: TextStyle(fontSize: 11))),
                        DropdownMenuItem(value: 'not_done', child: Text('পড়া হয়নি', style: TextStyle(fontSize: 11))),
                        DropdownMenuItem(value: 'absent', child: Text('অনুপস্থিত', style: TextStyle(fontSize: 11))),
                      ],
                      onChanged: (val) => ref.read(evalStatusFilterProvider.notifier).state = val,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
        Expanded(
          child: evaluationsAsync.when(
            data: (evaluations) {
              if (evaluations.isEmpty) {
                return const Center(child: Text('কোনো পাঠ মূল্যায়ন পাওয়া যায়নি'));
              }
              return ListView.builder(
                padding: const EdgeInsets.all(12),
                itemCount: evaluations.length,
                itemBuilder: (context, index) {
                  final eval = evaluations[index];
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    elevation: 1,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  eval['subject']?.toString() ?? 'N/A',
                                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.indigo),
                                ),
                              ),
                              Text(
                                eval['date']?.toString() ?? '',
                                style: const TextStyle(fontSize: 11, color: Colors.grey),
                              ),
                            ],
                          ),
                          if (eval['notes'] != null && eval['notes'].toString().isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Container(
                              padding: const EdgeInsets.all(10),
                              width: double.infinity,
                              decoration: BoxDecoration(
                                color: Colors.blueGrey[50],
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: Colors.blueGrey[100]!),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('পাঠ বিষয়/নোট:', style: TextStyle(fontSize: 10, color: Colors.blueGrey, fontWeight: FontWeight.bold)),
                                  const SizedBox(height: 4),
                                  Text(eval['notes'].toString(), style: const TextStyle(fontSize: 13, height: 1.4)),
                                ],
                              ),
                            ),
                          ],
                          const SizedBox(height: 12),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              _buildStatusBadge(eval['status_label'], eval['status_color']),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    const Text('শিক্ষক', style: TextStyle(fontSize: 9, color: Colors.grey)),
                                    Text(
                                      eval['teacher']?.toString() ?? 'N/A',
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          if (eval['remarks'] != null && eval['remarks'].toString().isNotEmpty) ...[
                            const Divider(height: 20),
                            Text('মন্তব্য: ${eval['remarks']}', style: const TextStyle(fontSize: 12, fontStyle: FontStyle.italic, color: Colors.black87)),
                          ],
                        ],
                      ),
                    ),
                  );
                },
              );
            },
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (err, _) => Center(child: Text('ত্রুটি: $err')),
          ),
        ),
      ],
    );
  }

  InputDecoration _filterDecoration(String label) {
    return InputDecoration(
      labelText: label,
      isDense: true,
      contentPadding: const EdgeInsets.all(8),
      border: const OutlineInputBorder(),
      labelStyle: const TextStyle(fontSize: 11),
    );
  }

  Widget _buildStatusBadge(String? label, String? colorKey) {
    Color color;
    switch (colorKey) {
      case 'success':
        color = Colors.green;
        break;
      case 'warning':
        color = Colors.orange;
        break;
      case 'danger':
        color = Colors.red;
        break;
      case 'secondary':
        color = Colors.grey;
        break;
      default:
        color = Colors.blue;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: color.withOpacity(0.5)),
      ),
      child: Text(
        label ?? 'N/A',
        style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold),
      ),
    );
  }
}
