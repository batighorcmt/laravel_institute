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
  bool _isSubmitting = false;
  List<dynamic> _students = [];
  Map<String, dynamic> _stats = {};

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
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'শিক্ষার্থী তালিকা লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  void _onStatusChanged(int index, String? status) {
    setState(() {
      _students[index]['status'] = status;
      _updateStats();
    });
  }

  void _updateStats() {
    int present = _students.where((s) => s['status'] == 'present').length;
    int absent = _students.where((s) => s['status'] == 'absent').length;
    _stats['present'] = present;
    _stats['absent'] = absent;
  }

  Future<void> _submit() async {
    if (_students.any((s) => s['status'] == null)) {
      showAppSnack(context, message: 'অনুগ্রহ করে সবার হাজিরা সম্পন্ন করুন');
      return;
    }

    setState(() => _isSubmitting = true);
    try {
      final items = _students.map((s) => {
        'student_id': s['id'],
        'status': s['status'],
      }).toList();

      await _repo.submitRoomAttendance(
        planId: widget.planId,
        roomId: widget.roomId,
        date: widget.date,
        items: items,
      );
      
      if (mounted) {
        showAppSnack(context, message: 'হাজিরা সফলভাবে সংরক্ষিত হয়েছে');
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isSubmitting = false);
        showAppSnack(context, message: 'সংরক্ষণ করতে সমস্যা হয়েছে');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('কক্ষ: ${widget.roomNo} - হাজিরা'),
        actions: [
          if (!_isLoading)
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: _loadStudents,
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                _buildStatsHeader(),
                Expanded(
                  child: ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: _students.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      final student = _students[index];
                      return _buildStudentRow(student, index);
                    },
                  ),
                ),
                _buildSubmitButton(),
              ],
            ),
    );
  }

  Widget _buildStatsHeader() {
    return Container(
      padding: const EdgeInsets.all(16),
      color: Colors.blue.shade50,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _statItem('মোট', _stats['total']?.toString() ?? '0', Colors.blue),
          _statItem('উপস্থিত', _stats['present']?.toString() ?? '0', Colors.green),
          _statItem('অনুপস্থিত', _stats['absent']?.toString() ?? '0', Colors.red),
        ],
      ),
    );
  }

  Widget _statItem(String label, String value, Color color) {
    return Column(
      children: [
        Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color)),
        Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade700)),
      ],
    );
  }

  Widget _buildStudentRow(dynamic student, int index) {
    final status = student['status'];
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(10),
        side: BorderSide(color: Colors.grey.shade200),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                shape: BoxShape.circle,
              ),
              child: Text(
                student['roll']?.toString() ?? '?',
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    student['name'] ?? 'N/A',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
                  Text(
                    student['class_name'] ?? 'N/A',
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                  ),
                ],
              ),
            ),
            _statusButton(
              icon: Icons.check,
              color: Colors.green,
              isSelected: status == 'present',
              onTap: () => _onStatusChanged(index, 'present'),
            ),
            const SizedBox(width: 8),
            _statusButton(
              icon: Icons.close,
              color: Colors.red,
              isSelected: status == 'absent',
              onTap: () => _onStatusChanged(index, 'absent'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _statusButton({
    required IconData icon,
    required Color color,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
          color: isSelected ? color : color.withOpacity(0.1),
          shape: BoxShape.circle,
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Icon(
          icon,
          size: 20,
          color: isSelected ? Colors.white : color,
        ),
      ),
    );
  }

  Widget _buildSubmitButton() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, -5)),
        ],
      ),
      child: SizedBox(
        width: double.infinity,
        height: 50,
        child: ElevatedButton(
          onPressed: _isSubmitting ? null : _submit,
          style: ElevatedButton.styleFrom(
            backgroundColor: Colors.blue,
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
          child: _isSubmitting
              ? const CircularProgressIndicator(color: Colors.white)
              : const Text('সংরক্ষণ করুন', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        ),
      ),
    );
  }
}
