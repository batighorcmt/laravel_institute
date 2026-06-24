import 'package:flutter/material.dart';
import '../../../../data/teacher/teacher_exam_repository.dart';
import '../../../../widgets/app_snack.dart';

class SeatFindingPage extends StatefulWidget {
  const SeatFindingPage({super.key});

  @override
  State<SeatFindingPage> createState() => _SeatFindingPageState();
}

class _SeatFindingPageState extends State<SeatFindingPage> {
  final TextEditingController _searchController = TextEditingController();
  final TeacherExamRepository _repo = TeacherExamRepository();

  bool _isLoading = true;
  bool _isSearching = false;
  List<dynamic> _plans = [];
  int? _selectedPlanId;
  List<dynamic> _results = [];

  @override
  void initState() {
    super.initState();
    _loadInitialData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    try {
      final data = await _repo.findSeat('', null);
      if (mounted) {
        setState(() {
          _plans = data['plans'] ?? [];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        showAppSnack(context, message: 'প্রাথমিক ডাটা লোড করতে ব্যর্থ হয়েছে');
      }
    }
  }

  Future<void> _search() async {
    final query = _searchController.text.trim();
    if (query.isEmpty) {
      showAppSnack(context, message: 'অনুগ্রহ করে সার্চ কীওয়ার্ড লিখুন');
      return;
    }
    if (_selectedPlanId == null) {
      showAppSnack(context, message: 'অনুগ্রহ করে সীট প্ল্যান নির্বাচন করুন');
      return;
    }

    setState(() => _isSearching = true);
    try {
      final data = await _repo.findSeat(query, _selectedPlanId);
      if (mounted) {
        setState(() {
          _results = data['results'] ?? [];
          _isSearching = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isSearching = false);
        showAppSnack(context, message: 'সার্চ করতে সমস্যা হয়েছে');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Seat Finding'), elevation: 0),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                _buildSearchHeader(),
                Expanded(
                  child: _isSearching
                      ? const Center(child: CircularProgressIndicator())
                      : RefreshIndicator(
                          onRefresh: _loadInitialData,
                          child: _results.isEmpty
                              ? _buildEmptyState()
                              : ListView.builder(
                                  padding: const EdgeInsets.all(16),
                                  itemCount: _results.length,
                                  itemBuilder: (context, index) {
                                    final student = _results[index];
                                    return _buildResultCard(student);
                                  },
                                ),
                        ),
                ),
              ],
            ),
    );
  }

  Widget _buildSearchHeader() {
    return Container(
      color: Theme.of(context).primaryColor,
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
      child: Column(
        children: [
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(8),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<int>(
                value: _selectedPlanId,
                isExpanded: true,
                hint: const Text('সীট প্ল্যান নির্বাচন করুন'),
                items: _plans.map((p) {
                  return DropdownMenuItem<int>(
                    value: p['id'],
                    child: Text(p['name'] ?? ''),
                  );
                }).toList(),
                onChanged: (val) {
                  setState(() => _selectedPlanId = val);
                },
              ),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: Container(
                  height: 48,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: TextField(
                    controller: _searchController,
                    decoration: const InputDecoration(
                      hintText: 'রোল বা নাম দিয়ে সার্চ করুন',
                      border: InputBorder.none,
                      contentPadding: EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 14,
                      ),
                      prefixIcon: Icon(Icons.search),
                    ),
                    onSubmitted: (_) => _search(),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              SizedBox(
                height: 48,
                child: ElevatedButton(
                  onPressed: _search,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.orange,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  child: const Text('সার্চ'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.person_search, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          Text(
            _searchController.text.isEmpty
                ? 'ছাত্র-ছাত্রী খুঁজুন'
                : 'কোনো ফলাফল পাওয়া যায়নি',
            style: const TextStyle(
              fontSize: 18,
              color: Colors.grey,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'সঠিক নাম বা রোল দিয়ে সার্চ করুন',
            style: TextStyle(color: Colors.grey),
          ),
        ],
      ),
    );
  }

  Widget _buildResultCard(dynamic student) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          // Top section: Photo, Name, Roll
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                // Photo
                CircleAvatar(
                  radius: 30,
                  backgroundColor: Colors.grey.shade200,
                  backgroundImage: student['photo_url'] != null
                      ? NetworkImage(student['photo_url'])
                      : null,
                  child: student['photo_url'] == null
                      ? const Icon(Icons.person, color: Colors.grey, size: 30)
                      : null,
                ),
                const SizedBox(width: 16),
                // Details
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        student['student_name'] ?? 'Unknown',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.black87,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Class: ${student['class_name'] ?? 'N/A'}${student['section_name'] != null ? ' | Sec: ${student['section_name']}' : ''}',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey.shade600,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'ID: ${student['student_id'] ?? 'N/A'}',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
                // Roll
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.blue.shade100),
                  ),
                  child: Column(
                    children: [
                      Text(
                        'ROLL',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: Colors.blue.shade700,
                        ),
                      ),
                      Text(
                        '${student['roll'] ?? '?'}',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.blue.shade900,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          // Divider
          Divider(height: 1, color: Colors.grey.shade200),
          // Bottom section: Seat Details
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: const BorderRadius.only(
                bottomLeft: Radius.circular(16),
                bottomRight: Radius.circular(16),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _buildInfoColumn(
                  'Room',
                  '${student['room_no'] ?? '?'}',
                  Icons.meeting_room,
                  Colors.orange,
                ),
                _buildInfoColumn(
                  'Column',
                  '${student['col_no'] ?? '?'}',
                  Icons.view_column,
                  Colors.purple,
                ),
                _buildInfoColumn(
                  'Bench',
                  '${student['bench_no'] ?? '?'}',
                  Icons.event_seat,
                  Colors.green,
                ),
                _buildInfoColumn(
                  'Position',
                  student['position']?.toString().toUpperCase() ?? '?',
                  Icons.person_pin_circle,
                  Colors.teal,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoColumn(
    String label,
    String value,
    IconData icon,
    Color color,
  ) {
    return Column(
      children: [
        Icon(icon, size: 20, color: color.withValues(alpha: 0.8)),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: Colors.black87,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
        ),
      ],
    );
  }
}
