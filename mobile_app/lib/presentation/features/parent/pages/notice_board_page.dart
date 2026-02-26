import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../state/parent_state.dart';

class NoticeBoardPage extends ConsumerWidget {
  const NoticeBoardPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final noticesAsync = ref.watch(parentNoticesProvider);

    return noticesAsync.when(
      data: (notices) {
        if (notices.isEmpty) {
          return const Center(child: Text('কোনো নোটিশ পাওয়া যায়নি'));
        }

        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: notices.length,
          itemBuilder: (context, index) {
            final notice = notices[index];
            const isHighPriority = false;

            return Card(
              margin: const EdgeInsets.only(bottom: 16),
              elevation: isHighPriority ? 4 : 1,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
                side: isHighPriority
                    ? const BorderSide(color: Colors.red, width: 1)
                    : BorderSide.none,
              ),
              child: ExpansionTile(
                title: Text(
                  notice['title']?.toString() ?? 'N/A',
                  style: TextStyle(
                    fontWeight: isHighPriority ? FontWeight.bold : FontWeight.normal,
                    color: isHighPriority ? Colors.red : Colors.black,
                  ),
                ),
                subtitle: Text(
                  notice['publish_at']?.toString() ?? '',
                  style: const TextStyle(fontSize: 12),
                ),
                children: [
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Divider(),
                        const SizedBox(height: 8),
                        Text(
                          notice['body']?.toString() ?? '',
                          style: const TextStyle(fontSize: 14, height: 1.6),
                        ),
                      ],
                    ),
                  ),
                ],
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
