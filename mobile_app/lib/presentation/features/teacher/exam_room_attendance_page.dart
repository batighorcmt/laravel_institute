import 'package:flutter/material.dart';
import '../../../../data/teacher/teacher_exam_repository.dart';
import '../../../../widgets/app_snack.dart';

class ExamRoomAttendancePage extends StatefulWidget {
  final int planId;
  final int roomId;
  final String roomNo;
  final String date;

  const ExamRoomAttendancePage({
    super.key,
    required this.planId,
    required this.roomId,
    required this.roomNo,
    required this.date,
  });

  @override
  State<ExamRoomAttendancePage> createState() => _ExamRoomAttendancePageState();
}

class _ExamRoomAttendancePageState extends State<ExamRoomAttendancePage> {
  final TeacherExamRepository _repo = TeacherExamRepository();
  bool _isLoading = true;
  List<dynamic> _students = [];
  Map<String, dynamic> _stats = {};
  String? _bulkMode; // 'present' or 'absent'

  @override
  void initState() {
    super.initState();
    _loadStudents();
  }

  Future<void> _loadStudents() async {
    try {
      final data = await _repo.getRoomAttendance(
        planId: widget.planId,
        roomId: widget.roomId,
        date: widget.date,
      );
      if (mounted) {
        setState(() {
          _students = List.from(data['students'] ?? []);
          _stats = data['stats'] ?? {};
          _isLoading = false;
          _updateBulkMode();
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'শিক্ষার্থী তালিকা লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  void _updateBulkMode() {
    if (_students.isEmpty) {
      _bulkMode = null;
      return;
    }
    bool allPresent = _students.every((s) => s['status'] == 'present');
    bool allAbsent = _students.every((s) => s['status'] == 'absent');
    if (allPresent) {
      _bulkMode = 'present';
    } else if (allAbsent) {
      _bulkMode = 'absent';
    } else {
      _bulkMode = null;
    }
  }

  Future<void> _onStatusChanged(int index, String status) async {
    final originalStatus = _students[index]['status'];
    if (originalStatus == status) return;

    setState(() {
      _students[index]['status'] = status;
      _updateStats();
      _updateBulkMode();
    });

    try {
      await _repo.submitRoomAttendance(
        planId: widget.planId,
        roomId: widget.roomId,
        date: widget.date,
        studentId: _students[index]['id'],
        status: status,
      );
    } catch (e) {
      if (mounted) {
        setState(() {
          _students[index]['status'] = originalStatus;
          _updateStats();
          _updateBulkMode();
        });
        showAppSnack(context, message: 'সংরক্ষণ ব্যর্থ হয়েছে');
      }
    }
  }

  Future<void> _onBulkUpdate(String status) async {
    setState(() {
      for (var s in _students) {
        s['status'] = status;
      }
      _updateStats();
      _bulkMode = status;
    });

    try {
      await _repo.bulkSubmitRoomAttendance(
        planId: widget.planId,
        roomId: widget.roomId,
        date: widget.date,
        status: status,
      );
    } catch (e) {
      if (mounted) {
        showAppSnack(context, message: 'Bulk আপডেট ব্যর্থ হয়েছে');
        _loadStudents(); // Reload to get correct state
      }
    }
  }

  void _updateStats() {
    int present = _students.where((s) => s['status'] == 'present').length;
    int absent = _students.where((s) => s['status'] == 'absent').length;
    setState(() {
      _stats['present'] = present;
      _stats['absent'] = absent;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        title: Text('কক্ষ: ${widget.roomNo}'),
        centerTitle: true,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                _buildStatsHeader(),
                _buildBulkToggle(),
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    itemCount: _students.length,
                    itemBuilder: (context, index) {
                      final student = _students[index];
                      return _buildStudentCard(student, index);
                    },
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildStatsHeader() {
    final gender = _stats['gender'] ?? {};
    final classes = _stats['classes'] ?? {};

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _statGroup('মোট শিক্ষার্থী', _stats['total']?.toString() ?? '0', Icons.people, Colors.blue),
              _statGroup('উপস্থিত', _stats['present']?.toString() ?? '0', Icons.check_circle, Colors.green),
              _statGroup('অনুপস্থিত', _stats['absent']?.toString() ?? '0', Icons.cancel, Colors.red),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _subStat('ছেলে: ${gender['male'] ?? 0}', Icons.male, Colors.blue),
              const SizedBox(width: 20),
              _subStat('মেয়ে: ${gender['female'] ?? 0}', Icons.female, Colors.pink),
            ],
          ),
          if (classes.isNotEmpty) ...[
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 4,
              alignment: WrapAlignment.center,
              children: classes.entries.map<Widget>((e) => Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: Text('${e.key}: ${e.value}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
              )).toList(),
            ),
          ],
        ],
      ),
    );
  }

  Widget _statGroup(String label, String value, IconData icon, Color color) {
    return Column(
      children: [
        Icon(icon, size: 24, color: color),
        const SizedBox(height: 4),
        Text(value, style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: color)),
        Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
      ],
    );
  }

  Widget _subStat(String text, IconData icon, Color color) {
    return Row(
      children: [
        Icon(icon, size: 16, color: color),
        const SizedBox(width: 4),
        Text(text, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
      ],
    );
  }

  Widget _buildBulkToggle() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
      child: Row(
        children: [
          Expanded(
            child: _bulkButton(
              label: 'সব উপস্থিত',
              status: 'present',
              color: Colors.green,
              isSelected: _bulkMode == 'present',
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _bulkButton(
              label: 'সব অনুপস্থিত',
              status: 'absent',
              color: Colors.red,
              isSelected: _bulkMode == 'absent',
            ),
          ),
        ],
      ),
    );
  }

  Widget _bulkButton({required String label, required String status, required Color color, required bool isSelected}) {
    return InkWell(
      onTap: () => _onBulkUpdate(status),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? color : Colors.white,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: isSelected ? color : Colors.grey.shade300),
          boxShadow: isSelected ? [BoxShadow(color: color.withOpacity(0.3), blurRadius: 4, offset: const Offset(0, 2))] : null,
        ),
        child: Center(
          child: Text(
            label,
            style: TextStyle(
              color: isSelected ? Colors.white : color,
              fontWeight: FontWeight.bold,
              fontSize: 14,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStudentCard(dynamic student, int index) {
    final status = student['status'];
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        children: [
          _buildStudentPhoto(student['photo_url']),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        'Roll: ${student['roll']}',
                        style: TextStyle(color: Colors.blue.shade900, fontWeight: FontWeight.bold, fontSize: 11),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        student['name'] ?? 'N/A',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  student['class_name'] ?? 'N/A',
                  style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          _statusToggle(
            status: status,
            onChanged: (newStatus) => _onStatusChanged(index, newStatus),
          ),
        ],
      ),
    );
  }

  Widget _buildStudentPhoto(String? url) {
    return Container(
      width: 50,
      height: 50,
      decoration: BoxDecoration(
        color: Colors.grey.shade100,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: url != null && url.isNotEmpty
            ? Image.network(
                url,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => const Icon(Icons.person, color: Colors.grey),
              )
            : const Icon(Icons.person, color: Colors.grey),
      ),
    );
  }

  Widget _statusToggle({required String? status, required Function(String) onChanged}) {
    return Row(
      children: [
        _miniStatusBtn(
          icon: Icons.check,
          color: Colors.green,
          isSelected: status == 'present',
          onTap: () => onChanged('present'),
        ),
        const SizedBox(width: 8),
        _miniStatusBtn(
          icon: Icons.close,
          color: Colors.red,
          isSelected: status == 'absent',
          onTap: () => onChanged('absent'),
        ),
      ],
    );
  }

  Widget _miniStatusBtn({required IconData icon, required Color color, required bool isSelected, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        width: 38,
        height: 38,
        decoration: BoxDecoration(
          color: isSelected ? color : Colors.white,
          shape: BoxShape.circle,
          border: Border.all(color: isSelected ? color : Colors.grey.shade300),
        ),
        child: Icon(
          icon,
          size: 20,
          color: isSelected ? Colors.white : Colors.grey.shade400,
        ),
      ),
    );
  }
}
修复
