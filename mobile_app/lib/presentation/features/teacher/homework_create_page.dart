import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import 'package:file_picker/file_picker.dart';

class TeacherHomeworkCreatePage extends StatefulWidget {
  const TeacherHomeworkCreatePage({super.key});
  @override
  State<TeacherHomeworkCreatePage> createState() =>
      _TeacherHomeworkCreatePageState();
}

class _TeacherHomeworkCreatePageState extends State<TeacherHomeworkCreatePage> {
  final _formKey = GlobalKey<FormState>();
  late final Dio _dio;

  final _titleCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  // Meta-driven selections
  List<Map<String, dynamic>> _classes = const [];
  List<Map<String, dynamic>> _sections = const [];
  List<Map<String, dynamic>> _subjects = const [];
  int? _selectedClassId;
  int? _selectedSectionId;
  int? _selectedSubjectId;

  String _homeworkDate = _formatDate(DateTime.now());
  String? _submissionDate;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _loadClassMeta();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadClassMeta() async {
    try {
      final r = await _dio.get('teacher/students-attendance/class/meta');
      final data = (r.data as List?)?.cast<Map>() ?? [];
      _classes = data.map((e) => e.cast<String, dynamic>()).toList();
      if (_classes.isNotEmpty) {
        _selectedClassId = (_classes.first['class_id'] as num?)?.toInt();
        _sections = ((_classes.first['sections'] as List?) ?? [])
            .cast<Map>()
            .map((e) => e.cast<String, dynamic>())
            .toList();
        if (_sections.isNotEmpty) {
          _selectedSectionId = (_sections.first['id'] as num?)?.toInt();
          await _loadSubjects();
        }
      }
      if (mounted) setState(() {});
    } catch (_) {}
  }

  Future<void> _loadSubjects() async {
    _subjects = const [];
    _selectedSubjectId = null;
    if (_selectedClassId == null || _selectedSectionId == null) return;
    try {
      final r = await _dio.get(
        'teacher/subjects',
        queryParameters: {
          'class_id': _selectedClassId,
          'section_id': _selectedSectionId,
        },
      );
      final data = (r.data is Map && r.data['data'] is List)
          ? (r.data['data'] as List).cast<Map>()
          : <Map>[];
      _subjects = data.map((e) => e.cast<String, dynamic>()).toList();
      if (_subjects.isNotEmpty) {
        _selectedSubjectId = (_subjects.first['id'] as num?)?.toInt();
      }
      if (mounted) setState(() {});
    } catch (_) {}
  }

  String? _attachmentPath;

  Future<void> _pickAttachment() async {
    final res = await FilePicker.platform.pickFiles(withData: false);
    if (res != null && res.files.isNotEmpty) {
      _attachmentPath = res.files.single.path;
      if (mounted) setState(() {});
    }
  }

  Future<void> _pickHomeworkDate() async {
    final now = DateTime.now();
    final initial = _parseDate(_homeworkDate) ?? now;
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(now.year - 2, 1, 1),
      lastDate: DateTime(now.year + 1, 12, 31),
      helpText: 'তারিখ নির্বাচন',
    );
    if (picked != null) {
      setState(() {
        _homeworkDate = _formatDate(picked);
      });
    }
  }

  Future<void> _pickSubmissionDate() async {
    final now = DateTime.now();
    final base = _parseDate(_homeworkDate) ?? now;
    final picked = await showDatePicker(
      context: context,
      initialDate: _parseDate(_submissionDate ?? _homeworkDate) ?? base,
      firstDate: base,
      lastDate: DateTime(now.year + 1, 12, 31),
      helpText: 'সাবমিশন ডেট',
    );
    if (picked != null) {
      setState(() {
        _submissionDate = _formatDate(picked);
      });
    }
  }

  DateTime? _parseDate(String? s) {
    if (s == null || s.isEmpty) return null;
    try {
      final p = s.split('-');
      if (p.length == 3)
        return DateTime(int.parse(p[0]), int.parse(p[1]), int.parse(p[2]));
    } catch (_) {}
    return null;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _submitting = true;
    });
    try {
      final form = FormData.fromMap({
        'class_id': _selectedClassId,
        'section_id': _selectedSectionId,
        'subject_id': _selectedSubjectId,
        'homework_date': _homeworkDate,
        'submission_date': _submissionDate,
        'title': _titleCtrl.text.trim(),
        'description': _descCtrl.text.trim().isEmpty
            ? null
            : _descCtrl.text.trim(),
        if (_attachmentPath != null)
          'attachment': await MultipartFile.fromFile(
            _attachmentPath!,
            filename: _attachmentPath!.split(RegExp(r'[\\/]')).last,
          ),
      });
      final r = await _dio.post('teacher/homework', data: form);
      if (!mounted) return;
      final msg = (r.data is Map && r.data['message'] is String)
          ? r.data['message'] as String
          : 'হোমওয়ার্ক তৈরি সম্পন্ন';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('সংরক্ষণ ব্যর্থ')));
    } finally {
      if (mounted)
        setState(() {
          _submitting = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Create Homework')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Class selector
              InputDecorator(
                decoration: const InputDecoration(labelText: 'Class'),
                child: DropdownButton<int>(
                  isExpanded: true,
                  value: _selectedClassId,
                  items: _classes
                      .map(
                        (c) => DropdownMenuItem<int>(
                          value: (c['class_id'] as num?)?.toInt(),
                          child: Text((c['class_name'] ?? 'Class').toString()),
                        ),
                      )
                      .toList(),
                  onChanged: (v) async {
                    setState(() {
                      _selectedClassId = v;
                      final cls = _classes.firstWhere(
                        (e) => (e['class_id'] as num?)?.toInt() == v,
                        orElse: () => {},
                      );
                      _sections = ((cls['sections'] as List?) ?? [])
                          .cast<Map>()
                          .map((e) => e.cast<String, dynamic>())
                          .toList();
                      _selectedSectionId = _sections.isNotEmpty
                          ? (_sections.first['id'] as num?)?.toInt()
                          : null;
                      _subjects = const [];
                      _selectedSubjectId = null;
                    });
                    await _loadSubjects();
                  },
                ),
              ),
              const SizedBox(height: 12),
              // Section selector
              InputDecorator(
                decoration: const InputDecoration(labelText: 'Section'),
                child: DropdownButton<int>(
                  isExpanded: true,
                  value: _selectedSectionId,
                  items: _sections
                      .map(
                        (s) => DropdownMenuItem<int>(
                          value: (s['id'] as num?)?.toInt(),
                          child: Text((s['name'] ?? 'Section').toString()),
                        ),
                      )
                      .toList(),
                  onChanged: (v) async {
                    setState(() {
                      _selectedSectionId = v;
                    });
                    await _loadSubjects();
                  },
                ),
              ),
              const SizedBox(height: 12),
              // Subject selector
              InputDecorator(
                decoration: const InputDecoration(labelText: 'Subject'),
                child: DropdownButton<int>(
                  isExpanded: true,
                  value: _selectedSubjectId,
                  items: _subjects
                      .map(
                        (s) => DropdownMenuItem<int>(
                          value: (s['id'] as num?)?.toInt(),
                          child: Text((s['name'] ?? 'Subject').toString()),
                        ),
                      )
                      .toList(),
                  onChanged: (v) {
                    setState(() {
                      _selectedSubjectId = v;
                    });
                  },
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _titleCtrl,
                decoration: const InputDecoration(labelText: 'Title'),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Title required' : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _descCtrl,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: 'Description (optional)',
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(child: Text('Date: $_homeworkDate')),
                  TextButton.icon(
                    onPressed: _pickHomeworkDate,
                    icon: const Icon(Icons.calendar_today),
                    label: const Text('Pick'),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(child: Text('Due: ${_submissionDate ?? '-'}')),
                  TextButton.icon(
                    onPressed: _pickSubmissionDate,
                    icon: const Icon(Icons.event),
                    label: const Text('Pick'),
                  ),
                ],
              ),
              const Divider(height: 24),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      _attachmentPath == null
                          ? 'No attachment'
                          : _attachmentPath!.split(RegExp(r'[\\/]')).last,
                    ),
                  ),
                  TextButton.icon(
                    onPressed: _pickAttachment,
                    icon: const Icon(Icons.attach_file),
                    label: const Text('Attachment'),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed:
                      _submitting ||
                          _selectedClassId == null ||
                          _selectedSectionId == null ||
                          _selectedSubjectId == null
                      ? null
                      : _submit,
                  icon: const Icon(Icons.save),
                  label: Text(_submitting ? 'Saving...' : 'Save'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

String _formatDate(DateTime d) {
  final m = d.month.toString().padLeft(2, '0');
  final day = d.day.toString().padLeft(2, '0');
  return '${d.year}-$m-$day';
}
