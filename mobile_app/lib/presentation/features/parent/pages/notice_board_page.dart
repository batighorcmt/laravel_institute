import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../state/notice_state.dart';
import '../../../widgets/notice_reply_section.dart';

class NoticeBoardPage extends ConsumerWidget {
  const NoticeBoardPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final noticesAsync = ref.watch(noticesListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('নোটিশ বোর্ড'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.invalidate(noticesListProvider),
          ),
        ],
      ),
      body: noticesAsync.when(
        data: (notices) {
          if (notices.isEmpty) {
            return const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.campaign_outlined, size: 64, color: Colors.grey),
                  SizedBox(height: 16),
                  Text('কোনো নতুন নোটিশ নেই', style: TextStyle(color: Colors.grey, fontSize: 16)),
                ],
              ),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: notices.length,
            itemBuilder: (context, index) {
              final notice = notices[index];
              final isRead = notice['is_read'] ?? false;
              final publishAt = DateTime.tryParse(notice['publish_at'] ?? '') ?? DateTime.now();

              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                elevation: isRead ? 1 : 3,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: isRead ? BorderSide.none : const BorderSide(color: Colors.blue, width: 0.5),
                ),
                child: ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  title: Text(
                    notice['title']?.toString() ?? 'N/A',
                    style: TextStyle(
                      fontWeight: isRead ? FontWeight.normal : FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                  subtitle: Padding(
                    padding: const EdgeInsets.only(top: 4.0),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_month, size: 14, color: Colors.grey),
                        const SizedBox(width: 4),
                        Text(DateFormat('dd MMMM yyyy').format(publishAt), style: const TextStyle(fontSize: 12)),
                        const SizedBox(width: 12),
                        const Icon(Icons.access_time, size: 14, color: Colors.grey),
                        const SizedBox(width: 4),
                        Text(DateFormat('hh:mm a').format(publishAt), style: const TextStyle(fontSize: 12)),
                      ],
                    ),
                  ),
                  trailing: isRead ? null : const Badge(label: Text('নতুন')),
                  onTap: () => _viewDetails(context, ref, notice),
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

  void _viewDetails(BuildContext context, WidgetRef ref, dynamic notice) {
    if (!(notice['is_read'] ?? false)) {
      ref.read(noticeRepositoryProvider).markAsRead(notice['id']);
      ref.invalidate(noticesListProvider);
    }
    
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.6,
        maxChildSize: 0.9,
        expand: false,
        builder: (context, scrollController) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              const SizedBox(height: 12),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(color: Colors.grey[300], borderRadius: BorderRadius.circular(2)),
              ),
              const SizedBox(height: 12),
              Expanded(
                child: SingleChildScrollView(
                  controller: scrollController,
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(color: Colors.blue.withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                            child: Text(
                              DateFormat('dd MMM yyyy').format(DateTime.tryParse(notice['publish_at'] ?? '') ?? DateTime.now()),
                              style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12),
                            ),
                          ),
                          const Spacer(),
                          if (notice['reply_required'] == true || notice['reply_required'] == 1) 
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(color: Colors.red.withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                              child: const Text('রিপ্লাই প্রয়োজন', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 12)),
                            ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Text(
                        notice['title']?.toString() ?? '',
                        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Color(0xFF1F2937), height: 1.2),
                      ),
                      const SizedBox(height: 16),
                      const Divider(height: 1),
                      const SizedBox(height: 20),
                      Text(
                        notice['body']?.toString() ?? '',
                        style: const TextStyle(fontSize: 16, height: 1.7, color: Color(0xFF374151)),
                      ),
                      const SizedBox(height: 40),
                      if (notice['reply_required'] == true || notice['reply_required'] == 1) ...[
                        NoticeReplySection(noticeId: notice['id'] as int),
                        const SizedBox(height: 24),
                      ],
                      SizedBox(
                        width: double.infinity,
                        height: 52,
                        child: FilledButton(
                          onPressed: () => Navigator.pop(context),
                          style: FilledButton.styleFrom(
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                          child: const Text('বন্ধ করুন', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                        ),
                      ),
                      const SizedBox(height: 24),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
