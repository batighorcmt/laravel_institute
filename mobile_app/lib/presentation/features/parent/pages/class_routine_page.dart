import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../state/parent_state.dart';

class ClassRoutinePage extends ConsumerWidget {
  const ClassRoutinePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final routineAsync = ref.watch(parentRoutineProvider);

    return routineAsync.when(
      data: (rawData) {
        if (rawData.isEmpty) {
          return const Center(child: Text('কোনো রুটিন পাওয়া যায়নি'));
        }

        // Group by day_name_bn
        final Map<String, List<String>> grouped = {};
        for (var item in rawData) {
          final day = item['day_name_bn'] ?? item['day_of_week'] ?? 'N/A';
          final sub = item['subject']?['name'] ?? 'N/A';
          final start = item['start_time'] ?? '';
          final end = item['end_time'] ?? '';
          
          grouped.putIfAbsent(day, () => []);
          grouped[day]!.add('$sub ($start-$end)');
        }

        final sortedDays = grouped.keys.toList();

        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: sortedDays.length,
          itemBuilder: (context, index) {
            final dayName = sortedDays[index];
            final classes = grouped[dayName]!;

            return Card(
              margin: const EdgeInsets.only(bottom: 12),
              child: ExpansionTile(
                initiallyExpanded: true,
                title: Text(
                  dayName,
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                children: [
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: classes.map((cls) {
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: Row(
                            children: [
                              Container(
                                width: 4,
                                height: 24,
                                margin: const EdgeInsets.only(right: 12),
                                color: Colors.blue,
                              ),
                              Expanded(
                                child: Text(
                                  cls,
                                  style: const TextStyle(fontSize: 16),
                                ),
                              ),
                            ],
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (err, stack) => Center(child: Text('ত্রুটি: $err')),
    );
  }
}
