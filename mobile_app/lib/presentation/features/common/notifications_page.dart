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
      String msg = 'Error loading notifications';
      try {
        if (e is DioException &&
            e.response != null &&
            e.response?.data != null) {
          final d = e.response?.data;
          if (d is Map && d['message'] != null)
            msg = d['message'];
          else
            msg = e.toString();
        } else {
          msg = e.toString();
        }
      } catch (_) {
        msg = e.toString();
      }

      if (mounted)
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(msg)));
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
                if (scroll.metrics.pixels == scroll.metrics.maxScrollExtent)
                  _loadMore();
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
                      trailing: Text(timeStr, style: const TextStyle(fontSize: 12, color: Colors.grey)),
                      onTap: () {
                        final nid = it['notice_id'] ?? it['notice'] ?? it['noticeId'] ?? it['id'];
                        if (nid != null) {
                          final id = int.tryParse(nid.toString()) ?? 0;
                          if (id > 0) {
                            GoRouter.of(context).push('/notices/$id');
                          }
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
