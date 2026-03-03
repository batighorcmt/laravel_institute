import 'package:flutter/material.dart';
import '../../../../core/network/dio_client.dart';
import 'package:intl/intl.dart';

class NotificationLogsPage extends StatefulWidget {
  const NotificationLogsPage({super.key});

  @override
  State<NotificationLogsPage> createState() => _NotificationLogsPageState();
}

class _NotificationLogsPageState extends State<NotificationLogsPage> {
  bool _isLoading = true;
  Map<String, dynamic> _stats = {};
  List<dynamic> _logs = [];
  int _currentPage = 1;
  bool _hasMore = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final statsResp = await DioClient().dio.get('notifications/stats');
      final logsResp = await DioClient().dio.get('notifications/logs', queryParameters: {'page': 1});
      
      setState(() {
        _stats = statsResp.data;
        _logs = logsResp.data['data'];
        _currentPage = 1;
        _hasMore = logsResp.data['next_page_url'] != null;
        _isLoading = false;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading stats: $e')),
        );
      }
      setState(() => _isLoading = false);
    }
  }

  Future<void> _loadMore() async {
    if (!_hasMore) return;
    try {
      final next = _currentPage + 1;
      final logsResp = await DioClient().dio.get('notifications/logs', queryParameters: {'page': next});
      
      setState(() {
        _logs.addAll(logsResp.data['data']);
        _currentPage = next;
        _hasMore = logsResp.data['next_page_url'] != null;
      });
    } catch (e) {
      debugPrint('Load more error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('নোটিফিকেশন লগ'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _isLoading 
        ? const Center(child: CircularProgressIndicator())
        : Column(
            children: [
              _buildStatsGrid(),
              const Divider(),
              Expanded(
                child: NotificationListener<ScrollNotification>(
                  onNotification: (ScrollNotification scrollInfo) {
                    if (scrollInfo.metrics.pixels == scrollInfo.metrics.maxScrollExtent) {
                      _loadMore();
                    }
                    return false;
                  },
                  child: ListView.builder(
                    itemCount: _logs.length,
                    itemBuilder: (context, index) {
                      final log = _logs[index];
                      return _buildLogItem(log);
                    },
                  ),
                ),
              ),
            ],
          ),
    );
  }

  Widget _buildStatsGrid() {
    return Padding(
      padding: const EdgeInsets.all(12.0),
      child: Row(
        children: [
          _statCard('Total', _stats['total']?.toString() ?? '0', Colors.blue),
          _statCard('Sent', _stats['sent']?.toString() ?? '0', Colors.green),
          _statCard('Failed', _stats['failed']?.toString() ?? '0', Colors.red),
        ],
      ),
    );
  }

  Widget _statCard(String label, String value, Color color) {
    return Expanded(
      child: Card(
        color: color.withOpacity(0.1),
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Column(
            children: [
              Text(label, style: TextStyle(color: color, fontWeight: FontWeight.bold)),
              Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLogItem(dynamic log) {
    final bool isSuccess = log['status'] == 'sent';
    final DateTime dt = DateTime.parse(log['created_at']).toLocal();
    final String timeStr = DateFormat('h:mm a, d MMM').format(dt);

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      child: ListTile(
        leading: Icon(
          isSuccess ? Icons.check_circle : Icons.error,
          color: isSuccess ? Colors.green : Colors.red,
        ),
        title: Text(log['title'] ?? 'No Title', maxLines: 1, overflow: TextOverflow.ellipsis),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(log['body'] ?? '', maxLines: 2, overflow: TextOverflow.ellipsis),
            const SizedBox(height: 4),
            Row(
              children: [
                Icon(Icons.person, size: 14, color: Colors.grey[600]),
                const SizedBox(width: 4),
                Text(log['user']?['name'] ?? 'Unknown User', style: TextStyle(fontSize: 12, color: Colors.grey[600])),
                const Spacer(),
                Text(timeStr, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
              ],
            ),
            if (!isSuccess && log['error_message'] != null)
              Padding(
                padding: const EdgeInsets.only(top: 4.0),
                child: Text('Error: ${log['error_message']}', style: const TextStyle(color: Colors.red, fontSize: 11)),
              ),
          ],
        ),
      ),
    );
  }
}
