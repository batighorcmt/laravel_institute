import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../state/parent_state.dart';

class LessonEvaluationPage extends ConsumerWidget {
  const LessonEvaluationPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final evaluationsAsync = ref.watch(parentEvaluationsProvider);

    return evaluationsAsync.when(
      data: (evaluations) {
        if (evaluations.isEmpty) {
          return const Center(child: Text('কোনো পাঠ মূল্যায়ন পাওয়া যায়নি'));
        }

        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: evaluations.length,
          itemBuilder: (context, index) {
            final eval = evaluations[index];
            final rating = double.tryParse(eval['rating']?.toString() ?? '0') ?? 0.0;

            return Card(
              margin: const EdgeInsets.only(bottom: 16),
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            eval['subject']?.toString() ?? 'N/A',
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.blue),
                          ),
                        ),
                        Text(
                          eval['date']?.toString() ?? '',
                          style: const TextStyle(fontSize: 12, color: Colors.grey),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text('অধ্যায়: ${eval['chapter'] ?? 'N/A'}', style: const TextStyle(fontWeight: FontWeight.w500)),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        const Text('রেটিং: '),
                        ...List.generate(5, (i) => Icon(
                          i < rating ? Icons.star : Icons.star_border,
                          color: Colors.amber,
                          size: 20,
                        )),
                      ],
                    ),
                    const SizedBox(height: 12),
                    const Text('শিক্ষকের মন্তব্য:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    const SizedBox(height: 4),
                    Text(eval['comments']?.toString() ?? 'কোনো মন্তব্য নেই', style: const TextStyle(fontSize: 14)),
                  ],
                ),
              ),
            );
          },
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (err, _) => Center(child: Text('ত্রুটি: $err')),
    );
  }
}
