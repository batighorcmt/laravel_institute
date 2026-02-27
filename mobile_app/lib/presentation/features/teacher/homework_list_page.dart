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

  Future<void> _delete(int id) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('মুছে ফেলুন'),
        content: const Text('আপনি কি নিশ্চিত যে এই হোমওয়ার্কটি মুছে ফেলতে চান?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('না')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('হ্যাঁ', style: TextStyle(color: Colors.red))),
        ],
      ),
    );
    if (ok != true) return;

    try {
      await _dio.delete('teacher/homework/$id');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('মুছে ফেলা হয়েছে')));
        _load();
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('অপারেশন ব্যর্থ')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Homework List')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? RefreshIndicator(onRefresh: _load, child: SingleChildScrollView(physics: const AlwaysScrollableScrollPhysics(), child: Container(height: 500, alignment: Alignment.center, child: Text(_error!))))
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.separated(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(12),
                itemCount: _items.length,
                separatorBuilder: (_, __) => const SizedBox(height: 12),
                itemBuilder: (ctx, i) {
                  final m = (_items[i] as Map).cast<String, dynamic>();
                  final id = (m['id'] as num).toInt();
                  final title = m['title']?.toString() ?? 'Homework';
                  final date = m['homework_date']?.toString() ?? '';
                  final due = m['submission_date']?.toString() ?? '';
                  final clsName = m['class_name']?.toString() ?? 'N/A';
                  final secName = m['section_name']?.toString() ?? 'N/A';
                  final subName = m['subject_name']?.toString() ?? 'N/A';

                  return Card(
                    elevation: 2,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  title,
                                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              Row(
                                children: [
                                  IconButton(
                                    icon: const Icon(Icons.edit, color: Colors.blue, size: 20),
                                    onPressed: () {
                                      Navigator.of(context).push(
                                        MaterialPageRoute(builder: (_) => TeacherHomeworkCreatePage(homework: m)),
                                      ).then((ok) => ok == true ? _load() : null);
                                    },
                                    constraints: const BoxConstraints(),
                                    padding: const EdgeInsets.all(8),
                                  ),
                                  IconButton(
                                    icon: const Icon(Icons.delete, color: Colors.red, size: 20),
                                    onPressed: () => _delete(id),
                                    constraints: const BoxConstraints(),
                                    padding: const EdgeInsets.all(8),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          const Divider(height: 16),
                          Row(
                            children: [
                              _infoChip(Icons.class_, clsName, Colors.indigo),
                              const SizedBox(width: 8),
                              _infoChip(Icons.grid_view, secName, Colors.teal),
                            ],
                          ),
                          const SizedBox(height: 8),
                          _infoLabel(Icons.book, 'Subject: ', subName),
                          const SizedBox(height: 4),
                          _infoLabel(Icons.calendar_today, 'Given: ', date),
                          if (due.isNotEmpty) ...[
                            const SizedBox(height: 4),
                            _infoLabel(Icons.event_available, 'Due: ', due, color: Colors.orange.shade800),
                          ],
                        ],
                      ),
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
        label: const Text('Add Homework'),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
      ),
    );
  }

  Widget _infoChip(IconData icon, String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(fontSize: 12, color: color, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _infoLabel(IconData icon, String label, String value, {Color? color}) {
    return Row(
      children: [
        Icon(icon, size: 14, color: color ?? Colors.black54),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(fontSize: 13, color: Colors.black54, fontWeight: FontWeight.w500),
        ),
        Expanded(
          child: Text(
            value,
            style: TextStyle(fontSize: 13, color: color ?? Colors.black87, fontWeight: FontWeight.bold),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}
