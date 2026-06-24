import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../data/teacher/teacher_exam_repository.dart';
import '../../../widgets/app_snack.dart';
import 'package:url_launcher/url_launcher.dart';

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
  ConsumerState<TeacherMarkEntryDetailPage> createState() =>
      _TeacherMarkEntryDetailPageState();
}

class _TeacherMarkEntryDetailPageState
    extends ConsumerState<TeacherMarkEntryDetailPage> {
  bool _isLoading = true;
  List<dynamic> _students = [];
  Map<String, dynamic>? _examSubject;
  bool _readOnly = false;
  String? _message;
  int _decimalPosition = 2;
  String? _printBlankUrl;
  String? _printFilledUrl;

  // All FocusNodes for every student × every active field, in order.
  // Rebuilt whenever _students or _examSubject changes.
  List<FocusNode> _allFocusNodes = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    for (final fn in _allFocusNodes) {
      fn.dispose();
    }
    super.dispose();
  }

  /// Builds a flat ordered list of FocusNodes – one per active field per student.
  /// Must be called after _students / _examSubject are set.
  void _rebuildFocusNodes() {
    // Dispose old nodes first
    for (final fn in _allFocusNodes) {
      fn.dispose();
    }

    final hasCreative = ((_examSubject?['creative_full'] ?? 0) as num) > 0;
    final hasMcq = ((_examSubject?['mcq_full'] ?? 0) as num) > 0;
    final hasPractical = ((_examSubject?['practical_full'] ?? 0) as num) > 0;
    final fieldsPerStudent =
        (hasCreative ? 1 : 0) + (hasMcq ? 1 : 0) + (hasPractical ? 1 : 0);

    _allFocusNodes = List.generate(
      _students.length * fieldsPerStudent,
      (_) => FocusNode(),
    );
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
          _decimalPosition = data['decimal_position'] ?? 0;
          _printBlankUrl = data['print_blank_url'];
          _printFilledUrl = data['print_filled_url'];
          _isLoading = false;
          _rebuildFocusNodes();
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'তথ্য লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasCreative = ((_examSubject?['creative_full'] ?? 0) as num) > 0;
    final hasMcq = ((_examSubject?['mcq_full'] ?? 0) as num) > 0;
    final hasPractical = ((_examSubject?['practical_full'] ?? 0) as num) > 0;
    final fieldsPerStudent =
        (hasCreative ? 1 : 0) + (hasMcq ? 1 : 0) + (hasPractical ? 1 : 0);

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
                      style: const TextStyle(
                        color: Colors.red,
                        fontWeight: FontWeight.bold,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                  child: Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _openPrintLink('print-blank'),
                          icon: const Icon(Icons.print_outlined),
                          label: const Text('ফাঁকা শিট'),
                          style: OutlinedButton.styleFrom(
                            side: const BorderSide(color: Colors.blue),
                            padding: const EdgeInsets.symmetric(vertical: 12),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () => _openPrintLink('print-filled'),
                          icon: const Icon(Icons.print),
                          label: const Text('পূরণকৃত শিট'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.blue,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 12),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: RefreshIndicator(
                    onRefresh: _loadData,
                    child: ListView.builder(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                      itemCount: _students.length,
                      itemBuilder: (context, index) {
                        final student = _students[index];

                        // Slice this student's FocusNodes from the flat list
                        final startIdx = index * fieldsPerStudent;
                        final studentNodes =
                            _allFocusNodes.length >= startIdx + fieldsPerStudent
                            ? _allFocusNodes.sublist(
                                startIdx,
                                startIdx + fieldsPerStudent,
                              )
                            : <FocusNode>[];

                        // The *very first* focus node of the NEXT student (null for last student)
                        final nextStudentFirstFocus =
                            (index + 1 < _students.length &&
                                _allFocusNodes.length >
                                    startIdx + fieldsPerStudent)
                            ? _allFocusNodes[startIdx + fieldsPerStudent]
                            : null;

                        return _StudentMarkRow(
                          key: ValueKey(student['student_id']),
                          student: student,
                          examId: widget.examId,
                          examSubjectId: widget.examSubjectId,
                          examSubject: _examSubject!,
                          readOnly: _readOnly,
                          decimalPosition: _decimalPosition,
                          focusNodes: studentNodes,
                          nextStudentFirstFocus: nextStudentFirstFocus,
                        );
                      },
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  void _openPrintLink(String type) {
    final url = type == 'print-blank' ? _printBlankUrl : _printFilledUrl;
    if (url != null) {
      _launchInBrowser(url);
    } else {
      showAppSnack(context, message: 'লিঙ্ক পাওয়া যায়নি');
    }
  }

  Future<void> _launchInBrowser(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      showAppSnack(context, message: 'লিঙ্কটি ওপেন করা সম্ভব হচ্ছে না');
    }
  }
}

class _StudentMarkRow extends StatefulWidget {
  final dynamic student;
  final int examId;
  final int examSubjectId;
  final Map<String, dynamic> examSubject;
  final bool readOnly;
  final int decimalPosition;

  /// Ordered FocusNodes for this student's active fields (creative → mcq → practical).
  final List<FocusNode> focusNodes;

  /// First FocusNode of the NEXT student (null if this is the last student).
  final FocusNode? nextStudentFirstFocus;

  const _StudentMarkRow({
    super.key,
    required this.student,
    required this.examId,
    required this.examSubjectId,
    required this.examSubject,
    required this.readOnly,
    required this.decimalPosition,
    required this.focusNodes,
    required this.nextStudentFirstFocus,
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

  // ─── helpers ──────────────────────────────────────────────────────────────

  bool get _hasCreative => (widget.examSubject['creative_full'] ?? 0) > 0;
  bool get _hasMcq => (widget.examSubject['mcq_full'] ?? 0) > 0;
  bool get _hasPractical => (widget.examSubject['practical_full'] ?? 0) > 0;

  /// Returns the FocusNode at position [i] within this student's nodes (or null).
  FocusNode? _fn(int i) =>
      i < widget.focusNodes.length ? widget.focusNodes[i] : null;

  /// Ordered list of (focusNode, nextFocusNode) for the active fields.
  List<(FocusNode?, FocusNode?)> get _fieldFocusPairs {
    final nodes = <FocusNode?>[];
    if (_hasCreative) nodes.add(_fn(0));
    if (_hasMcq) nodes.add(_fn(nodes.length));
    if (_hasPractical) nodes.add(_fn(nodes.length));

    return List.generate(nodes.length, (i) {
      final current = nodes[i];
      // next = next field within this student, OR first field of next student
      final next = (i + 1 < nodes.length)
          ? nodes[i + 1]
          : widget.nextStudentFirstFocus;
      return (current, next);
    });
  }

  // Focus nodes resolved per field
  FocusNode? get _creativeFocus => _fn(0);
  FocusNode? get _mcqFocus {
    int idx = _hasCreative ? 1 : 0;
    return _fn(idx);
  }

  FocusNode? get _practicalFocus {
    int idx = (_hasCreative ? 1 : 0) + (_hasMcq ? 1 : 0);
    return _fn(idx);
  }

  /// Next focus after creative
  FocusNode? get _afterCreative => _hasMcq
      ? _mcqFocus
      : (_hasPractical ? _practicalFocus : widget.nextStudentFirstFocus);

  /// Next focus after mcq
  FocusNode? get _afterMcq =>
      _hasPractical ? _practicalFocus : widget.nextStudentFirstFocus;

  /// Next focus after practical (last field → next student)
  FocusNode? get _afterPractical => widget.nextStudentFirstFocus;

  // ─── digit-count auto-advance ─────────────────────────────────────────────

  int _digitCount(dynamic value) {
    final n = (value ?? 0).toInt();
    if (n <= 0) return 1;
    return n.toString().length;
  }

  void _autoAdvanceIfNeeded(String value, dynamic maxMark, FocusNode? next) {
    if (next == null) return;
    final max = (maxMark ?? 0) as num;
    if (max <= 0) return;

    // If typing decimals, do not auto-advance as length varies
    if (value.contains('.')) return;

    final current = double.tryParse(value);
    if (current == null || value.trim().isEmpty) return;

    // Rule 1: If current value matches or exceeds max mark, it's definitely done
    if (current >= max) {
      next.requestFocus();
      return;
    }

    // Rule 2: If we are not using decimals, and adding even one more digit
    // would exceed the max mark, then we can safely advance.
    // e.g., if max is 100 and current is 9, next value would be 90 (which is <=100), so STAY.
    // e.g., if max is 100 and current is 11, next value would be 110 (which is >100), so JUMP.
    if (widget.decimalPosition == 0) {
      if (current * 10 > max) {
        next.requestFocus();
      }
    }
  }

  // ─── lifecycle ────────────────────────────────────────────────────────────

  @override
  void initState() {
    super.initState();
    final mark = widget.student['mark'];
    _creativeController = TextEditingController(
      text: mark?['creative']?.toString() ?? '',
    );
    _mcqController = TextEditingController(
      text: mark?['mcq']?.toString() ?? '',
    );
    _practicalController = TextEditingController(
      text: mark?['practical']?.toString() ?? '',
    );
    _isAbsent = mark?['is_absent'] ?? false;
    _letterGrade = mark?['letter_grade'];
    _totalMarks = mark?['total'] != null
        ? double.tryParse(mark!['total'].toString())
        : null;
  }

  @override
  void dispose() {
    _creativeController.dispose();
    _mcqController.dispose();
    _practicalController.dispose();
    _debounce?.cancel();
    // FocusNodes are owned by the parent – do NOT dispose them here.
    super.dispose();
  }

  // ─── save logic ───────────────────────────────────────────────────────────

  void _onChanged() {
    if (widget.readOnly) return;

    if (_debounce?.isActive ?? false) _debounce!.cancel();
    _debounce = Timer(const Duration(milliseconds: 800), () {
      final cr = double.tryParse(_creativeController.text) ?? 0;
      final mq = double.tryParse(_mcqController.text) ?? 0;
      final pr = double.tryParse(_practicalController.text) ?? 0;

      if (cr > (widget.examSubject['creative_full'] ?? 0)) {
        _showErrorDialog(
          'সৃজনশীল',
          widget.examSubject['creative_full'],
          _creativeController,
        );
        return;
      }
      if (mq > (widget.examSubject['mcq_full'] ?? 0)) {
        _showErrorDialog('MCQ', widget.examSubject['mcq_full'], _mcqController);
        return;
      }
      if (pr > (widget.examSubject['practical_full'] ?? 0)) {
        _showErrorDialog(
          'ব্যবহারিক',
          widget.examSubject['practical_full'],
          _practicalController,
        );
        return;
      }

      _save();
    });
  }

  void _showErrorDialog(
    String label,
    dynamic max,
    TextEditingController controller,
  ) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text(
          'ভুল ইনপুট!',
          style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold),
        ),
        content: Text(
          '$label নম্বর $max এর বেশি হতে পারবে না। অনুগ্রহ করে সঠিক নম্বর দিন।',
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              controller.clear();
              setState(() {});
            },
            child: const Text('ঠিক আছে'),
          ),
        ],
      ),
    );
  }

  Future<void> _save() async {
    if (widget.readOnly) return;

    final cr = double.tryParse(_creativeController.text) ?? 0;
    final mq = double.tryParse(_mcqController.text) ?? 0;
    final pr = double.tryParse(_practicalController.text) ?? 0;

    if (cr > (widget.examSubject['creative_full'] ?? 0) ||
        mq > (widget.examSubject['mcq_full'] ?? 0) ||
        pr > (widget.examSubject['practical_full'] ?? 0)) {
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
          _totalMarks = result['total_marks'] != null
              ? double.tryParse(result['total_marks'].toString())
              : null;
        });
      }
    } catch (_) {
      // quiet fail
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  // ─── build ────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── student header ──
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade700,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    children: [
                      const Text(
                        'ROLL',
                        style: TextStyle(
                          color: Colors.white70,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        '${widget.student['roll']}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 18,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              widget.student['student_name'] ?? 'N/A',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Text(
                            'Sec: ${widget.student['section']}',
                            style: TextStyle(
                              color: Colors.grey.shade700,
                              fontSize: 13,
                            ),
                          ),
                          if (_totalMarks != null) ...[
                            const SizedBox(width: 12),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 2,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.blue.shade50,
                                borderRadius: BorderRadius.circular(4),
                                border: Border.all(color: Colors.blue.shade200),
                              ),
                              child: Row(
                                children: [
                                  Text(
                                    'Total: ${_totalMarks!.toStringAsFixed(widget.decimalPosition)}',
                                    style: TextStyle(
                                      color: Colors.blue.shade900,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 13,
                                    ),
                                  ),
                                  if (_letterGrade != null) ...[
                                    Container(
                                      margin: const EdgeInsets.only(left: 8),
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 4,
                                        vertical: 0,
                                      ),
                                      decoration: BoxDecoration(
                                        color: _letterGrade == 'F'
                                            ? Colors.red
                                            : Colors.green,
                                        borderRadius: BorderRadius.circular(2),
                                      ),
                                      child: Text(
                                        _letterGrade!,
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 11,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
                Column(
                  children: [
                    const Text('Absent', style: TextStyle(fontSize: 12)),
                    SizedBox(
                      height: 30,
                      child: Switch(
                        value: _isAbsent,
                        activeThumbColor: Colors.red,
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
                    ),
                  ],
                ),
                if (_isSaving)
                  const Padding(
                    padding: EdgeInsets.only(left: 8.0, top: 10),
                    child: SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                  ),
              ],
            ),
            const Divider(),
            // ── mark fields ──
            if (!_isAbsent)
              Row(
                children: [
                  if (_hasCreative)
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(right: 8.0),
                        child: _buildField(
                          'সৃজনশীল',
                          _creativeController,
                          widget.examSubject['creative_full'],
                          _creativeFocus,
                          _afterCreative,
                        ),
                      ),
                    ),
                  if (_hasMcq)
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(right: 8.0),
                        child: _buildField(
                          'MCQ',
                          _mcqController,
                          widget.examSubject['mcq_full'],
                          _mcqFocus,
                          _afterMcq,
                        ),
                      ),
                    ),
                  if (_hasPractical)
                    Expanded(
                      child: _buildField(
                        'ব্যবহারিক',
                        _practicalController,
                        widget.examSubject['practical_full'],
                        _practicalFocus,
                        _afterPractical,
                      ),
                    ),
                ],
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildField(
    String label,
    TextEditingController controller,
    dynamic fullMark,
    FocusNode? focusNode,
    FocusNode? nextFocusNode,
  ) {
    final val = double.tryParse(controller.text) ?? 0;
    final isError = val > (fullMark ?? 0);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          '$label (সর্বোচ্চ: $fullMark)',
          style: const TextStyle(fontSize: 10, color: Colors.grey),
        ),
        const SizedBox(height: 4),
        TextField(
          controller: controller,
          focusNode: focusNode,
          enabled: !widget.readOnly,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          textInputAction: nextFocusNode != null
              ? TextInputAction.next
              : TextInputAction.done,
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
              borderSide: BorderSide(
                color: isError ? Colors.red : Colors.blue,
                width: 2,
              ),
            ),
            contentPadding: const EdgeInsets.symmetric(
              vertical: 8,
              horizontal: 8,
            ),
          ),
          onChanged: (v) {
            setState(() {}); // rebuild for error-border
            _onChanged();
            // Auto-advance when digit count matches max-mark's digit length
            _autoAdvanceIfNeeded(v, fullMark, nextFocusNode);
          },
          onSubmitted: (_) {
            // Keyboard "Next" / "Done" button also advances
            nextFocusNode?.requestFocus();
          },
        ),
      ],
    );
  }
}
