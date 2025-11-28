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
  // Routine-driven selections (today only)
  // [{class_id,class_name,sections:[{id,name,subjects:[{id,name}]}]}]
  List<Map<String, dynamic>> _classes = const [];
  List<Map<String, dynamic>> _sections = const [];
  List<Map<String, dynamic>> _subjects = const [];
  int? _selectedClassId;
  int? _selectedSectionId;
  int? _selectedSubjectId;

  // Creation date will be set server-side to today
  String? _submissionDate;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _loadTodayRoutine();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadTodayRoutine() async {
    try {
      final r = await _dio.get('teacher/lesson-evaluations/today-routine');
      final items = (r.data is Map && r.data['items'] is List)
          ? (r.data['items'] as List).cast<Map>()
          : <Map>[];
      final Map<int, Map<String, dynamic>> grouped = {};
      for (final raw in items) {
        final m = raw.cast<String, dynamic>();
        final clsId = (m['class_id'] as num?)?.toInt();
        final secId = (m['section_id'] as num?)?.toInt();
        final subId = (m['subject_id'] as num?)?.toInt();
        if (clsId == null || secId == null || subId == null) continue;
        final cls = grouped.putIfAbsent(
          clsId,
          () => {
            'class_id': clsId,
            'class_name': (m['class_name'] ?? '').toString(),
            'sections': <Map<String, dynamic>>[],
          },
        );
        final sections = (cls['sections'] as List).cast<Map<String, dynamic>>();
        Map<String, dynamic>? sec = sections.firstWhere(
          (e) => (e['id'] as int) == secId,
          orElse: () => {},
        );
        if (sec.isEmpty) {
          sec = {
            'id': secId,
            'name': (m['section_name'] ?? '').toString(),
            'subjects': <Map<String, dynamic>>[],
          };
          sections.add(sec);
        }
        final subs = (sec['subjects'] as List).cast<Map<String, dynamic>>();
        if (!subs.any((e) => (e['id'] as int) == subId)) {
          subs.add({'id': subId, 'name': (m['subject_name'] ?? '').toString()});
        }
      }
      _classes = grouped.values.toList();
      if (_classes.isNotEmpty) {
        _selectedClassId = (_classes.first['class_id'] as int);
        _sections = (_classes.first['sections'] as List)
            .cast<Map<String, dynamic>>();
        if (_sections.isNotEmpty) {
          _selectedSectionId = (_sections.first['id'] as int);
          _subjects = (_sections.first['subjects'] as List)
              .cast<Map<String, dynamic>>();
          if (_subjects.isNotEmpty)
            _selectedSubjectId = (_subjects.first['id'] as int);
        }
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

  // Homework creation date is server-side. Only due date is picked.

  Future<void> _pickSubmissionDate() async {
    final now = DateTime.now();
    final base = DateTime(now.year, now.month, now.day);
    final picked = await showDatePicker(
      context: context,
      initialDate: _parseDate(_submissionDate) ?? base,
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
    if (_selectedClassId == null ||
        _selectedSectionId == null ||
        _selectedSubjectId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('ক্লাস, শাখা ও বিষয় নির্বাচন করুন')),
      );
      return;
    }
    setState(() {
      _submitting = true;
    });
    try {
      final form = FormData.fromMap({
        'class_id': _selectedClassId,
        'section_id': _selectedSectionId,
        'subject_id': _selectedSubjectId,
        // homework_date is omitted; server will default to today
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
                      _subjects = _sections.isNotEmpty
                          ? ((_sections.first['subjects'] as List?)
                                    ?.cast<Map<String, dynamic>>() ??
                                [])
                          : [];
                      _selectedSubjectId = _subjects.isNotEmpty
                          ? (_subjects.first['id'] as int)
                          : null;
                    });
                  },
                ),
              ),
              const SizedBox(height: 12),
              // Section selector (2-column)
              InputDecorator(
                decoration: const InputDecoration(labelText: 'Section'),
                child: GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisExtent: 40,
                    crossAxisSpacing: 8,
                    mainAxisSpacing: 8,
                  ),
                  itemCount: _sections.length,
                  itemBuilder: (_, i) {
                    final s = _sections[i];
                    final id = (s['id'] as num?)?.toInt();
                    final selected = id != null && id == _selectedSectionId;
                    return OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        backgroundColor: selected
                            ? Theme.of(
                                context,
                              ).colorScheme.primary.withOpacity(0.1)
                            : null,
                      ),
                      onPressed: () {
                        setState(() {
                          _selectedSectionId = id;
                          _subjects =
                              (s['subjects'] as List?)
                                  ?.cast<Map<String, dynamic>>() ??
                              [];
                          _selectedSubjectId = _subjects.isNotEmpty
                              ? (_subjects.first['id'] as int)
                              : null;
                        });
                      },
                      child: Text((s['name'] ?? 'Section').toString()),
                    );
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
              // Creation date hidden; server sets today
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
                  onPressed: _submitting ? null : _submit,
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
