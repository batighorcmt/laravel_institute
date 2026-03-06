import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../state/notice_state.dart';

class NoticeDetailPage extends ConsumerWidget {
  final int noticeId;
  const NoticeDetailPage({super.key, required this.noticeId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final future = ref.watch(noticeStatsProvider(noticeId));
    // We'll actually fetch details directly via repository
    return Scaffold(
      appBar: AppBar(title: const Text('নোটিশ')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: ref.read(noticeRepositoryProvider).getNoticeDetails(noticeId),
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
          if (snap.hasError) return Center(child: Text('Error: ${snap.error}'));
          final notice = snap.data!;
          final publishAt = DateTime.tryParse(notice['publish_at'] ?? '')?.toLocal() ?? DateTime.now();
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(notice['title'] ?? '', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Row(children: [
                  Icon(Icons.calendar_today, size: 14, color: Colors.grey),
                  const SizedBox(width: 6),
                  Text(DateFormat('dd MMM yyyy, h:mm a').format(publishAt), style: const TextStyle(color: Colors.grey)),
                ]),
                const SizedBox(height: 16),
                Text(notice['body'] ?? '', style: const TextStyle(fontSize: 16, height: 1.6)),
              ],
            ),
          );
        },
      ),
    );
  }
}
