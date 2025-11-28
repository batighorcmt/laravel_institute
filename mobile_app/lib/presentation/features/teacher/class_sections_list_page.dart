import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/dio_client.dart';

class ClassSectionsListPage extends StatefulWidget {
  const ClassSectionsListPage({super.key});
  @override
  State<ClassSectionsListPage> createState() => _ClassSectionsListPageState();
}

class _ClassSectionsListPageState extends State<ClassSectionsListPage> {
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
      final r = await _dio.get('teacher/students-attendance/class/meta');
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
      appBar: AppBar(title: const Text('Class Attendance')),
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
                final name = (m['class_name'] ?? '') as String;
                final sections = (m['sections'] as List? ?? []).cast<Map>();
                return Card(
                  elevation: 0,
                  child: ExpansionTile(
                    title: Text(name.isEmpty ? 'Class ${m['class_id']}' : name),
                    children: [
                      for (final s in sections)
                        ListTile(
                          title: Text('Section: ${s['name']}'),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: () {
                            final sectionId = s['id'];
                            final titleText =
                                '${name.isEmpty ? 'Class ${m['class_id']}' : name} • Section ${s['name']}';
                            context.push(
                              '/teacher/students-attendance/class/sections/$sectionId/mark?title=${Uri.encodeComponent(titleText)}',
                            );
                          },
                        ),
                    ],
                  ),
                );
              },
            ),
    );
  }
}
