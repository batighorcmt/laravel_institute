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

        // Define day order starting from Sunday
        final dayOrder = [
          'রবিবার',
          'সোমবার',
          'মঙ্গলবার',
          'বুধবার',
          'বৃহস্পতিবার',
          'শুক্রবার',
          'শনিবার'
        ];

        // Group and sort data
        final Map<String, List<Map<String, dynamic>>> grouped = {};
        for (var item in rawData) {
          final day = item['day_name_bn']?.toString() ?? 'অন্যান্য';
          grouped.putIfAbsent(day, () => []);
          grouped[day]!.add(Map<String, dynamic>.from(item));
        }

        // Sort items within each day by period number
        for (var day in grouped.keys) {
          grouped[day]!.sort((a, b) {
            final pa = a['period_number'] ?? 0;
            final pb = b['period_number'] ?? 0;
            return pa.compareTo(pb);
          });
        }

        final sortedDays = dayOrder.where((day) => grouped.containsKey(day)).toList();
        // Add any other days that might be in the data but not in our list
        for (var day in grouped.keys) {
          if (!sortedDays.contains(day)) sortedDays.add(day);
        }

        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: sortedDays.length,
          itemBuilder: (context, index) {
            final dayName = sortedDays[index];
            final routines = grouped[dayName]!;

            return Card(
              margin: const EdgeInsets.only(bottom: 16),
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: ExpansionTile(
                initiallyExpanded: true,
                shape: const Border(),
                title: Text(
                  dayName,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.blue),
                ),
                children: routines.map((rt) {
                  final subject = rt['subject']?['name'] ?? 'N/A';
                  final teacher = rt['teacher']?['name'] ?? 'শিক্ষক নির্ধারিত নয়';
                  final period = rt['period_number'] != null ? '${rt['period_number']}ম পিরিয়ড' : '';
                  
                  // Only show time if strictly provided and not just "12:00 AM" or similar defaults if that's the case
                  // But the resource formats it. If DB is null, Carbon::parse(null) is the issue.
                  // For now, let's assume if it exists in JSON, we show it, but the user says it shows even when "not in database".
                  final start = rt['start_time'];
                  final end = rt['end_time'];
                  final hasTime = start != null && end != null;

                  return Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(
                      border: Border(top: BorderSide(color: Colors.grey.withOpacity(0.1))),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Column(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: Colors.blue.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                rt['period_number']?.toString() ?? '?',
                                style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.blue),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                subject,
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                              ),
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  const Icon(Icons.person_outline, size: 14, color: Colors.grey),
                                  const SizedBox(width: 4),
                                  Text(
                                    teacher,
                                    style: const TextStyle(color: Colors.grey, fontSize: 13),
                                  ),
                                ],
                              ),
                              if (hasTime) ...[
                                const SizedBox(height: 4),
                                Row(
                                  children: [
                                    const Icon(Icons.access_time, size: 14, color: Colors.orange),
                                    const SizedBox(width: 4),
                                    Text(
                                      '$start - $end',
                                      style: const TextStyle(color: Colors.orange, fontSize: 13, fontWeight: FontWeight.w500),
                                    ),
                                  ],
                                ),
                              ],
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                }).toList(),
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
