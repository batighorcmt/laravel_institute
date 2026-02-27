import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../data/teacher/teacher_exam_repository.dart';
import '../../../widgets/app_snack.dart';

class TeacherMarkEntryDetailPage extends ConsumerStatefulWidget {
  final int examId;
  final int subjectId;
  final int classId;
  final int examSubjectId;
  final String subjectName;

  const TeacherMarkEntryDetailPage({
    super.key,
    required this.examId,
    required this.subjectId,
    required this.classId,
    required this.examSubjectId,
    required this.subjectName,
  });

  @override
  ConsumerState<TeacherMarkEntryDetailPage> createState() => _TeacherMarkEntryDetailPageState();
}

class _TeacherMarkEntryDetailPageState extends ConsumerState<TeacherMarkEntryDetailPage> {
  bool _isLoading = true;
  List<dynamic> _students = [];
  Map<String, dynamic>? _examSubject;
  bool _readOnly = false;
  String? _message;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final repo = TeacherExamRepository();
      final data = await repo.getStudentsForMarkEntry(
        examId: widget.examId,
        subjectId: widget.subjectId,
        classId: widget.classId,
      );
      if (mounted) {
        setState(() {
          _students = data['students'] ?? [];
          _examSubject = data['exam_subject'];
          _readOnly = data['read_only'] ?? false;
          _message = data['message'];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'তথ্য লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.subjectName)),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                if (_message != null)
                  Container(
                    width: double.infinity,
                    color: Colors.red.shade100,
                    padding: const EdgeInsets.all(12),
                    child: Text(
                      _message!,
                      style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold),
                      textAlign: TextAlign.center,
                    ),
                  ),
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _students.length,
                    itemBuilder: (context, index) {
                      final student = _students[index];
                      return _StudentMarkRow(
                        student: student,
                        examId: widget.examId,
                        examSubjectId: widget.examSubjectId,
                        examSubject: _examSubject!,
                        readOnly: _readOnly,
                      );
                    },
                  ),
                ),
              ],
            ),
    );
  }
}

class _StudentMarkRow extends StatefulWidget {
  final dynamic student;
  final int examId;
  final int examSubjectId;
  final Map<String, dynamic> examSubject;
  final bool readOnly;

  const _StudentMarkRow({
    required this.student,
    required this.examId,
    required this.examSubjectId,
    required this.examSubject,
    required this.readOnly,
  });

  @override
  State<_StudentMarkRow> createState() => _StudentMarkRowState();
}

class _StudentMarkRowState extends State<_StudentMarkRow> {
  late TextEditingController _creativeController;
  late TextEditingController _mcqController;
  late TextEditingController _practicalController;
  late bool _isAbsent;
  Timer? _debounce;
  bool _isSaving = false;

  String? _letterGrade;
  double? _totalMarks;

  @override
  void initState() {
    super.initState();
    final mark = widget.student['mark'];
    _creativeController = TextEditingController(text: mark?['creative']?.toString() ?? '');
    _mcqController = TextEditingController(text: mark?['mcq']?.toString() ?? '');
    _practicalController = TextEditingController(text: mark?['practical']?.toString() ?? '');
    _isAbsent = mark?['is_absent'] ?? false;
    _letterGrade = mark?['letter_grade'];
    _totalMarks = mark?['total'] != null ? double.tryParse(mark!['total'].toString()) : null;
  }

  @override
  void dispose() {
    _creativeController.dispose();
    _mcqController.dispose();
    _practicalController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  void _onChanged() {
    if (widget.readOnly) return;
    
    // Quick validation
    final cr = double.tryParse(_creativeController.text) ?? 0;
    final mq = double.tryParse(_mcqController.text) ?? 0;
    final pr = double.tryParse(_practicalController.text) ?? 0;

    if (cr > (widget.examSubject['creative_full'] ?? 0) ||
        mq > (widget.examSubject['mcq_full'] ?? 0) ||
        pr > (widget.examSubject['practical_full'] ?? 0)) {
       // Maybe show a warning or just cap it. For now let's just let it be, but highlight error.
    }

    if (_debounce?.isActive ?? false) _debounce!.cancel();
    _debounce = Timer(const Duration(milliseconds: 800), () {
      _save();
    });
  }

  Future<void> _save() async {
    if (widget.readOnly) return;

    // Validation
    final cr = double.tryParse(_creativeController.text) ?? 0;
    final mq = double.tryParse(_mcqController.text) ?? 0;
    final pr = double.tryParse(_practicalController.text) ?? 0;

    if (cr > (widget.examSubject['creative_full'] ?? 0) ||
        mq > (widget.examSubject['mcq_full'] ?? 0) ||
        pr > (widget.examSubject['practical_full'] ?? 0)) {
       // Do not save invalid marks
       return;
    }

    setState(() => _isSaving = true);
    try {
      final repo = TeacherExamRepository();
      final result = await repo.saveMarkResult(
        examId: widget.examId,
        examSubjectId: widget.examSubjectId,
        studentId: widget.student['student_id'],
        creative: double.tryParse(_creativeController.text),
        mcq: double.tryParse(_mcqController.text),
        practical: double.tryParse(_practicalController.text),
        isAbsent: _isAbsent,
      );
      if (mounted && result != null) {
        setState(() {
          _letterGrade = result['letter_grade'];
          _totalMarks = result['total_marks'] != null ? double.tryParse(result['total_marks'].toString()) : null;
        });
      }
    } catch (e) {
      // quiet fail
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasCreative = (widget.examSubject['creative_full'] ?? 0) > 0;
    final hasMcq = (widget.examSubject['mcq_full'] ?? 0) > 0;
    final hasPractical = (widget.examSubject['practical_full'] ?? 0) > 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            widget.student['student_name'] ?? 'N/A',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                          if (_letterGrade != null) ...[
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: _letterGrade == 'F' ? Colors.red : Colors.green,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                _letterGrade!,
                                style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ],
                      ),
                      RichText(
                        text: TextSpan(
                          style: TextStyle(color: Theme.of(context).colorScheme.onSurface, fontSize: 13),
                          children: [
                            const TextSpan(text: 'Roll: '),
                            TextSpan(
                              text: '${widget.student['roll']}',
                              style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.blue),
                            ),
                            TextSpan(text: ' | Sec: ${widget.student['section']}'),
                            if (_totalMarks != null) 
                              TextSpan(
                                text: ' | Total: $_totalMarks',
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                Column(
                  children: [
                    const Text('Absent', style: TextStyle(fontSize: 12)),
                    Switch(
                      value: _isAbsent,
                      onChanged: widget.readOnly
                          ? null
                          : (v) {
                              setState(() {
                                _isAbsent = v;
                                if (v) {
                                  _creativeController.clear();
                                  _mcqController.clear();
                                  _practicalController.clear();
                                }
                              });
                              _save();
                            },
                    ),
                  ],
                ),
                if (_isSaving)
                  const Padding(
                    padding: EdgeInsets.only(left: 8.0),
                    child: SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)),
                  ),
              ],
            ),
            const Divider(),
            if (!_isAbsent)
              Row(
                children: [
                  if (hasCreative)
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(right: 8.0),
                        child: _buildField('Creative', _creativeController, widget.examSubject['creative_full']),
                      ),
                    ),
                  if (hasMcq)
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(right: 8.0),
                        child: _buildField('MCQ', _mcqController, widget.examSubject['mcq_full']),
                      ),
                    ),
                  if (hasPractical)
                    Expanded(
                      child: _buildField('Practical', _practicalController, widget.examSubject['practical_full']),
                    ),
                ],
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildField(String label, TextEditingController controller, dynamic fullMark) {
    final val = double.tryParse(controller.text) ?? 0;
    final isError = val > (fullMark ?? 0);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('$label (Max: $fullMark)', style: const TextStyle(fontSize: 10, color: Colors.grey)),
        const SizedBox(height: 4),
        TextField(
          controller: controller,
          enabled: !widget.readOnly,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: InputDecoration(
            isDense: true,
            hintText: '0',
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: BorderSide(color: isError ? Colors.red : Colors.grey),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: BorderSide(color: isError ? Colors.red : Colors.grey),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: BorderSide(color: isError ? Colors.red : Colors.blue, width: 2),
            ),
            contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
          ),
          onChanged: (v) {
            setState(() {}); // trigger rebuild for isError cue
            _onChanged();
          },
        ),
      ],
    );
  }
}
