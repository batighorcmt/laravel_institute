// dart:io removed (no longer needed after simplifying download logic)
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/config/env.dart';

final parentExamResultDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, String>((ref, examId) async {
      final dio = DioClient().dio;
      final res = await dio.get('parent/exams/$examId/results');
      return res.data as Map<String, dynamic>;
    });

class ParentExamResultDetailPage extends ConsumerStatefulWidget {
  final String examId;
  const ParentExamResultDetailPage({super.key, required this.examId});

  @override
  ConsumerState<ParentExamResultDetailPage> createState() =>
      _ParentExamResultDetailPageState();
}

class _ParentExamResultDetailPageState
    extends ConsumerState<ParentExamResultDetailPage> {
  bool _isDownloading = false;

  // Downloads marksheet PDF from the dedicated API endpoint
  Future<void> _downloadMarksheet(String marksheetUrl) async {
    if (_isDownloading) return;
    setState(() => _isDownloading = true);

    try {
      final token = await const FlutterSecureStorage().read(
        key: 'auth_token',
      );
      if (token == null) throw 'লগইন তথ্য পাওয়া যায়নি';

      final baseUrl = Env.apiBaseUrl.endsWith('/')
          ? Env.apiBaseUrl
          : '${Env.apiBaseUrl}/';

      // Use the provided URL from API if available, otherwise fallback to standard route
      String pdfUrl = marksheetUrl;
      if (pdfUrl.isEmpty) {
        pdfUrl = '${baseUrl}parent/exams/${widget.examId}/marksheet';
      }

      // Ensure the URL is absolute
      if (!pdfUrl.startsWith('http')) {
        pdfUrl = baseUrl + pdfUrl.replaceFirst('api/', '');
      }

      final dir = await getApplicationDocumentsDirectory();
      final savePath =
          '${dir.path}/Marksheet-${widget.examId}-${DateTime.now().millisecondsSinceEpoch}.pdf';

      final downloadDio = Dio();
      await downloadDio.download(
        pdfUrl,
        savePath,
        options: Options(
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/pdf',
          },
          responseType: ResponseType.bytes,
        ),
      );

      final result = await OpenFilex.open(savePath);
      if (result.type != ResultType.done) {
        throw 'ফাইল খুলতে পারছে না: ${result.message}';
      }
    } catch (e) {
      String msg = e.toString();
      if (e is DioException) {
        msg = e.response?.data?['message'] ?? 'সার্ভারের সাথে সংযোগ হচ্ছে না।';
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('মার্কশিট ডাউনলোড ব্যর্থ: $msg'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isDownloading = false);
    }
  }

  String _formatGp(dynamic gp) {
    final val = (gp is num) ? gp.toDouble() : double.tryParse('$gp') ?? 0.0;
    return val.toStringAsFixed(2);
  }

  Color _gradeColor(String grade) {
    switch (grade) {
      case 'A+':
        return const Color(0xFF1B8A5A);
      case 'A':
        return const Color(0xFF2E7D32);
      case 'A-':
        return const Color(0xFF558B2F);
      case 'B':
        return const Color(0xFF1565C0);
      case 'C':
        return const Color(0xFF6A1B9A);
      case 'D':
        return const Color(0xFFE65100);
      default:
        return const Color(0xFFC62828);
    }
  }

  @override
  Widget build(BuildContext context) {
    final resultAsync = ref.watch(
      parentExamResultDetailProvider(widget.examId),
    );

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        title: const Text('পরীক্ষার ফলাফল'),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black87,
        elevation: 0.5,
        actions: [
          if (resultAsync.hasValue)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: TextButton.icon(
                onPressed: _isDownloading
                    ? null
                    : () => _downloadMarksheet(
                        resultAsync.value!['marksheet_url'] ?? '',
                      ),
                icon: _isDownloading
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.picture_as_pdf, size: 18),
                label: Text(_isDownloading ? 'ডাউনলোড...' : 'মার্কশিট'),
              ),
            ),
        ],
      ),
      body: Stack(
        children: [
          resultAsync.when(
            data: (data) {
              final summary = data['summary'] as Map<String, dynamic>;
              final subjects = data['subjects'] as List<dynamic>;
              final exam = data['exam'] as Map<String, dynamic>;
              final grade = summary['total_grade']?.toString() ?? 'F';
              final isFailed = grade == 'F';

              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _buildSummaryCard(exam, summary, grade, isFailed),
                  const SizedBox(height: 16),
                  const Padding(
                    padding: EdgeInsets.only(left: 4, bottom: 10),
                    child: Text(
                      'বিষয়ভিত্তিক ফলাফল',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF37474F),
                      ),
                    ),
                  ),
                  ..._buildSubjectList(subjects),
                  const SizedBox(height: 80),
                ],
              );
            },
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (err, _) => Center(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.error_outline,
                      size: 56,
                      color: Colors.red.shade300,
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'ফলাফল লোড করা যায়নি।',
                      style: TextStyle(fontSize: 16, color: Colors.black54),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: () => ref.invalidate(
                        parentExamResultDetailProvider(widget.examId),
                      ),
                      child: const Text('পুনরায় চেষ্টা করুন'),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // Download overlay
          if (_isDownloading)
            Container(
              color: Colors.black26,
              child: const Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CircularProgressIndicator(color: Colors.white),
                    SizedBox(height: 12),
                    Text(
                      'মার্কশিট ডাউনলোড হচ্ছে...',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  // ── Summary Card ──
  Widget _buildSummaryCard(
    Map<String, dynamic> exam,
    Map<String, dynamic> summary,
    String grade,
    bool isFailed,
  ) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isFailed
              ? [const Color(0xFFB71C1C), const Color(0xFFE53935)]
              : [const Color(0xFF1565C0), const Color(0xFF1976D2)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: (isFailed ? Colors.red : Colors.blue).withValues(alpha: 0.3),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            Text(
              exam['name'] ?? '',
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _summaryPill('প্রাপ্ত নম্বর', '${summary['total_marks'] ?? 0}'),
                Container(width: 1, height: 40, color: Colors.white30),
                _summaryPill(
                  'গ্রেড পয়েন্ট',
                  _formatGp(summary['total_gpa'] ?? 0),
                ),
                Container(width: 1, height: 40, color: Colors.white30),
                _summaryPill(
                  'গ্রেড',
                  summary['total_grade']?.toString() ?? 'F',
                ),
                if (summary['position'] != null &&
                    summary['position'].toString() != '-') ...[
                  Container(width: 1, height: 40, color: Colors.white30),
                  _summaryPill('অবস্থান', '${summary['position']}'),
                ],
              ],
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.18),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                summary['status']?.toString() ?? '',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _summaryPill(String label, String value) {
    return Column(
      children: [
        Text(
          value,
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: Colors.white.withValues(alpha: 0.8),
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  // ── Subject list builder ──
  List<Widget> _buildSubjectList(List<dynamic> subjects) {
    final widgets = <Widget>[];
    for (final s in subjects) {
      final subject = s as Map<String, dynamic>;
      final type = subject['type'] as String? ?? 'single';
      final isFailed = subject['is_failed'] == true;
      final gradeColor = _gradeColor(
        subject['letter_grade']?.toString() ?? 'F',
      );
      if (type == 'display_only') {
        widgets.add(_buildDisplayOnlyRow(subject, gradeColor));
      } else if (type == 'combined') {
        widgets.add(_buildCombinedRow(subject, gradeColor, isFailed));
      } else {
        widgets.add(_buildSingleSubjectCard(subject, gradeColor, isFailed));
      }
    }
    return widgets;
  }

  // ── Normal subject card ──
  Widget _buildSingleSubjectCard(
    Map<String, dynamic> subject,
    Color gradeColor,
    bool isFailed,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isFailed ? Colors.red.shade100 : Colors.transparent,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Row(
                    children: [
                      if (subject['is_optional'] == true)
                        Container(
                          margin: const EdgeInsets.only(right: 6),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 6,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.amber.shade100,
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: const Text(
                            'ঐচ্ছিক',
                            style: TextStyle(
                              fontSize: 10,
                              color: Colors.orange,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      Expanded(
                        child: Text(
                          subject['name']?.toString() ?? '',
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 15,
                            color: Color(0xFF263238),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                _gradeBadge(
                  subject['letter_grade']?.toString() ?? 'F',
                  subject['grade_point'] ?? 0,
                  gradeColor,
                ),
              ],
            ),
            const SizedBox(height: 12),
            _buildMarksBreakdown(subject),
            const SizedBox(height: 10),
            _buildTotalRow(subject, isFailed),
          ],
        ),
      ),
    );
  }

  // ── display_only sub-row ──
  Widget _buildDisplayOnlyRow(Map<String, dynamic> subject, Color gradeColor) {
    return Container(
      margin: const EdgeInsets.only(bottom: 4, left: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFF9F9FF),
        border: Border(
          left: BorderSide(color: gradeColor.withValues(alpha: 0.4), width: 3),
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        child: Row(
          children: [
            Expanded(
              child: Text(
                subject['name']?.toString() ?? '',
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF546E7A),
                ),
              ),
            ),
            const SizedBox(width: 8),
            _buildMarksBreakdown(subject),
            const SizedBox(width: 12),
            Text(
              '${subject['total_marks'] ?? 0}/${subject['full_marks'] ?? 0}',
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 13,
                color: Color(0xFF37474F),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Combined/merged row ──
  Widget _buildCombinedRow(
    Map<String, dynamic> subject,
    Color gradeColor,
    bool isFailed,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12, left: 12),
      decoration: BoxDecoration(
        color: isFailed
            ? Colors.red.shade50
            : gradeColor.withValues(alpha: 0.07),
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(10),
          bottomRight: Radius.circular(10),
          topRight: Radius.circular(10),
        ),
        border: Border.all(
          color: isFailed
              ? Colors.red.shade200
              : gradeColor.withValues(alpha: 0.3),
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Icon(Icons.merge_type, size: 16, color: gradeColor),
                const SizedBox(width: 6),
                Text(
                  subject['name']?.toString() ?? '',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                    color: gradeColor,
                  ),
                ),
              ],
            ),
            Row(
              children: [
                Text(
                  'মোট: ${subject['total_marks'] ?? 0}/${subject['full_marks'] ?? 0}',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                    color: isFailed
                        ? Colors.red.shade700
                        : const Color(0xFF37474F),
                  ),
                ),
                const SizedBox(width: 10),
                _gradeBadge(
                  subject['letter_grade']?.toString() ?? 'F',
                  subject['grade_point'] ?? 0,
                  gradeColor,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  // ── Grade badge ──
  Widget _gradeBadge(String grade, dynamic gp, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Column(
        children: [
          Text(
            grade,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 16,
              color: color,
            ),
          ),
          Text(
            'GP: ${_formatGp(gp)}',
            style: TextStyle(
              fontSize: 10,
              color: color.withValues(alpha: 0.8),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  // ── Marks breakdown chips ──
  Widget _buildMarksBreakdown(Map<String, dynamic> subject) {
    final creative = (subject['creative_full_mark'] ?? 0) as num;
    final mcq = (subject['mcq_full_mark'] ?? 0) as num;
    final practical = (subject['practical_full_mark'] ?? 0) as num;

    final chips = <Widget>[];
    if (creative > 0) {
      chips.add(
        _markChip(
          label: 'সৃজনশীল',
          got: subject['creative_marks'] ?? 0,
          full: creative,
          color: const Color(0xFF1565C0),
        ),
      );
    }
    if (mcq > 0) {
      chips.add(
        _markChip(
          label: 'বহুনির্বাচনী',
          got: subject['mcq_marks'] ?? 0,
          full: mcq,
          color: const Color(0xFF6A1B9A),
        ),
      );
    }
    if (practical > 0) {
      chips.add(
        _markChip(
          label: 'ব্যবহারিক',
          got: subject['practical_marks'] ?? 0,
          full: practical,
          color: const Color(0xFF00838F),
        ),
      );
    }

    if (chips.isEmpty) return const SizedBox.shrink();
    return Wrap(spacing: 8, runSpacing: 6, children: chips);
  }

  Widget _markChip({
    required String label,
    required dynamic got,
    required num full,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: RichText(
        text: TextSpan(
          style: TextStyle(fontSize: 12, color: color),
          children: [
            TextSpan(
              text: label,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            const TextSpan(text: ': '),
            TextSpan(
              text: '$got',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            TextSpan(text: '/$full'),
          ],
        ),
      ),
    );
  }

  // ── Total marks progress bar ──
  Widget _buildTotalRow(Map<String, dynamic> subject, bool isFailed) {
    final total = (subject['total_marks'] ?? 0) as num;
    final full = (subject['full_marks'] ?? 0) as num;
    final percent = (full > 0) ? (total / full).clamp(0, 1).toDouble() : 0.0;
    final barColor = isFailed ? Colors.red : const Color(0xFF1565C0);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'মোট: $total / $full',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 13,
                color: isFailed ? Colors.red.shade700 : const Color(0xFF263238),
              ),
            ),
            if (subject['is_absent'] == true)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.orange.shade100,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: const Text(
                  'অনুপস্থিত',
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.deepOrange,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 6),
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: LinearProgressIndicator(
            value: percent,
            minHeight: 6,
            backgroundColor: Colors.grey.shade200,
            valueColor: AlwaysStoppedAnimation<Color>(barColor),
          ),
        ),
      ],
    );
  }
}
