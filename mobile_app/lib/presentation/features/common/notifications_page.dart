import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/dio_client.dart';

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key});

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  bool _isLoading = true;
  List<dynamic> _items = [];
  int _page = 1;
  bool _hasMore = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final resp = await DioClient().dio.get(
        'notifications',
        queryParameters: {'page': 1},
      );
      setState(() {
        _items = resp.data['data'];
        _page = 1;
        _hasMore = resp.data['next_page_url'] != null;
        _isLoading = false;
      });

      // Mark all as read on open
      await DioClient().dio.post(
        'notifications/mark-read',
        data: {'all': true},
      );
    } catch (e) {
      String msg = 'নোটিফিকেশন লোড করা যায়নি। আবার চেষ্টা করুন।';
      try {
        if (e is DioException &&
            e.response != null &&
            e.response?.data != null) {
          final d = e.response?.data;
          if (d is Map && d['message'] != null) {
            msg = d['message'].toString();
          }
        }
      } catch (_) {}

      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(msg)));
      }
      setState(() => _isLoading = false);
    }
  }

  Future<void> _loadMore() async {
    if (!_hasMore) return;
    try {
      final next = _page + 1;
      final resp = await DioClient().dio.get(
        'notifications',
        queryParameters: {'page': next},
      );
      setState(() {
        _items.addAll(resp.data['data']);
        _page = next;
        _hasMore = resp.data['next_page_url'] != null;
      });
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('নোটিফিকেশন'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadData),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : NotificationListener<ScrollNotification>(
              onNotification: (scroll) {
                if (scroll.metrics.pixels == scroll.metrics.maxScrollExtent) {
                  _loadMore();
                }
                return false;
              },
              child: ListView.builder(
                itemCount: _items.length,
                itemBuilder: (context, index) {
                  final it = _items[index];
                  final seen = it['read_at'] != null;
                  DateTime dt;
                  try {
                    dt = DateTime.parse(it['created_at']).toLocal();
                  } catch (_) {
                    dt = DateTime.now();
                  }
                  final timeStr = DateFormat('dd MMM, h:mm a').format(dt);

                  return Card(
                    margin: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    child: ListTile(
                      leading: Icon(
                        seen
                            ? Icons.mark_email_read
                            : Icons.notifications_active,
                        color: seen ? Colors.grey : Colors.blue,
                      ),
                      title: Text(
                        it['title'] ?? '',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      subtitle: Text(it['body'] ?? ''),
                      trailing: Text(
                        timeStr,
                        style: const TextStyle(
                          fontSize: 12,
                          color: Colors.grey,
                        ),
                      ),
                      onTap: () {
                        int? noticeId;
                        final rawNoticeId = it['notice_id'];
                        final noticeObj = it['notice'];
                        if (rawNoticeId != null) {
                          noticeId = int.tryParse(rawNoticeId.toString());
                        } else if (noticeObj is Map && noticeObj['id'] != null) {
                          noticeId = int.tryParse(noticeObj['id'].toString());
                        }

                        if (noticeId != null && noticeId > 0) {
                          GoRouter.of(context).push('/notices/$noticeId');
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text(
                                'এই নোটিফিকেশনের জন্য বিস্তারিত কোনো পৃষ্ঠা নেই',
                              ),
                            ),
                          );
                        }
                      },
                    ),
                  );
                },
              ),
            ),
    );
  }
}
