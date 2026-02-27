import 'package:flutter/material.dart';
import '../../../../data/teacher/teacher_exam_repository.dart';
import '../../../../widgets/app_snack.dart';

class ExamDutyPage extends StatefulWidget {
  const ExamDutyPage({super.key});

  @override
  State<ExamDutyPage> createState() => _ExamDutyPageState();
}

class _ExamDutyPageState extends State<ExamDutyPage> {
  bool _isLoading = true;
  List<dynamic> _duties = [];

  @override
  void initState() {
    super.initState();
    _loadDuties();
  }

  Future<void> _loadDuties() async {
    try {
      final repo = TeacherExamRepository();
      final data = await repo.getTodaysDuty();
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Exam Duty'),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black87,
        elevation: 0,
      ),
      body: _isLoading
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
    );
  }

  Widget _buildEmptyState() {
    return ListView(
      children: [
        SizedBox(height: MediaQuery.of(context).size.height * 0.2),
        const Icon(Icons.assignment_turned_in_outlined, size: 80, color: Colors.grey),
        const SizedBox(height: 16),
        const Text(
          'আজ কোনো ডিউটি নেই',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 18, color: Colors.grey, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 8),
        const Text(
          'আপনার জন্য আজকে কোনো পরীক্ষার ডিউটি বরাদ্দ করা হয়নি।',
          textAlign: TextAlign.center,
          style: TextStyle(color: Colors.grey),
        ),
      ],
    );
  }

  Widget _buildDutyCard(dynamic duty) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.only(bottom: 16),
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
                          style: TextStyle(color: Colors.grey.shade600, fontSize: 14),
                        ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.orange.shade100,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    'আজকের ডিউটি',
                    style: TextStyle(color: Colors.orange.shade900, fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 12),
              child: Divider(),
            ),
            _buildInfoRow(Icons.business, 'বিল্ডিং', duty['building'] ?? 'N/A'),
            const SizedBox(height: 8),
            _buildInfoRow(Icons.layers, 'তলা', duty['floor'] ?? 'N/A'),
            const SizedBox(height: 8),
            _buildInfoRow(Icons.event_note, 'সীট প্ল্যান', duty['seat_plan'] ?? 'N/A'),
            if (duty['exams'] != null && (duty['exams'] as List).isNotEmpty) ...[
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Divider(height: 1),
              ),
              const Text(
                'পরীক্ষাসমূহ:',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.blueGrey),
              ),
              const SizedBox(height: 4),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: (duty['exams'] as List).map((e) => Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(4),
                    border: Border.all(color: Colors.grey.shade300),
                  ),
                  child: Text(
                    e.toString(),
                    style: const TextStyle(fontSize: 11),
                  ),
                )).toList(),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 18, color: Colors.grey.shade600),
        const SizedBox(width: 8),
        Text('$label:', style: TextStyle(color: Colors.grey.shade700, fontWeight: FontWeight.w500)),
        const SizedBox(width: 4),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.bold),
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}
