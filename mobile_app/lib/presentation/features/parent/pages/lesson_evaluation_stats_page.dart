import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../state/parent_state.dart';

class LessonEvaluationStatsPage extends ConsumerWidget {
  const LessonEvaluationStatsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final statsAsync = ref.watch(parentEvaluationStatsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('বাৎসরিক পরিসংখ্যান'),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
      ),
      body: statsAsync.when(
        data: (stats) {
          if (stats.isEmpty) {
            return RefreshIndicator(
              onRefresh: () async => ref.refresh(parentEvaluationStatsProvider),
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: SizedBox(
                  height: MediaQuery.of(context).size.height * 0.7,
                  child: const Center(child: Text('কোনো পরিসংখ্যান পাওয়া যায়নি')),
                ),
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () async => ref.refresh(parentEvaluationStatsProvider),
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              child: Card(
                elevation: 4,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: Column(
                  children: [
                     Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          const Icon(Icons.assignment_turned_in, color: Colors.blue),
                          const SizedBox(width: 8),
                          Text(
                            'লেসন ইভ্যালুয়েশন রিপোর্ট (${DateTime.now().year})',
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.blue),
                          ),
                        ],
                      ),
                    ),
                    const Divider(height: 1),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: DataTable(
                        headingRowColor: WidgetStateProperty.all(Colors.grey[100]),
                        columnSpacing: 15,
                        horizontalMargin: 10,
                        columns: const [
                          DataColumn(label: Text('বিষয়ের নাম', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
                          DataColumn(label: Text('শিক্ষকের নাম', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
                          DataColumn(label: Text('পড়া হয়েছে', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
                          DataColumn(label: Text('আংশিক', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
                          DataColumn(label: Text('হয় নাই', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
                          DataColumn(label: Text('অনুপস্থিত', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13))),
                        ],
                        rows: stats.map((item) {
                          return DataRow(cells: [
                            DataCell(
                              SizedBox(
                                width: 100,
                                child: Text(item['subject_name'] ?? 'N/A', style: const TextStyle(fontSize: 12), overflow: TextOverflow.ellipsis, maxLines: 2),
                              ),
                            ),
                            DataCell(
                               SizedBox(
                                width: 120,
                                child: Text(item['teacher_name'] ?? 'N/A', style: const TextStyle(fontSize: 12), overflow: TextOverflow.ellipsis, maxLines: 2),
                              ),
                            ),
                            DataCell(_buildCountBadge(item['completed'], Colors.green)),
                            DataCell(_buildCountBadge(item['partial'], Colors.orange)),
                            DataCell(_buildCountBadge(item['not_done'], Colors.red)),
                            DataCell(_buildCountBadge(item['absent'], Colors.blueGrey)),
                          ]);
                        }).toList(),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],
                ),
              ),
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(child: Text('ত্রুটি: $err')),
      ),
    );
  }

  Widget _buildCountBadge(dynamic count, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        count.toString(),
        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
      ),
    );
  }
}
