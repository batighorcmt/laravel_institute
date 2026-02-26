import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../../../core/config/env.dart';
import '../../../state/parent_state.dart';

class HomeworkListPage extends ConsumerWidget {
  const HomeworkListPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeworkAsync = ref.watch(parentHomeworkProvider);
    final subjectsAsync = ref.watch(parentSubjectsProvider);
    final teachersAsync = ref.watch(parentTeachersProvider);

    final selectedDate = ref.watch(homeworkDateFilterProvider);
    final selectedSubjectId = ref.watch(homeworkSubjectFilterProvider);
    final selectedTeacherId = ref.watch(homeworkTeacherFilterProvider);

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
                          initialDate: selectedDate ?? DateTime.now(),
                          firstDate: DateTime(2024),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          ref.read(homeworkDateFilterProvider.notifier).state = picked;
                        }
                      },
                      icon: const Icon(Icons.calendar_today, size: 16),
                      label: Text(selectedDate == null ? 'তারিখ নির্বাচন' : "${selectedDate.day}/${selectedDate.month}/${selectedDate.year}"),
                    ),
                  ),
                  if (selectedDate != null)
                    IconButton(
                      icon: const Icon(Icons.clear, size: 18),
                      onPressed: () => ref.read(homeworkDateFilterProvider.notifier).state = null,
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
                        decoration: const InputDecoration(
                          labelText: 'বিষয়',
                          isDense: true,
                          contentPadding: EdgeInsets.all(8),
                          border: OutlineInputBorder(),
                          labelStyle: TextStyle(fontSize: 12),
                        ),
                        style: const TextStyle(fontSize: 12, color: Colors.black),
                        items: [
                          const DropdownMenuItem(value: null, child: Text('সব বিষয়', style: TextStyle(fontSize: 12))),
                          ...items.map((e) => DropdownMenuItem(
                                value: e['id'] as int,
                                child: Text(
                                  e['name'] ?? '',
                                  style: const TextStyle(fontSize: 12),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              )),
                        ],
                        onChanged: (val) => ref.read(homeworkSubjectFilterProvider.notifier).state = val,
                      ),
                      loading: () => const Text('লোড হচ্ছে...', style: TextStyle(fontSize: 12)),
                      error: (_, __) => const Text('ত্রুটি', style: TextStyle(fontSize: 12)),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: teachersAsync.when(
                      data: (items) => DropdownButtonFormField<int>(
                        value: selectedTeacherId,
                        isExpanded: true,
                        decoration: const InputDecoration(
                          labelText: 'শিক্ষক',
                          isDense: true,
                          contentPadding: EdgeInsets.all(8),
                          border: OutlineInputBorder(),
                          labelStyle: TextStyle(fontSize: 12),
                        ),
                        style: const TextStyle(fontSize: 12, color: Colors.black),
                        items: [
                          const DropdownMenuItem(value: null, child: Text('সব শিক্ষক', style: TextStyle(fontSize: 12))),
                          ...items.map((e) => DropdownMenuItem(
                                value: e['id'] as int,
                                child: Text(
                                  e['name'] ?? '',
                                  style: const TextStyle(fontSize: 12),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              )),
                        ],
                        onChanged: (val) => ref.read(homeworkTeacherFilterProvider.notifier).state = val,
                      ),
                      loading: () => const Text('লোড হচ্ছে...', style: TextStyle(fontSize: 12)),
                      error: (_, __) => const Text('ত্রুটি', style: TextStyle(fontSize: 12)),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        Expanded(
          child: homeworkAsync.when(
            data: (homeworks) {
              final filtered = homeworks.where((hw) {
                if (selectedSubjectId != null && hw['subject_id'] != selectedSubjectId) return false;
                if (selectedTeacherId != null && hw['teacher_id'] != selectedTeacherId) return false;
                return true;
              }).toList();

              if (filtered.isEmpty) {
                return const Center(child: Text('কোনো হোমওয়ার্ক পাওয়া যায়নি'));
              }

              return ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: filtered.length,
                itemBuilder: (context, index) {
                  final hw = filtered[index];
                  final hasAttachment = hw['attachment'] != null;

                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    elevation: 1,
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(color: Colors.blue, borderRadius: BorderRadius.circular(4)),
                                child: Text(
                                  hw['subject_name']?.toString() ?? 'N/A',
                                  style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                                ),
                              ),
                              Text(
                                "দেওয়া হয়েছে: ${hw['homework_date'] ?? 'N/A'}",
                                style: const TextStyle(fontSize: 11, color: Colors.grey),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Text(
                            hw['title']?.toString() ?? 'N/A',
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            hw['description']?.toString() ?? '',
                            style: const TextStyle(fontSize: 14, height: 1.5),
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text('শিক্ষক', style: TextStyle(fontSize: 10, color: Colors.grey)),
                                    Text(
                                      hw['teacher_name']?.toString() ?? 'N/A',
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ),
                              ),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  const Text('জমাদানের শেষ তারিখ', style: TextStyle(fontSize: 10, color: Colors.red)),
                                  Text(hw['submission_date'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.red, fontSize: 13)),
                                ],
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: hasAttachment
                                  ? () async {
                                      final storageUrl = Env.apiBaseUrl.replaceAll('/api/v1/', '/storage/');
                                      final attachmentPath = hw['attachment'].toString();
                                      // Correctly encoding the path manually just in case Uri.parse has trouble with spaces
                                      final encodedPath = attachmentPath.split('/').map((e) => Uri.encodeComponent(e)).join('/');
                                      final url = Uri.parse(storageUrl + encodedPath);
                                      if (await canLaunchUrl(url)) {
                                        await launchUrl(url, mode: LaunchMode.externalApplication);
                                      } else {
                                        if (context.mounted) {
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            const SnackBar(content: Text('ডাউনলোড লিঙ্কটি ওপেন করা সম্ভব হচ্ছে না')),
                                          );
                                        }
                                      }
                                    }
                                  : null,
                              icon: const Icon(Icons.file_download, size: 18),
                              label: Text(hasAttachment ? 'অ্যাটাচমেন্ট ডাউনলোড করুন' : 'অ্যাটাচমেন্ট নেই'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: hasAttachment ? Colors.indigo : Colors.grey[300],
                                foregroundColor: hasAttachment ? Colors.white : Colors.grey,
                                padding: const EdgeInsets.symmetric(vertical: 12),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              );
            },
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (err, stack) => Center(child: Text('ত্রুটি: $err')),
          ),
        ),
      ],
    );
  }
}
