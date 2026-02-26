import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../state/parent_state.dart';

class LeaveApplicationPage extends ConsumerStatefulWidget {
  const LeaveApplicationPage({super.key});

  @override
  ConsumerState<LeaveApplicationPage> createState() => _LeaveApplicationPageState();
}

class _LeaveApplicationPageState extends ConsumerState<LeaveApplicationPage> {
  late TextEditingController _reasonController;
  late TextEditingController _remarksController;
  DateTime? _startDate;
  DateTime? _endDate;
  String _leaveType = 'সাধারণ ছুটি';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _reasonController = TextEditingController();
    _remarksController = TextEditingController();
  }

  @override
  void dispose() {
    _reasonController.dispose();
    _remarksController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final leavesAsync = ref.watch(parentLeavesProvider);

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'নতুন ছুটির আবেদন',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 24),

            // Leave Type
            const Text(
              'ছুটির ধরন',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            DropdownButton<String>(
              isExpanded: true,
              value: _leaveType,
              items:
                  [
                    'সাধারণ ছুটি',
                    'অসুস্থতার কারণে',
                    'পরিবারের জরুরি প্রয়োজনে',
                    'শিক্ষা ভ্রমণ',
                    'অন্যান্য',
                  ].map((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value),
                    );
                  }).toList(),
              onChanged: (String? newValue) {
                setState(() {
                  _leaveType = newValue!;
                });
              },
            ),
            const SizedBox(height: 24),

            // Start Date
            const Text(
              'শুরুর তারিখ',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            ElevatedButton.icon(
              onPressed: () => _selectDate(context, true),
              icon: const Icon(Icons.calendar_today),
              label: Text(
                _startDate == null
                    ? 'তারিখ নির্বাচন করুন'
                    : DateFormat('dd/MM/yyyy').format(_startDate!),
              ),
              style: ElevatedButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
            ),
            const SizedBox(height: 24),

            // End Date
            const Text(
              'শেষের তারিখ',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            ElevatedButton.icon(
              onPressed: () => _selectDate(context, false),
              icon: const Icon(Icons.calendar_today),
              label: Text(
                _endDate == null
                    ? 'তারিখ নির্বাচন করুন'
                    : DateFormat('dd/MM/yyyy').format(_endDate!),
              ),
              style: ElevatedButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
            ),
            const SizedBox(height: 24),

            // Duration
            if (_startDate != null && _endDate != null)
              Card(
                color: Colors.blue.withOpacity(0.1),
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('মোট দিন:'),
                      Text(
                        '${_endDate!.difference(_startDate!).inDays + 1} দিন',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            const SizedBox(height: 24),

            // Reason
            const Text('कारण', style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            TextField(
              controller: _reasonController,
              maxLines: 3,
              decoration: InputDecoration(
                hintText: 'ছুটির কারণ বর্ণনা করুন',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Submit Button
            SizedBox(
              width: double.infinity,
              child: _isLoading 
                ? const Center(child: CircularProgressIndicator())
                : ElevatedButton(
                onPressed: _submitApplication,
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  backgroundColor: Colors.blue,
                ),
                child: const Text(
                  'আবেদন জমা দিন',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 32),

            // Previous Applications
            const Text(
              'আগের আবেদন',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            leavesAsync.when(
              data: (leaves) => Column(
                children: leaves.map((leave) {
                  final status = leave['status']?.toString().toLowerCase() ?? 'pending';
                  Color color = Colors.orange;
                  String stText = 'অপেক্ষমান';
                  if (status == 'approved') { color = Colors.green; stText = 'অনুমোদিত'; }
                  if (status == 'rejected') { color = Colors.red; stText = 'প্রত্যাখ্যাত'; }

                  return _buildApplicationCard(
                    leave['type']?.toString() ?? 'N/A',
                    '${leave['start_date']} - ${leave['end_date']}',
                    stText,
                    color,
                  );
                }).toList(),
              ),
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (err, _) => Text('ত্রুটি: $err'),
            ),
          ],
        ),
    );
  }

  Future<void> _selectDate(BuildContext context, bool isStartDate) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now().add(const Duration(days: 90)),
    );
    if (picked != null) {
      setState(() {
        if (isStartDate) {
          _startDate = picked;
        } else {
          _endDate = picked;
        }
      });
    }
  }

  Future<void> _submitApplication() async {
    if (_startDate == null ||
        _endDate == null ||
        _reasonController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('অনুগ্রহ করে সব প্রয়োজনীয় ক্ষেত্র পূরণ করুন'),
        ),
      );
      return;
    }

    setState(() => _isLoading = true);
    try {
      final repo = ref.read(parentRepositoryProvider);
      final studentId = ref.read(selectedStudentIdProvider);

      await repo.submitLeave({
        'student_id': studentId,
        'type': _leaveType,
        'reason': _reasonController.text,
        'start_date': DateFormat('yyyy-MM-dd').format(_startDate!),
        'end_date': DateFormat('yyyy-MM-dd').format(_endDate!),
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('আবেদনটি সফলভাবে জমা দেওয়া হয়েছে')),
        );
        _reasonController.clear();
        _remarksController.clear();
        setState(() {
          _startDate = null;
          _endDate = null;
          _leaveType = 'সাধারণ ছুটি';
        });
        ref.invalidate(parentLeavesProvider);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('ভুল হয়েছে: $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Widget _buildApplicationCard(
    String type,
    String dates,
    String status,
    Color statusColor,
  ) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: const Icon(Icons.assignment_turned_in, color: Colors.blue),
        title: Text(type, style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Text(dates),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: statusColor.withOpacity(0.2),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            status,
            style: TextStyle(
              color: statusColor,
              fontWeight: FontWeight.bold,
              fontSize: 12,
            ),
          ),
        ),
      ),
    );
  }
}
