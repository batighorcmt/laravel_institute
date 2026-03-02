import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../state/notice_state.dart';
import 'notice_create_page.dart';

class NoticeListPage extends ConsumerWidget {
  const NoticeListPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final noticesAsync = ref.watch(noticesListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('নোটিশ ব্যবস্থাপনা'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.invalidate(noticesListProvider),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const NoticeCreatePage()),
          ).then((_) => ref.invalidate(noticesListProvider));
        },
        child: const Icon(Icons.add),
      ),
      body: noticesAsync.when(
        data: (notices) {
          if (notices.isEmpty) {
            return const Center(child: Text('কোনো নোটিশ পাওয়া যায়নি'));
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: notices.length,
            itemBuilder: (context, index) {
              final notice = notices[index];
              final publishAt = DateTime.tryParse(notice['publish_at'] ?? '') ?? DateTime.now();

              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: ListTile(
                  title: Text(notice['title']?.toString() ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold)),
                  subtitle: Text(DateFormat('dd MMM yyyy, hh:mm a').format(publishAt)),
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.bar_chart, color: Colors.blue),
                        onPressed: () => _viewStats(context, notice),
                      ),
                      IconButton(
                        icon: const Icon(Icons.delete_outline, color: Colors.red),
                        onPressed: () => _confirmDelete(context, ref, notice),
                      ),
                    ],
                  ),
                  onTap: () => _viewDetails(context, notice),
                ),
              );
            },
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(child: Text('ত্রুটি: $err')),
      ),
    );
  }

  void _viewDetails(BuildContext context, dynamic notice) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(notice['title']?.toString() ?? ''),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(DateFormat('dd MMMM yyyy, hh:mm a').format(DateTime.tryParse(notice['publish_at'] ?? '') ?? DateTime.now()), 
                   style: const TextStyle(fontSize: 12, color: Colors.grey)),
              const Divider(),
              Text(notice['body']?.toString() ?? ''),
              const SizedBox(height: 16),
              const Text('টার্গেট অডিয়েন্স:', style: TextStyle(fontWeight: FontWeight.bold)),
              Text(notice['audience_type'] ?? 'সবাই'),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('বন্ধ করুন')),
        ],
      ),
    );
  }

  void _viewStats(BuildContext context, dynamic notice) {
    // Navigate to a stats page or show a modal
    showModalBottomSheet(
      context: context,
      builder: (context) => Consumer(
        builder: (context, ref, _) {
          final statsAsync = ref.watch(noticeStatsProvider(notice['id']));
          return statsAsync.when(
            data: (stats) => Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('নোটিশ পরিসংখ্যান', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _StatCard(label: 'দেখা হয়েছে', value: '${stats['read_count']}', color: Colors.blue),
                      _StatCard(label: 'রিপ্লাই', value: '${stats['reply_count']}', color: Colors.green),
                    ],
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(onPressed: () => Navigator.pop(context), child: const Text('ঠিক আছে')),
                ],
              ),
            ),
            loading: () => const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator())),
            error: (err, _) => Center(child: Text('ত্রুটি: $err')),
          );
        },
      ),
    );
  }

  void _confirmDelete(BuildContext context, WidgetRef ref, dynamic notice) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('ডিলিট নিশ্চিত করুন'),
        content: const Text('আপনি কি নিশ্চিতভাবে এই নোটিশটি ডিলিট করতে চান?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('না')),
          TextButton(
            onPressed: () async {
              await ref.read(noticeRepositoryProvider).deleteNotice(notice['id']);
              Navigator.pop(context);
              ref.invalidate(noticesListProvider);
            },
            child: const Text('হ্যাঁ, ডিলিট করুন', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  const _StatCard({required this.label, required this.value, required this.color});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(value, style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: color)),
        Text(label, style: const TextStyle(color: Colors.grey)),
      ],
    );
  }
}
