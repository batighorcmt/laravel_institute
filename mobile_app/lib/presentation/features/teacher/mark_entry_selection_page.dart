import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../data/teacher/teacher_exam_repository.dart';
import '../../../widgets/app_snack.dart';
import 'teacher_mark_entry_detail_page.dart';

class MarkEntrySelectionPage extends ConsumerStatefulWidget {
  const MarkEntrySelectionPage({super.key});

  @override
  ConsumerState<MarkEntrySelectionPage> createState() => _MarkEntrySelectionPageState();
}

class _MarkEntrySelectionPageState extends ConsumerState<MarkEntrySelectionPage> {
  bool _isLoading = true;
  List<dynamic> _years = [];
  List<dynamic> _classes = [];
  List<dynamic> _exams = [];
  List<dynamic> _subjects = [];

  int? _selectedYearId;
  int? _selectedClassId;
  int? _selectedExamId;
  int? _selectedSubjectId;
  int? _examSubjectId;

  @override
  void initState() {
    super.initState();
    _loadMeta();
  }

  Future<void> _loadMeta() async {
    try {
      final repo = TeacherExamRepository();
      final meta = await repo.getMarkEntryMeta();
      if (mounted) {
        setState(() {
          _years = meta['academic_year_id'] ?? meta['academic_years'] ?? [];
          _classes = meta['classes'] ?? [];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'ফাইল লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  Future<void> _loadExams(int yearId) async {
    setState(() {
      _isLoading = true;
      _exams = [];
      _selectedExamId = null;
      _subjects = [];
      _selectedSubjectId = null;
    });
    try {
      final repo = TeacherExamRepository();
      final exams = await repo.getExams(yearId, status: 'active');
      if (mounted) {
        setState(() {
          _exams = exams;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'পরীক্ষা লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  Future<void> _loadSubjects(int examId) async {
    setState(() {
      _isLoading = true;
      _subjects = [];
      _selectedSubjectId = null;
    });
    try {
      final repo = TeacherExamRepository();
      final subjects = await repo.getSubjects(examId);
      if (mounted) {
        setState(() {
          _subjects = subjects;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'বিষয় লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  void _triggerExamLoad() {
    if (_selectedYearId != null && _selectedClassId != null) {
      _loadExams(_selectedYearId!, _selectedClassId!);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('নম্বর এন্ট্রি')),
      body: _isLoading && _years.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text('শিক্ষাবর্ষ', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<int>(
                    value: _selectedYearId,
                    items: _years.map((y) => DropdownMenuItem<int>(
                      value: y['id'],
                      child: Text(y['name']),
                    )).toList(),
                    onChanged: (v) {
                      if (v != null) {
                        setState(() {
                          _selectedYearId = v;
                          _exams = [];
                          _selectedExamId = null;
                        });
                        _triggerExamLoad();
                      }
                    },
                    decoration: const InputDecoration(border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 12)),
                  ),
                  const SizedBox(height: 16),

                  const Text('শ্রেণী', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<int>(
                    value: _selectedClassId,
                    items: _classes.map((c) => DropdownMenuItem<int>(
                      value: c['id'],
                      child: Text(c['name']),
                    )).toList(),
                    onChanged: (v) {
                      if (v != null) {
                        setState(() {
                          _selectedClassId = v;
                          _exams = [];
                          _selectedExamId = null;
                        });
                        _triggerExamLoad();
                      }
                    },
                    decoration: const InputDecoration(border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 12)),
                  ),
                  const SizedBox(height: 16),

                  const Text('পরীক্ষা', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<int>(
                    value: _selectedExamId,
                    items: _exams.map((e) => DropdownMenuItem<int>(
                      value: e['id'],
                      child: Text(e['name']),
                    )).toList(),
                    onChanged: (v) {
                      if (v != null) {
                        setState(() => _selectedExamId = v);
                        _loadSubjects(v);
                      }
                    },
                    decoration: const InputDecoration(border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 12)),
                  ),
                  const SizedBox(height: 16),

                  const Text('বিষয়', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<int>(
                    value: _selectedSubjectId,
                    items: _subjects.map((s) => DropdownMenuItem<int>(
                      value: s['id'],
                      child: Text(s['name']),
                    )).toList(),
                    onChanged: (v) {
                      if (v!=null) {
                        setState(() {
                          _selectedSubjectId = v;
                          final s = _subjects.firstWhere((element) => element['id'] == v);
                          _examSubjectId = s['exam_subject_id'];
                        });
                      }
                    },
                    decoration: const InputDecoration(border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 12)),
                  ),
                  const SizedBox(height: 24),

                  ElevatedButton(
                    onPressed: (_selectedYearId != null && _selectedClassId != null && _selectedExamId != null && _selectedSubjectId != null)
                        ? () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => TeacherMarkEntryDetailPage(
                                  examId: _selectedExamId!,
                                  subjectId: _selectedSubjectId!,
                                  classId: _selectedClassId!,
                                  examSubjectId: _examSubjectId!,
                                  subjectName: _subjects.firstWhere((s) => s['id'] == _selectedSubjectId)['name'],
                                ),
                              ),
                            );
                          }
                        : null,
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.blue, foregroundColor: Colors.white, minimumSize: const Size(double.infinity, 50)),
                    child: const Text('শিক্ষার্থী তালিকা দেখুন'),
                  ),
                ],
              ),
            ),
    );
  }
}
