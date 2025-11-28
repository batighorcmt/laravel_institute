import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';

class StudentsAttendanceMenuPage extends StatefulWidget {
  const StudentsAttendanceMenuPage({super.key});

  @override
  State<StudentsAttendanceMenuPage> createState() => _StudentsAttendanceMenuPageState();
}

class _StudentsAttendanceMenuPageState extends State<StudentsAttendanceMenuPage> {
  late final Dio _dio;
  Map<String, bool> _modules = const {};
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final r = await _dio.get('teacher/students-attendance/modules');
      final data = (r.data is Map<String, dynamic>) ? (r.data as Map<String,dynamic>) : <String,dynamic>{};
      _modules = {
        'class_attendance': data['class_attendance'] == true,
        'extra_class_attendance': data['extra_class_attendance'] == true,
        'team_attendance': data['team_attendance'] == true,
      };
    } catch (e) {
      _error = 'Failed to load';
    } finally {
      if (mounted) setState(() { _loading = false; });
    }
  }

  void _open(String key) {
    if (_modules[key] != true) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('এই মডিউলটি আপনার জন্য সক্রিয় নয়')));
      return;
    }
    switch (key) {
      case 'class_attendance':
        Navigator.of(context).pushNamed('/teacher/students-attendance/class');
        break;
      case 'extra_class_attendance':
        Navigator.of(context).pushNamed('/teacher/students-attendance/extra');
        break;
      case 'team_attendance':
        Navigator.of(context).pushNamed('/teacher/students-attendance/team');
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Students Attendance')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
                  _Card(keyId: 'class_attendance', title: 'Class Attendance', icon: Icons.fact_check_outlined, enabled: _modules['class_attendance'] == true, onTap: _open),
                  const SizedBox(height: 12),
                  _Card(keyId: 'extra_class_attendance', title: 'Extra Class Attendance', icon: Icons.event_note_outlined, enabled: _modules['extra_class_attendance'] == true, onTap: _open),
                  const SizedBox(height: 12),
                  _Card(keyId: 'team_attendance', title: 'Team Attendance', icon: Icons.groups_outlined, enabled: _modules['team_attendance'] == true, onTap: _open),
                ],
              ),
            ),
    );
  }
}

class _Card extends StatelessWidget {
  final String keyId; final String title; final IconData icon; final bool enabled; final void Function(String) onTap;
  const _Card({required this.keyId, required this.title, required this.icon, required this.enabled, required this.onTap});
  @override
  Widget build(BuildContext context) {
    final color = enabled ? Theme.of(context).colorScheme.primary : Colors.grey;
    return InkWell(
      onTap: enabled ? () => onTap(keyId) : null,
      child: Card(
        elevation: 0,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
          child: Row(children:[
            Icon(icon, size: 32, color: color),
            const SizedBox(width: 12),
            Expanded(child: Text(title, style: TextStyle(fontWeight: FontWeight.w600, color: enabled ? null : Colors.grey)) ),
            Icon(Icons.chevron_right, color: color)
          ]),
        ),
      ),
    );
  }
}
