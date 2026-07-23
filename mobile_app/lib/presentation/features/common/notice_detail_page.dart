import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../state/notice_state.dart';
import '../../widgets/notice_reply_section.dart';

String _friendlyNoticeError(Object? error) {
  if (error is DioException) {
    final status = error.response?.statusCode;
    if (status == 404) {
      return 'নোটিশটি খুঁজে পাওয়া যায়নি। এটি মুছে ফেলা হয়ে থাকতে পারে।';
    }
    if (status == 403 || status == 401) {
      return 'এই নোটিশটি দেখার অনুমতি নেই।';
    }
    return 'নোটিশ লোড করা যায়নি। ইন্টারনেট সংযোগ পরীক্ষা করে আবার চেষ্টা করুন।';
  }
  return 'নোটিশ লোড করা যায়নি। আবার চেষ্টা করুন।';
}

class NoticeDetailPage extends ConsumerWidget {
  final int noticeId;
  const NoticeDetailPage({super.key, required this.noticeId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Mark as read when entering
    Future.microtask(() {
      ref.read(noticeRepositoryProvider).markAsRead(noticeId);
      ref.invalidate(noticesListProvider);
    });

    // We'll actually fetch details directly via repository
    return Scaffold(
      appBar: AppBar(title: const Text('নোটিশ')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: ref.read(noticeRepositoryProvider).getNoticeDetails(noticeId),
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.info_outline,
                      size: 40,
                      color: Colors.grey.shade500,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      _friendlyNoticeError(snap.error),
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 15),
                    ),
                  ],
                ),
              ),
            );
          }
          final notice = snap.data!;
          final publishAt =
              DateTime.tryParse(notice['publish_at'] ?? '')?.toLocal() ??
              DateTime.now();
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  notice['title'] ?? '',
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Icon(Icons.calendar_today, size: 14, color: Colors.grey),
                    const SizedBox(width: 6),
                    Text(
                      DateFormat('dd MMM yyyy, h:mm a').format(publishAt),
                      style: const TextStyle(color: Colors.grey),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  notice['body'] ?? '',
                  style: const TextStyle(fontSize: 16, height: 1.6),
                ),
                const SizedBox(height: 32),
                if (notice['reply_required'] == true || notice['reply_required'] == 1) ...[
                  NoticeReplySection(
                    noticeId: noticeId,
                    initialHasReplied: notice['has_replied'] ?? false,
                  ),
                  const SizedBox(height: 24),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
