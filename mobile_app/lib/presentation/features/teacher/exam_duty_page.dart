import 'package:flutter/material.dart';
import '../../../../data/teacher/teacher_exam_repository.dart';
import '../../../../widgets/app_snack.dart';
import 'exam_room_attendance_page.dart';

class ExamDutyPage extends StatefulWidget {
  final bool isController;
  const ExamDutyPage({super.key, this.isController = false});

  @override
  State<ExamDutyPage> createState() => _ExamDutyPageState();
}

class _ExamDutyPageState extends State<ExamDutyPage> {
  final TeacherExamRepository _repo = TeacherExamRepository();
  bool _isLoading = false;
  List<dynamic> _duties = [];
  
  List<dynamic> _plans = [];
  List<String> _availableDates = [];
  int? _selectedPlanId;
  String? _selectedDate;

  @override
  void initState() {
    super.initState();
    if (widget.isController) {
      _loadInitialMeta();
    } else {
      _selectedDate = _formatDate(DateTime.now());
      setState(() => _isLoading = true);
      _loadDuties();
    }
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
  }

  Future<void> _loadDatesForPlan(int planId) async {
    try {
      final meta = await _repo.getDutyMeta(planId: planId);
      if (mounted) {
        setState(() {
          _availableDates = List<String>.from(meta['dates'] ?? []);
          if (_availableDates.isNotEmpty) {
             // Try to keep same date or pick first/today
             String today = _formatDate(DateTime.now());
             if (_availableDates.contains(_selectedDate)) {
               // keep selectedDate
             } else if (_availableDates.contains(today)) {
               _selectedDate = today;
             } else {
               _selectedDate = _availableDates.first;
             }
          } else {
            _selectedDate = null;
          }
        });
        _loadDuties();
      }
    } catch (_) {}
  }

  Future<void> _loadDuties() async {
    setState(() => _isLoading = true);
    try {
      final data = await _repo.getTodaysDuty(
        planId: _selectedPlanId,
        date: _selectedDate,
      );
      if (mounted) {
        setState(() {
          _duties = data;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'ডাটা লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  String _formatDate(DateTime d) {
    return '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        title: const Text('Exam Duty List'),
        elevation: 0,
      ),
      body: Column(
        children: [
          if (widget.isController) _buildFilters(),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _loadDuties,
                    child: _duties.isEmpty
                        ? _buildEmptyState()
                        : ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: _duties.length,
                            itemBuilder: (context, index) {
                              final duty = _duties[index];
                              return _buildDutyCard(duty);
                            },
                          ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilters() {
    return Container(
      padding: const EdgeInsets.all(16),
      color: Colors.white,
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: Colors.grey.shade100,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.grey.shade300),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<int>(
                isExpanded: true,
                value: _selectedPlanId,
                hint: const Text('সীট প্ল্যান নির্বাচন করুন'),
                items: _plans.map<DropdownMenuItem<int>>((p) {
                  return DropdownMenuItem<int>(
                    value: p['id'],
                    child: Text(p['name']),
                  );
                }).toList(),
                onChanged: (val) {
                  if (val != null) {
                    setState(() => _selectedPlanId = val);
                    _loadDatesForPlan(val);
                  }
                },
              ),
            ),
          ),
          if (_availableDates.isNotEmpty) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.grey.shade300),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<String>(
                  isExpanded: true,
                  value: _selectedDate,
                  hint: const Text('তারিখ নির্বাচন করুন'),
                  items: _availableDates.map<DropdownMenuItem<String>>((d) {
                    return DropdownMenuItem<String>(
                      value: d,
                      child: Text(d),
                    );
                  }).toList(),
                  onChanged: (val) {
                    if (val != null) {
                      setState(() => _selectedDate = val);
                      _loadDuties();
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
    return ListView(
      children: [
        SizedBox(height: MediaQuery.of(context).size.height * 0.2),
        const Icon(Icons.assignment_turned_in_outlined, size: 80, color: Colors.grey),
        const SizedBox(height: 16),
        const Text(
          'কোনো তথ্য পাওয়া যায়নি',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 18, color: Colors.grey, fontWeight: FontWeight.bold),
        ),
      ],
    );
  }

  Widget _buildDutyCard(dynamic duty) {
    bool isAssigned = duty['is_assigned'] ?? true;
    return Card(
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.only(bottom: 16),
      child: InkWell(
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => ExamRoomAttendancePage(
                planId: duty['seat_plan_id'],
                roomId: duty['seat_plan_room_id'],
                roomNo: duty['room_no'] ?? 'N/A',
                date: duty['duty_date'],
              ),
            ),
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(Icons.meeting_room, color: Colors.blue.shade700),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'কক্ষ নং: ${duty['room_no'] ?? 'N/A'}',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        if (duty['room_title'] != null)
                          Text(
                            duty['room_title'],
                            style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                          ),
                      ],
                    ),
                  ),
                  if (widget.isController)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: isAssigned ? Colors.green.shade50 : Colors.red.shade50,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        isAssigned ? (duty['teacher_name'] ?? 'Assigned') : 'Not Assigned',
                        style: TextStyle(
                          color: isAssigned ? Colors.green.shade800 : Colors.red.shade800,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                ],
              ),
              const Divider(height: 24),
              _buildInfoRow(Icons.event_note, 'সীট প্ল্যান', duty['seat_plan'] ?? 'N/A'),
              const SizedBox(height: 8),
              _buildInfoRow(Icons.access_time, 'শীফট', duty['shift'] ?? 'N/A'),
              const SizedBox(height: 8),
              _buildInfoRow(Icons.business, 'অবস্থান', '${duty['building'] ?? ''} (${duty['floor'] ?? ''})'),
              if (duty['classes'] != null && (duty['classes'] as List).isNotEmpty) ...[
                const SizedBox(height: 12),
                const Text(
                  'শ্রেণি সমূহ:',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.blueGrey),
                ),
                const SizedBox(height: 6),
                Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  children: (duty['classes'] as List).map((e) => Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: Colors.blue.shade100),
                    ),
                    child: Text(
                      e.toString(),
                      style: TextStyle(fontSize: 11, color: Colors.blue.shade900, fontWeight: FontWeight.bold),
                    ),
                  )).toList(),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 16, color: Colors.grey.shade600),
        const SizedBox(width: 8),
        Text('$label:', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
        const SizedBox(width: 6),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}
