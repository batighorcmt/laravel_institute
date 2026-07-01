import 'package:flutter/material.dart';
import '../../../../data/teacher/teacher_exam_repository.dart';
import '../../../../widgets/app_snack.dart';

class ExamAttendanceReportPage extends StatefulWidget {
  const ExamAttendanceReportPage({super.key});

  @override
  State<ExamAttendanceReportPage> createState() => _ExamAttendanceReportPageState();
}

class _ExamAttendanceReportPageState extends State<ExamAttendanceReportPage> {
  final TeacherExamRepository _repo = TeacherExamRepository();
  bool _isLoadingMeta = true;
  bool _isLoadingData = false;

  List<dynamic> _plans = [];
  List<String> _availableDates = [];

  int? _selectedPlanId;
  String? _selectedDate;

  List<dynamic> _rows = [];
  List<dynamic> _overallClasses = [];
  List<dynamic> _absentStudents = [];

  @override
  void initState() {
    super.initState();
    _loadInitialMeta();
  }

  Future<void> _loadInitialMeta() async {
    try {
      final meta = await _repo.getDutyMeta();
      if (mounted) {
        setState(() {
          _plans = meta['plans'] ?? [];
        });
      }
    } catch (_) {}
    setState(() => _isLoadingMeta = false);
  }

  Future<void> _loadDatesForPlan(int planId) async {
    try {
      final meta = await _repo.getDutyMeta(planId: planId);
      if (mounted) {
        setState(() {
          _availableDates = List<String>.from(meta['dates'] ?? []);
          _selectedDate = null;
          _rows = [];
          _overallClasses = [];
          _absentStudents = [];
        });
      }
    } catch (_) {}
  }

  Future<void> _loadReport() async {
    if (_selectedPlanId == null || _selectedDate == null) return;
    setState(() => _isLoadingData = true);
    try {
      final data = await _repo.getAttendanceReport(_selectedPlanId, _selectedDate);
      if (mounted) {
        setState(() {
          _rows = data['rows'] ?? [];
          _overallClasses = data['overall_classes'] ?? [];
          _absentStudents = data['absent_students'] ?? [];
          _isLoadingData = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingData = false);
        showAppSnack(context, message: 'রিপোর্ট লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        title: const Text('Attendance Report', style: TextStyle(color: Colors.black87)),
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black87,
        iconTheme: const IconThemeData(color: Colors.black87),
      ),
      body: _isLoadingMeta
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                _buildFilters(),
                Expanded(
                  child: _isLoadingData
                      ? const Center(child: CircularProgressIndicator())
                      : _rows.isEmpty && _selectedDate != null
                          ? _buildEmptyState()
                          : _buildReportContent(),
                ),
              ],
            ),
    );
  }

  Widget _buildFilters() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            offset: const Offset(0, 4),
            blurRadius: 10,
          ),
        ],
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.indigo.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.indigo.shade100),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<int>(
                isExpanded: true,
                value: _selectedPlanId,
                hint: const Text('সীট প্ল্যান নির্বাচন করুন'),
                icon: const Icon(Icons.keyboard_arrow_down, color: Colors.indigo),
                items: _plans.map<DropdownMenuItem<int>>((p) {
                  return DropdownMenuItem<int>(
                    value: p['id'],
                    child: Text(
                      p['name'],
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                  );
                }).toList(),
                onChanged: (val) {
                  if (val != null) {
                    setState(() {
                      _selectedPlanId = val;
                      _rows = [];
                      _overallClasses = [];
                      _absentStudents = [];
                    });
                    _loadDatesForPlan(val);
                  }
                },
              ),
            ),
          ),
          if (_availableDates.isNotEmpty) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.teal.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.teal.shade100),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<String>(
                  isExpanded: true,
                  value: _selectedDate,
                  hint: const Text('তারিখ নির্বাচন করুন'),
                  icon: const Icon(Icons.calendar_today, size: 20, color: Colors.teal),
                  items: _availableDates.map<DropdownMenuItem<String>>((d) {
                    return DropdownMenuItem<String>(
                      value: d,
                      child: Text(
                        d,
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                    );
                  }).toList(),
                  onChanged: (val) {
                    if (val != null) {
                      setState(() => _selectedDate = val);
                      _loadReport();
                    }
                  },
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.assignment_outlined, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          Text(
            'কোনো তথ্য পাওয়া যায়নি',
            style: TextStyle(fontSize: 18, color: Colors.grey.shade500, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildReportContent() {
    if (_selectedDate == null) {
      return Center(
        child: Text(
          'রিপোর্ট দেখতে প্ল্যান এবং তারিখ নির্বাচন করুন',
          style: TextStyle(color: Colors.grey.shade500),
        ),
      );
    }

    int totalPresent = 0;
    int totalAbsent = 0;
    for (var r in _rows) {
      totalPresent += int.tryParse(r['present_cnt']?.toString() ?? '0') ?? 0;
      totalAbsent += int.tryParse(r['absent_cnt']?.toString() ?? '0') ?? 0;
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _buildSummaryCards(totalPresent, totalAbsent),
        if (_overallClasses.isNotEmpty) ...[
          const SizedBox(height: 16),
          _buildClassWiseSummary(),
        ],
        const SizedBox(height: 24),
        if (_rows.isNotEmpty) ...[
          const Text(
            'রুম ভিত্তিক রিপোর্ট',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.indigo),
          ),
          const SizedBox(height: 12),
          ..._rows.map((r) => _buildRoomRow(r)),
        ],
        const SizedBox(height: 32),
        if (_absentStudents.isNotEmpty) ...[
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.red.shade50,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.red.shade200),
            ),
            child: Row(
              children: [
                const Icon(Icons.person_off, color: Colors.red),
                const SizedBox(width: 8),
                Text(
                  'অনুপস্থিত শিক্ষার্থী (${_absentStudents.length} জন)',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.red),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              childAspectRatio: 0.80,
            ),
            itemCount: _absentStudents.length,
            itemBuilder: (context, index) {
              return _buildAbsentStudentCard(_absentStudents[index]);
            },
          ),
        ] else if (_rows.isNotEmpty) ...[
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.green.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.green.shade200),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.check_circle, color: Colors.green),
                SizedBox(width: 8),
                Text(
                  'সবাই উপস্থিত আছে',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.green),
                ),
              ],
            ),
          ),
        ]
      ],
    );
  }

  String _toBnNum(String number) {
    const en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    const bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    String res = number;
    for (int i = 0; i < en.length; i++) {
      res = res.replaceAll(en[i], bn[i]);
    }
    return res;
  }

  Widget _buildClassWiseSummary() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            offset: const Offset(0, 4),
            blurRadius: 8,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'শ্রেণি ভিত্তিক সারাংশ',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.indigo),
          ),
          const SizedBox(height: 12),
          ..._overallClasses.map((c) {
            final cName = c['class_name'] ?? '';
            final tS = int.tryParse(c['total_student']?.toString() ?? '0') ?? 0;
            final cP = int.tryParse(c['present_cnt']?.toString() ?? '0') ?? 0;
            final cA = int.tryParse(c['absent_cnt']?.toString() ?? '0') ?? 0;
            
            return Padding(
              padding: const EdgeInsets.only(bottom: 8.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    flex: 2,
                    child: Text(cName, style: const TextStyle(fontWeight: FontWeight.w600)),
                  ),
                  Expanded(
                    flex: 1,
                    child: Text('মোট: ${_toBnNum(tS.toString())}', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                  ),
                  Expanded(
                    flex: 1,
                    child: Text('উ: ${_toBnNum(cP.toString())}', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 13)),
                  ),
                  Expanded(
                    flex: 1,
                    child: Text('অ: ${_toBnNum(cA.toString())}', style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 13), textAlign: TextAlign.right),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildSummaryCards(int present, int absent) {
    return Row(
      children: [
        Expanded(
          child: _statCard(
            title: 'উপস্থিত',
            value: _toBnNum(present.toString()),
            color: Colors.green,
            icon: Icons.how_to_reg,
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: _statCard(
            title: 'অনুপস্থিত',
            value: _toBnNum(absent.toString()),
            color: Colors.red,
            icon: Icons.person_off,
          ),
        ),
      ],
    );
  }

  Widget _statCard({required String title, required String value, required Color color, required IconData icon}) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 32),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: color),
          ),
          const SizedBox(height: 4),
          Text(
            title,
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: color.withValues(alpha: 0.8)),
          ),
        ],
      ),
    );
  }

  Widget _buildRoomRow(dynamic row) {
    final roomNo = _toBnNum((row['room_no'] ?? 'N/A').toString());
    final invigilator = row['invigilator'] ?? 'N/A';
    final p = int.tryParse(row['present_cnt']?.toString() ?? '0') ?? 0;
    final a = int.tryParse(row['absent_cnt']?.toString() ?? '0') ?? 0;
    final classes = row['classes'] as List<dynamic>? ?? [];
    final attendanceTaken = row['attendance_taken'] == true;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            offset: const Offset(0, 4),
            blurRadius: 8,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: attendanceTaken ? Colors.indigo.shade50 : Colors.red.shade50,
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Text(
                    roomNo,
                    style: TextStyle(fontWeight: FontWeight.bold, color: attendanceTaken ? Colors.indigo.shade700 : Colors.red.shade700, fontSize: 16),
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'রুম: $roomNo',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'পরিদর্শক: $invigilator',
                      style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                    ),
                  ],
                ),
              ),
              if (attendanceTaken) 
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text('মোট উপস্থিত: ${_toBnNum(p.toString())}', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text('মোট অনুপস্থিত: ${_toBnNum(a.toString())}', style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
                  ],
                )
              else 
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.red.shade50,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text('হাজিরা সম্পন্ন হয়নি', style: TextStyle(color: Colors.red.shade700, fontSize: 12, fontWeight: FontWeight.bold)),
                ),
            ],
          ),
          if (attendanceTaken && classes.isNotEmpty) ...[
            const Padding(
              padding: EdgeInsets.only(top: 12, bottom: 8),
              child: Divider(),
            ),
            ...classes.map((c) {
              final cName = c['class_name'] ?? '';
              final cP = int.tryParse(c['present_cnt']?.toString() ?? '0') ?? 0;
              final cA = int.tryParse(c['absent_cnt']?.toString() ?? '0') ?? 0;
              final total = cP + cA;
              return Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('শ্রেণি: $cName (মোট: ${_toBnNum(total.toString())})', style: TextStyle(fontSize: 13, color: Colors.grey.shade800, fontWeight: FontWeight.w600)),
                    Row(
                      children: [
                        Text('উপস্থিত: ${_toBnNum(cP.toString())}', style: const TextStyle(fontSize: 12, color: Colors.green)),
                        const SizedBox(width: 8),
                        Text('অনুপস্থিত: ${_toBnNum(cA.toString())}', style: const TextStyle(fontSize: 12, color: Colors.red)),
                      ],
                    ),
                  ],
                ),
              );
            }).toList(),
          ],
        ],
      ),
    );
  }

  Widget _buildAbsentStudentCard(dynamic student) {
    final name = student['name'] ?? 'N/A';
    final roll = _toBnNum((student['roll'] ?? 'N/A').toString());
    final className = student['class_name'] ?? 'N/A';
    final roomNo = _toBnNum((student['room_no'] ?? 'N/A').toString());
    final photoUrl = student['photo_url'];

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.red.shade100),
        boxShadow: [
          BoxShadow(
            color: Colors.red.withValues(alpha: 0.05),
            offset: const Offset(0, 4),
            blurRadius: 8,
          ),
        ],
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(color: Colors.red.shade200, width: 2),
              color: Colors.grey.shade100,
            ),
            child: ClipOval(
              child: photoUrl != null && photoUrl.toString().isNotEmpty
                  ? Image.network(
                      photoUrl,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => const Icon(Icons.person, color: Colors.grey, size: 30),
                    )
                  : const Icon(Icons.person, color: Colors.grey, size: 30),
            ),
          ),
          const SizedBox(height: 12),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text(
              name,
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'রোল: $roll',
            style: TextStyle(color: Colors.grey.shade700, fontSize: 12),
          ),
          Text(
            className,
            style: TextStyle(color: Colors.indigo.shade400, fontSize: 11, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(
              color: Colors.red.shade50,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              'রুম $roomNo',
              style: TextStyle(color: Colors.red.shade700, fontSize: 10, fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }
}
