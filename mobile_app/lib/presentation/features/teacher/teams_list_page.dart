import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';

class TeamsListPage extends StatefulWidget {
  const TeamsListPage({super.key});
  @override
  State<TeamsListPage> createState() => _TeamsListPageState();
}

class _TeamsListPageState extends State<TeamsListPage> {
  late final Dio _dio;
  List<dynamic> _items = const [];
  bool _loading = true;
  String? _error;
  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final r = await _dio.get('teacher/students-attendance/team/meta');
      _items = (r.data is List) ? (r.data as List) : [];
    } catch (e) {
      _error = 'লোড ব্যর্থ';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Team Attendance')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(
              child: Text(_error!, style: const TextStyle(color: Colors.red)),
            )
          : ListView.separated(
              padding: const EdgeInsets.all(12),
              separatorBuilder: (_, i) => const SizedBox(height: 8),
              itemCount: _items.length,
              itemBuilder: (ctx, i) {
                final m = _items[i] as Map<String, dynamic>? ?? {};
                return ListTile(
                  title: Text(m['name'] ?? 'Team'),
                  subtitle: Text(m['type'] ?? ''),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    /* navigate later */
                  },
                );
              },
            ),
    );
  }
}
