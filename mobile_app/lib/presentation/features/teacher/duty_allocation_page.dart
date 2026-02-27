import 'package:flutter/material.dart';
import 'package:dropdown_search/dropdown_search.dart';
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
  bool _isSaving = false;

  List<dynamic> _plans = [];
  List<String> _availableDates = [];
  List<dynamic> _teachers = [];

  int? _selectedPlanId;
  String? _selectedDate;
  List<dynamic> _rooms = [];

  // Map of roomId -> selected teacher object (or null)
  final Map<int, dynamic> _selections = {};

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
        });
      }
    } catch (_) {}
    setState(() => _isLoadingMeta = false);
  }

  Future<void> _loadTeachers() async {
    try {
      final list = await _repo.getTeachersList();
      if (mounted) setState(() => _teachers = list);
    } catch (_) {}
  }

  Future<void> _loadDatesForPlan(int planId) async {
    try {
      final meta = await _repo.getDutyMeta(planId: planId);
      if (mounted) {
        setState(() {
          _availableDates = List<String>.from(meta['dates'] ?? []);
          _selectedDate = null;
          _rooms = [];
          _selections.clear();
        });
      }
    } catch (_) {}
  }

  Future<void> _loadRooms() async {
    if (_selectedPlanId == null || _selectedDate == null) return;
    setState(() {
      _isLoadingRooms = true;
      _selections.clear();
    });
    try {
      final data = await _repo.getTodaysDuty(
        planId: _selectedPlanId,
        date: _selectedDate,
      );
      if (mounted) {
        // Pre-populate selections from existing assignments
        final Map<int, dynamic> preSelected = {};
        for (final room in data) {
          final roomId = room['seat_plan_room_id'] as int;
          final teacherUserId = room['teacher_user_id'];
          if (teacherUserId != null) {
            try {
              preSelected[roomId] = _teachers.firstWhere((t) => t['user_id'] == teacherUserId);
            } catch (_) {}
          } else {
            preSelected[roomId] = null;
          }
        }
        setState(() {
          _rooms = data;
          _selections.addAll(preSelected);
          _isLoadingRooms = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingRooms = false);
        showAppSnack(context, message: 'রুম তালিকা লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  // Returns the set of teacher user_ids already selected (excluding the given roomId)
  Set<int> _assignedTeacherIds({int? excludeRoomId}) {
    final result = <int>{};
    _selections.forEach((roomId, teacher) {
      if (roomId != excludeRoomId && teacher != null) {
        result.add(teacher['user_id'] as int);
      }
    });
    return result;
  }

  Future<void> _saveAll() async {
    if (_selectedPlanId == null || _selectedDate == null) return;
    setState(() => _isSaving = true);
    try {
      final allocations = _rooms.map<Map<String, dynamic>>((room) {
        final roomId = room['seat_plan_room_id'] as int;
        final teacher = _selections[roomId];
        return {
          'room_id': roomId,
          'teacher_user_id': teacher?['user_id'],
        };
      }).toList();

      await _repo.assignDuty(
        planId: _selectedPlanId!,
        date: _selectedDate!,
        allocations: allocations,
      );
      showAppSnack(context, message: 'সকল ডিউটি সংরক্ষণ করা হয়েছে', success: true);
      _loadRooms();
    } catch (e) {
      showAppSnack(context, message: 'সংরক্ষণ ব্যর্থ হয়েছে');
    } finally {
      if (mounted) setState(() => _isSaving = false);
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
                              padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                              itemCount: _rooms.length,
                              itemBuilder: (context, index) {
                                final room = _rooms[index];
                                return _buildAllocationCard(room);
                              },
                            ),
                ),
              ],
            ),
      bottomNavigationBar: _rooms.isNotEmpty
          ? SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton.icon(
                    onPressed: _isSaving ? null : _saveAll,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue.shade700,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: _isSaving
                        ? const SizedBox(
                            width: 20, height: 20,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                          )
                        : const Icon(Icons.save_alt_rounded),
                    label: Text(_isSaving ? 'সংরক্ষণ হচ্ছে...' : 'সকল ডিউটি সংরক্ষণ করুন',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  ),
                ),
              ),
            )
          : null,
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
                      _selections.clear();
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
                        _selections.clear();
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
            'সীট প্ল্যান ও তারিখ নির্বাচন করুন',
            style: TextStyle(fontSize: 16, color: Colors.grey, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildAllocationCard(dynamic room) {
    final roomId = room['seat_plan_room_id'] as int;
    final selectedTeacher = _selections[roomId];

    // Teachers already assigned to other rooms (to prevent duplicates)
    final assignedIds = _assignedTeacherIds(excludeRoomId: roomId);
    final availableTeachers = _teachers.where((t) {
      final uid = t['user_id'] as int;
      return !assignedIds.contains(uid);
    }).toList();

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
                if (selectedTeacher != null)
                  const Icon(Icons.check_circle, color: Colors.green, size: 20),
              ],
            ),
            const SizedBox(height: 12),
            DropdownSearch<dynamic>(
              key: ValueKey('room_$roomId'),
              items: availableTeachers,
              itemAsString: (item) => item['display_name'] ?? '',
              selectedItem: selectedTeacher,
              onChanged: (val) {
                setState(() => _selections[roomId] = val);
              },
              filterFn: (item, filter) {
                if (filter.isEmpty) return true;
                if (filter.length < 2) return false;
                final name = (item['display_name'] ?? '').toString().toLowerCase();
                return name.contains(filter.toLowerCase());
              },
              dropdownDecoratorProps: DropDownDecoratorProps(
                dropdownSearchDecoration: InputDecoration(
                  hintText: "শিক্ষক নির্বাচন করুন",
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: BorderSide(color: Colors.grey.shade200)),
                  enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: BorderSide(color: Colors.grey.shade200)),
                  filled: true,
                  fillColor: Colors.grey.shade50,
                ),
              ),
              clearButtonProps: const ClearButtonProps(isVisible: true),
              popupProps: PopupProps.menu(
                showSearchBox: true,
                searchFieldProps: const TextFieldProps(
                  decoration: InputDecoration(
                    hintText: "২ বা ততোধিক অক্ষর দিয়ে খুঁজুন...",
                    prefixIcon: Icon(Icons.search),
                  ),
                ),
                constraints: const BoxConstraints(maxHeight: 250),
                itemBuilder: (context, item, isSelected) {
                  return ListTile(
                    title: Text(item['display_name']),
                    selected: isSelected,
                    dense: true,
                    visualDensity: VisualDensity.compact,
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
