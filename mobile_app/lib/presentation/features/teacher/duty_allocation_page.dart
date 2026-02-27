import 'package:flutter/material.dart';
import '../../../../data/teacher/teacher_exam_repository.dart';
import '../../../../widgets/app_snack.dart';

class DutyAllocationPage extends StatefulWidget {
  const DutyAllocationPage({super.key});

  @override
  State<DutyAllocationPage> createState() => _DutyAllocationPageState();
}

class _DutyAllocationPageState extends State<DutyAllocationPage> {
  final TeacherExamRepository _repo = TeacherExamRepository();
  bool _isLoadingMeta = true;
  bool _isLoadingRooms = false;
  
  List<dynamic> _plans = [];
  List<String> _availableDates = [];
  List<dynamic> _teachers = [];
  
  int? _selectedPlanId;
  String? _selectedDate;
  List<dynamic> _rooms = [];

  @override
  void initState() {
    super.initState();
    _loadInitialMeta();
    _loadTeachers();
  }

  Future<void> _loadInitialMeta() async {
    try {
      final meta = await _repo.getDutyMeta();
      if (mounted) {
        setState(() {
          _plans = meta['plans'] ?? [];
          if (_plans.isNotEmpty) {
            _selectedPlanId = _plans.first['id'];
          }
        });
        if (_selectedPlanId != null) {
          await _loadDatesForPlan(_selectedPlanId!);
        }
      }
    } catch (_) {}
    setState(() => _isLoadingMeta = false);
  }

  Future<void> _loadTeachers() async {
    try {
      final list = await _repo.getTeachersList();
      if (mounted) {
        setState(() => _teachers = list);
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
             _selectedDate = _availableDates.contains(_selectedDate) ? _selectedDate : _availableDates.first;
          } else {
            _selectedDate = null;
          }
        });
        if (_selectedDate != null) {
          _loadRooms();
        }
      }
    } catch (_) {}
  }

  Future<void> _loadRooms() async {
    if (_selectedPlanId == null || _selectedDate == null) return;
    setState(() => _isLoadingRooms = true);
    try {
      final data = await _repo.getTodaysDuty(
        planId: _selectedPlanId,
        date: _selectedDate,
      );
      if (mounted) {
        setState(() {
          _rooms = data;
          _isLoadingRooms = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingRooms = false);
        showAppSnack(context, message: 'রুম তালিকা লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  Future<void> _assignDuty(int roomId, int? teacherUserId) async {
    if (_selectedPlanId == null || _selectedDate == null) return;
    
    try {
      if (teacherUserId == null) {
        await _repo.removeDuty(
          planId: _selectedPlanId!,
          roomId: roomId,
          date: _selectedDate!,
        );
      } else {
        await _repo.assignDuty(
          planId: _selectedPlanId!,
          roomId: roomId,
          date: _selectedDate!,
          teacherUserId: teacherUserId,
        );
      }
      showAppSnack(context, message: 'সংরক্ষণ করা হয়েছে', isError: false);
      _loadRooms(); // Refresh to update view
    } catch (e) {
      showAppSnack(context, message: 'সংরক্ষণ ব্যর্থ হয়েছে');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        title: const Text('Duty Allocation'),
        elevation: 0,
      ),
      body: _isLoadingMeta
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                _buildFilters(),
                Expanded(
                  child: _isLoadingRooms
                      ? const Center(child: CircularProgressIndicator())
                      : _rooms.isEmpty
                          ? _buildEmptyState()
                          : ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: _rooms.length,
                              itemBuilder: (context, index) {
                                final room = _rooms[index];
                                return _buildAllocationCard(room);
                              },
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
                    setState(() {
                      _selectedPlanId = val;
                      _rooms = [];
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
                      setState(() {
                        _selectedDate = val;
                        _rooms = [];
                      });
                      _loadRooms();
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
          Icon(Icons.room_preferences_outlined, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          const Text(
            'কোনো কক্ষ পাওয়া যায়নি',
            style: TextStyle(fontSize: 18, color: Colors.grey, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildAllocationCard(dynamic room) {
    // Find current assigned teacher's user_id if any
    int? currentTeacherUserId;
    if (room['is_assigned'] == true) {
       // Our API returns id > 0 if assigned. 
       // We need the teacher_user_id to match in dropdown
       // Actually let's look at what todaysDuty returns for controller:
       // 'teacher_name' is there, but we need Teacher ID.
       // Let's modify Backend to return teacher_user_id too if assigned.
    }
    
    // For now, let's assume we might need a small backend fix or use teacher_name matching (unreliable)
    // I will go ahead and fix Backend in next step to include teacher_user_id in room list.
    
    int? teacherUserId = room['teacher_user_id']; // To be added to API response

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.blue.shade50,
                  radius: 18,
                  child: Text(
                    room['room_no']?.toString() ?? '?',
                    style: TextStyle(color: Colors.blue.shade900, fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        room['room_title'] ?? 'Room ${room['room_no']}',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      Text(
                        'Shift: ${room['shift'] ?? 'N/A'}',
                        style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Text(
              'ডিউটি শিক্ষক বরাদ্দ করুন:',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.blueGrey),
            ),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              decoration: BoxDecoration(
                color: Colors.grey.shade50,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<int>(
                  isExpanded: true,
                  value: teacherUserId,
                  hint: const Text('শিক্ষক নির্বাচন করুন'),
                  items: [
                    const DropdownMenuItem<int>(
                      value: null,
                      child: Text('মুলতুবি (None)', style: TextStyle(color: Colors.red)),
                    ),
                    ..._teachers.map((t) => DropdownMenuItem<int>(
                      value: t['user_id'],
                      child: Text(t['display_name']),
                    )),
                  ],
                  onChanged: (val) => _assignDuty(room['seat_plan_room_id'], val),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
