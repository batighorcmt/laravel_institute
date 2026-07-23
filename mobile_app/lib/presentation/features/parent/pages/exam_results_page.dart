import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/network/dio_client.dart';
// Removed GradientScaffold import

// Provider to fetch exams
final parentExamsProvider = FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final dio = DioClient().dio;
  final res = await dio.get('parent/exams');
  return res.data['data'] as List<dynamic>;
});

class ParentExamResultsPage extends ConsumerWidget {
  const ParentExamResultsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final examsAsync = ref.watch(parentExamsProvider);
    final cs = Theme.of(context).colorScheme;

    // No own Scaffold/AppBar here — this page renders inside ParentShellPage,
    // which already supplies the app bar (using the route's nav label). An
    // extra Scaffold+AppBar here rendered the title twice.
    return ColoredBox(
      color: Colors.white,
      child: examsAsync.when(
        data: (exams) {
          if (exams.isEmpty) {
            return const Center(child: Text('কোনো পরীক্ষার ফলাফল পাওয়া যায়নি।'));
          }
          return RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(parentExamsProvider);
            },
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: exams.length,
              itemBuilder: (context, index) {
                final exam = exams[index];
                return Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                    leading: CircleAvatar(
                      backgroundColor: cs.primaryContainer,
                      foregroundColor: cs.onPrimaryContainer,
                      child: const Icon(Icons.leaderboard),
                    ),
                    title: Text(
                      (exam['name'] ?? '').toString(),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 8.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('তারিখ: ${exam['start_date'] ?? '-'} হতে ${exam['end_date'] ?? '-'}'),
                          if (exam['result_publish_date'] != null)
                            Padding(
                              padding: const EdgeInsets.only(top: 4.0),
                              child: Text(
                                'ফলাফল প্রকাশ: ${exam['result_publish_date']}',
                                style: TextStyle(
                                  color: cs.primary,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                    trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                    onTap: () {
                      context.push('/parent/exams/${exam['id']}/results');
                    },
                  ),
                );
              },
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, stack) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('ত্রুটি দেখা দিয়েছে', style: TextStyle(color: cs.error)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => ref.invalidate(parentExamsProvider),
                child: const Text('পুনরায় চেষ্টা করুন'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
