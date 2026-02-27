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
          if (_plans.isNotEmpty) {
            _selectedPlanId = _plans.first['id'];
          }
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
      appBar: AppBar(
        title: const Text('Seat Finding'),
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                _buildSearchHeader(),
                Expanded(
                  child: _isSearching
                      ? const Center(child: CircularProgressIndicator())
                      : _results.isEmpty
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
                      hintText: 'ID, রোল বা নাম দিয়ে সার্চ করুন',
                      border: InputBorder.none,
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 14),
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
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
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
            _searchController.text.isEmpty ? 'ছাত্র-ছাত্রী খুঁজুন' : 'কোনো ফলাফল পাওয়া যায়নি',
            style: const TextStyle(fontSize: 18, color: Colors.grey, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          const Text(
            'সঠিক নাম, আইডি বা রোল দিয়ে সার্চ করুন',
            style: TextStyle(color: Colors.grey),
          ),
        ],
      ),
    );
  }

  Widget _buildResultCard(dynamic student) {
    return Card(
      elevation: 2,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        leading: CircleAvatar(
          backgroundColor: Colors.blue.shade100,
          child: Text(
            (student['roll'] ?? '?').toString(),
            style: TextStyle(color: Colors.blue.shade900, fontWeight: FontWeight.bold),
          ),
        ),
        title: Text(
          student['student_name'] ?? 'N/A',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Text('ID: ${student['student_id'] ?? 'N/A'}'),
        trailing: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: Colors.blue.shade700,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('ROOM', style: TextStyle(color: Colors.white70, fontSize: 10)),
              Text(
                student['room_no'] ?? '?',
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
