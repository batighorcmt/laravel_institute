import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import 'homework_create_page.dart';

class TeacherHomeworkListPage extends StatefulWidget {
  const TeacherHomeworkListPage({super.key});
  @override
  State<TeacherHomeworkListPage> createState() =>
      _TeacherHomeworkListPageState();
}

class _TeacherHomeworkListPageState extends State<TeacherHomeworkListPage> {
  late final Dio _dio;
  bool _loading = true;
  String? _error;
  List<dynamic> _items = const [];

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await _dio.get('teacher/homework');
      final data = r.data;
      if (data is List) {
        _items = data;
      } else if (data is Map<String, dynamic> && data['data'] is List) {
        _items = data['data'] as List<dynamic>;
      } else {
        _items = const [];
      }
    } catch (e) {
      _error = 'লোড ব্যর্থ';
    } finally {
      if (mounted)
        setState(() {
          _loading = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Homework')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(child: Text(_error!))
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.separated(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(12),
                itemCount: _items.length,
                separatorBuilder: (_, __) => const SizedBox(height: 8),
                itemBuilder: (ctx, i) {
                  final m = (_items[i] as Map).cast<String, dynamic>();
                  final title = m['title']?.toString() ?? 'Homework';
                  final date = m['homework_date']?.toString() ?? '';
                  final due = m['submission_date']?.toString() ?? '';
                  return Card(
                    child: ListTile(
                      title: Text(title),
                      subtitle: Text(
                        due.isNotEmpty
                            ? 'Date: $date  •  Due: $due'
                            : 'Date: $date',
                      ),
                      trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                      onTap: () {},
                    ),
                  );
                },
              ),
            ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.of(context)
              .push(
                MaterialPageRoute(
                  builder: (_) => const TeacherHomeworkCreatePage(),
                ),
              )
              .then((ok) {
                if (ok == true) _load();
              });
        },
        icon: const Icon(Icons.add),
        label: const Text('Create'),
      ),
    );
  }
}
