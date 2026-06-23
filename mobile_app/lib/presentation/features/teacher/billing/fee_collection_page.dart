import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

class FeeCollectionPage extends ConsumerStatefulWidget {
  const FeeCollectionPage({super.key});

  @override
  ConsumerState<FeeCollectionPage> createState() => _FeeCollectionPageState();
}

class _FeeCollectionPageState extends ConsumerState<FeeCollectionPage> {
  final Dio _dio = DioClient().dio;

  // Loading states
  bool _isLoadingStudents = true;

  // All students from ALL teacher sections
  List<dynamic> _allStudentsList = [];
  List<dynamic> _filteredStudents = [];
  List<int> _allowedSectionIds = [];

  // Search controllers
  final TextEditingController _rollNameController = TextEditingController();
  final TextEditingController _studentIdController = TextEditingController();

  // Selected student & fee data
  Map<String, dynamic>? _selectedStudent;
  bool _isLoadingFees = false;
  List<dynamic> _dueFees = [];
  bool _isFineEnabled = false;
  List<Map<String, dynamic>> _selectedFeesToPay = [];
  bool _isPaying = false;
  int? _academicYearId;
  int? _currentSchoolId;

  @override
  void initState() {
    super.initState();
    _fetchSchoolMeta();
    _loadAllSectionStudents();
  }

  @override
  void dispose() {
    _rollNameController.dispose();
    _studentIdController.dispose();
    super.dispose();
  }

  Future<void> _fetchSchoolMeta() async {
    try {
      final res = await _dio.get('meta/school');
      if (res.data is Map && res.data['academic_year_id'] != null) {
        _academicYearId = int.tryParse(res.data['academic_year_id'].toString());
      }
      if (res.data is Map && res.data['id'] != null) {
        _currentSchoolId = int.tryParse(res.data['id'].toString());
      }
    } catch (_) {}
  }

  /// Loads students from ALL sections assigned to this teacher
  Future<void> _loadAllSectionStudents() async {
    setState(() => _isLoadingStudents = true);
    try {
      // Step 1: Get all teacher's class-section meta
      final metaRes = await _dio.get('teacher/students-attendance/class/meta');
      if (!mounted) return;

      final classesInfo = metaRes.data is List ? metaRes.data as List : [];

      // Step 2: Collect all section IDs
      final List<dynamic> allStudents = [];
      final List<int> sectionIds = [];
      for (final cls in classesInfo) {
        final sections = cls['sections'] as List? ?? [];
        for (final sec in sections) {
          final sId = int.tryParse(sec['id']?.toString() ?? '');
          if (sId != null) sectionIds.add(sId);
          try {
            final studRes = await _dio.get(
              'teacher/students-attendance/class/${sec['id']}/students',
            );
            if (studRes.data != null && studRes.data['students'] != null) {
              final students = studRes.data['students'] as List;
              // Tag each student with class & section name for display
              for (final s in students) {
                s['class_name'] = cls['class_name'] ?? '';
                s['section_name'] = sec['name'] ?? '';
              }
              allStudents.addAll(students);
            }
          } catch (_) {
            // If one section fails, continue with others
          }
        }
      }

      if (mounted) {
        setState(() {
          _allStudentsList = allStudents;
          _allowedSectionIds = sectionIds;
        });
        // 🔑 KEY FIX: Re-run filter in case user already typed something
        // while the list was still loading.
        _onFilterChanged();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('শিক্ষার্থী লোড ব্যর্থ: $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoadingStudents = false);
    }
  }

  /// Shared filter logic — returns matching students without setState
  List<dynamic> _computeFiltered() {
    final rollNameQ = _rollNameController.text.trim().toLowerCase();
    final studentIdQ = _studentIdController.text.trim().toLowerCase();

    if (rollNameQ.isEmpty && studentIdQ.isEmpty) return [];

    return _allStudentsList.where((s) {
      bool matchesRollName = false;
      if (rollNameQ.isNotEmpty) {
        final roll = s['roll']?.toString().trim().toLowerCase() ?? '';
        final name = s['name']?.toString().toLowerCase() ?? '';
        final rollExact = roll == rollNameQ;
        final nameContains = name.contains(rollNameQ);
        matchesRollName = rollExact || nameContains;
      }

      bool matchesStudentId = false;
      if (studentIdQ.isNotEmpty) {
        final sid = s['student_id']?.toString().trim().toLowerCase() ?? '';
        matchesStudentId = sid == studentIdQ || sid.contains(studentIdQ);
      }

      // Use OR logic as requested: match either condition if both are provided
      if (rollNameQ.isNotEmpty && studentIdQ.isNotEmpty) {
        return matchesRollName || matchesStudentId;
      }
      // Otherwise match whichever is provided
      return matchesRollName || matchesStudentId;
    }).toList();
  }

  void _onFilterChanged() {
    setState(() => _filteredStudents = _computeFiltered());
  }

  /// Called when user taps "খুঁজুন" or presses search on keyboard.
  Future<void> _doSearch() async {
    FocusScope.of(context).unfocus();

    final rollNameQ = _rollNameController.text.trim();
    final studentIdQ = _studentIdController.text.trim();

    if (rollNameQ.isEmpty && studentIdQ.isEmpty) {
      setState(() => _filteredStudents = []);
      return;
    }

    // Attempt server-side search (matching web behavior for broader access)
    setState(() => _isLoadingStudents = true);

    try {
      final queryParams = {
        'roll_no': rollNameQ,
        'q': studentIdQ,
      };
      if (_academicYearId != null) {
        queryParams['academic_year_id'] = _academicYearId.toString();
      }

      // Restrict search to teacher's assigned sections (web parity)
      if (_allowedSectionIds.isNotEmpty) {
        queryParams['allowed_section_ids'] = _allowedSectionIds.join(',');
      }

      final res = await _dio.get('principal/students/search', queryParameters: queryParams);
      final List results = res.data is List ? res.data : [];

      // Map API fields (full_name, roll_no) to the keys used in our widget
      final mappedResults = results.map((s) {
        return {
          ...s,
          'name': s['full_name'] ?? s['name_bn'] ?? s['name_en'] ?? 'Unknown',
          'roll': s['roll_no']?.toString() ?? '',
        };
      }).toList();

      if (mounted) {
        setState(() {
          _filteredStudents = mappedResults;
          _isLoadingStudents = false;
        });

        // If exactly one match → go directly to fee collection
        if (mappedResults.length == 1) {
          _fetchDueFees(Map<String, dynamic>.from(mappedResults.first));
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingStudents = false);
        // Fallback to local filtering if API fails or offline
        final localResults = _computeFiltered();
        setState(() => _filteredStudents = localResults);
        if (localResults.length == 1) {
          _fetchDueFees(Map<String, dynamic>.from(localResults.first));
        }
      }
    }
  }


  Future<void> _fetchDueFees(Map<String, dynamic> student) async {
    setState(() {
      _selectedStudent = student;
      _isLoadingFees = true;
      _dueFees = [];
      _selectedFeesToPay = [];
    });

    try {
      final res = await _dio.get('billing/fees/student/${student['id']}/due');
      if (mounted && res.data is Map) {
        setState(() {
          _dueFees = res.data['due_fees'] ?? [];
          _isFineEnabled = res.data['is_fine_enabled'] ?? false;
          _selectedFeesToPay = _dueFees.map((fee) {
            double fine = _isFineEnabled
                ? double.tryParse(fee['calculated_fine']?.toString() ?? '0') ?? 0
                : 0;
            double due =
                (double.tryParse(fee['amount']?.toString() ?? '0') ?? 0) -
                (double.tryParse(fee['paid_amount']?.toString() ?? '0') ?? 0);
            return {
              'student_fee_id': fee['id'],
              'amount': due + fine,
              'base_due': due,
              'fine_amount': fine,
              'category_name': fee['formatted_category_name'] ?? 'Fee',
              'selected': false,
            };
          }).toList();
        });
      }
    } catch (e) {
      if (mounted) {
        String msg = e.toString();
        if (e is DioException && e.response?.data is Map) {
          msg = e.response?.data['message'] ?? msg;
        }
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: Colors.red),
        );
        setState(() => _selectedStudent = null);
      }
    } finally {
      if (mounted) setState(() => _isLoadingFees = false);
    }
  }

  void _toggleFeeSelection(int index, bool? value) {
    setState(() => _selectedFeesToPay[index]['selected'] = value ?? false);
  }

  bool get _allSelected =>
      _selectedFeesToPay.isNotEmpty &&
      _selectedFeesToPay.every((f) => f['selected'] == true);

  void _toggleSelectAll() {
    final newVal = !_allSelected;
    setState(() {
      for (final f in _selectedFeesToPay) {
        f['selected'] = newVal;
      }
    });
  }

  double get _totalSelectedAmount {
    return _selectedFeesToPay
        .where((f) => f['selected'] == true)
        .fold(0.0, (sum, f) => sum + (f['amount'] as double));
  }

  Future<void> _collectFees() async {
    final selected =
        _selectedFeesToPay.where((f) => f['selected'] == true).toList();
    if (selected.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('কমপক্ষে একটি ফি নির্বাচন করুন')),
      );
      return;
    }

    setState(() => _isPaying = true);

    try {
      final payload = {
        'student_id': _selectedStudent!['id'],
        'academic_year_id': _academicYearId ?? 1,
        'school_id': _currentSchoolId ?? _selectedStudent!['school_id'],
        'payment_method': 'cash',
        'received_at': DateTime.now().toIso8601String(),
        'fees': selected
            .map((f) => {
                  'student_fee_id': f['student_fee_id'],
                  'amount': f['amount'],
                  'fine_amount': f['fine_amount'],
                })
            .toList(),
      };

      final res = await _dio.post('billing/fees/collect', data: payload);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res.data['message'] ?? 'পেমেন্ট সফলভাবে সম্পন্ন হয়েছে!'),
            backgroundColor: Colors.green.shade600,
            behavior: SnackBarBehavior.floating,
          ),
        );
        setState(() {
          _selectedStudent = null;
          _dueFees = [];
          _selectedFeesToPay = [];
          _rollNameController.clear();
          _studentIdController.clear();
        });
      }
    } catch (e) {
      if (mounted) {
        String msg = 'পেমেন্ট প্রসেস করতে ব্যর্থ হয়েছে';
        if (e is DioException) {
          if (e.response?.data is Map) {
            msg = e.response?.data['message'] ??
                e.response?.data['error'] ??
                msg;
          } else {
            msg = 'সার্ভার ত্রুটি: ${e.response?.statusCode ?? "Unknown"}';
          }
        }
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(msg),
            backgroundColor: Colors.red.shade600,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isPaying = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7F9),
      appBar: AppBar(
        title: const Text('ফি সংগ্রহ', style: TextStyle(fontWeight: FontWeight.bold)),
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1A1D1F),
      ),
      body: Column(
        children: [
          _buildSearchHeader(),
          Expanded(
            child: _selectedStudent == null
                ? _buildResultsList()
                : ListView(
                    physics: const BouncingScrollPhysics(),
                    children: [
                      _buildStudentHeader(),
                      _buildDuesSection(),
                    ],
                  ),
          ),
          if (_selectedStudent != null && _dueFees.isNotEmpty && !_isLoadingFees)
            _buildBottomPaymentBar(),
        ],
      ),
    );
  }

  Widget _buildSearchHeader() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Expanded(
                child: _buildBeautifulField(
                  controller: _rollNameController,
                  label: 'রোল বা নাম',
                  hint: 'যেমন: 10 বা করিম',
                  icon: Icons.person_search_outlined,
                  onChanged: (_) => _onFilterChanged(),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                child: Text(
                  'অথবা',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey.shade400,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              Expanded(
                child: _buildBeautifulField(
                  controller: _studentIdController,
                  label: 'শিক্ষার্থী আইডি',
                  hint: 'সরাসরি আইডি',
                  icon: Icons.badge_outlined,
                  onChanged: (_) => _onFilterChanged(),
                  onSubmitted: (_) => _doSearch(),
                  action: TextInputAction.search,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              if (_isLoadingStudents)
                Row(
                  children: [
                    SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.indigo.shade300),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      'লোড হচ্ছে…',
                      style: TextStyle(fontSize: 12, color: Colors.indigo.shade300),
                    ),
                  ],
                ),
              const Spacer(),
              TextButton.icon(
                onPressed: () {
                  _rollNameController.clear();
                  _studentIdController.clear();
                  setState(() {
                    _filteredStudents = [];
                    _selectedStudent = null;
                  });
                },
                icon: const Icon(Icons.refresh, size: 18),
                label: const Text('রিসেট'),
                style: TextButton.styleFrom(foregroundColor: Colors.grey.shade600),
              ),
              const SizedBox(width: 8),
              ElevatedButton.icon(
                onPressed: _isLoadingStudents ? null : _doSearch,
                icon: const Icon(Icons.search, size: 18),
                label: const Text('খুঁজুন'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.indigo,
                  foregroundColor: Colors.white,
                  elevation: 2,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildBeautifulField({
    required TextEditingController controller,
    required String label,
    required String hint,
    required IconData icon,
    Function(String)? onChanged,
    Function(String)? onSubmitted,
    TextInputAction action = TextInputAction.next,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF00BF6D).withOpacity(0.7),
          ),
        ),
        const SizedBox(height: 4),
        TextField(
          controller: controller,
          onChanged: onChanged,
          onSubmitted: onSubmitted,
          textInputAction: action,
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: TextStyle(fontSize: 13, color: Colors.grey.shade400),
            prefixIcon: Icon(icon, size: 18, color: const Color(0xFF00BF6D).withOpacity(0.3)),
            filled: true,
            fillColor: const Color(0xFFF9FAFB),
            isDense: true,
            contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey.shade200),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey.shade200),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFF00BF6D), width: 1.5),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildStudentHeader() {
    final photoUrl = _selectedStudent!['photo_url']?.toString();
    return Container(
      margin: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF00BF6D), Color(0xFF0FD68A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF00BF6D).withOpacity(0.25),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(2),
            decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
            child: CircleAvatar(
              radius: 32,
              backgroundColor: const Color(0xFFF0FDF4),
              backgroundImage: (photoUrl != null && photoUrl.isNotEmpty) ? NetworkImage(photoUrl) : null,
              child: (photoUrl == null || photoUrl.isEmpty)
                  ? const Icon(Icons.person, size: 32, color: Color(0xFF00BF6D))
                  : null,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _selectedStudent!['name'] ?? '',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 6),
                Wrap(
                  spacing: 8,
                  runSpacing: 4,
                  children: [
                    _buildStudentBadge(Icons.school, 'শ্রেণি: ${_selectedStudent!['class_name'] ?? ''} (${_selectedStudent!['section_name'] ?? ''})'),
                    _buildStudentBadge(Icons.tag, 'রোল: ${_selectedStudent!['roll'] ?? ''}'),
                    _buildStudentBadge(Icons.badge, 'আইডি: ${_selectedStudent!['student_id'] ?? ''}'),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStudentBadge(IconData icon, String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.18),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: Colors.white),
          const SizedBox(width: 6),
          Text(
            text,
            style: const TextStyle(fontSize: 12, color: Colors.white, fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }

  Widget _buildDuesSection() {
    if (_isLoadingFees) {
      return const Column(
        children: [
          SizedBox(height: 60),
          Center(child: CircularProgressIndicator()),
          SizedBox(height: 16),
          Text('বকেয়া তথ্য লোড হচ্ছে…'),
        ],
      );
    }

    if (_dueFees.isEmpty) {
      return Container(
        padding: const EdgeInsets.symmetric(vertical: 80),
        alignment: Alignment.center,
        child: Column(
          children: [
            Icon(Icons.check_circle_outline, size: 64, color: Colors.green.shade200),
            const SizedBox(height: 16),
            const Text(
              'কোনো বকেয়া পাওয়া যায়নি',
              style: TextStyle(fontSize: 16, color: Colors.green, fontWeight: FontWeight.bold),
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'বকেয়া তালিকা (${_dueFees.length})',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.blueGrey),
              ),
              InkWell(
                onTap: _toggleSelectAll,
                child: Text(
                  _allSelected ? 'সব বাতিল' : 'সবই সিলেক্ট',
                  style: TextStyle(color: const Color(0xFF00BF6D), fontSize: 12, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        ),
        ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: _selectedFeesToPay.length,
          itemBuilder: (context, index) {
            final f = _selectedFeesToPay[index];
            final double baseDue = f['base_due'] as double;
            final double fine = f['fine_amount'] as double;
            final double total = f['amount'] as double;
            final bool isSelected = f['selected'] == true;

            return AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              decoration: BoxDecoration(
                color: isSelected ? const Color(0xFFF0FDF4) : Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isSelected ? const Color(0xFF00BF6D).withOpacity(0.5) : Colors.grey.shade200,
                  width: isSelected ? 1.5 : 1.0,
                ),
                boxShadow: [
                  if (isSelected)
                    BoxShadow(color: const Color(0xFF00BF6D).withOpacity(0.05), blurRadius: 4, offset: const Offset(0, 2)),
                ],
              ),
              child: ListTile(
                contentPadding: const EdgeInsets.fromLTRB(8, 4, 16, 4),
                leading: Checkbox(
                  value: isSelected,
                  onChanged: (val) => _toggleFeeSelection(index, val),
                  activeColor: const Color(0xFF00BF6D),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                ),
                title: Text(
                  f['category_name'],
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                    color: isSelected ? const Color(0xFF065F46) : const Color(0xFF1A1D1F),
                  ),
                ),
                subtitle: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const SizedBox(height: 2),
                    Text('বকেয়া: ৳${baseDue.toStringAsFixed(2)}', style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                    if (fine > 0)
                      Container(
                        margin: const EdgeInsets.only(top: 2),
                        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                        decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(4)),
                        child: Text(
                          'জরিমানা: ৳${fine.toStringAsFixed(2)}',
                          style: TextStyle(color: Colors.red.shade700, fontSize: 10, fontWeight: FontWeight.bold),
                        ),
                      ),
                  ],
                ),
                trailing: Text(
                  '৳${total.toStringAsFixed(2)}',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                    color: isSelected ? const Color(0xFF00BF6D) : const Color(0xFF111111),
                  ),
                ),
                onTap: () => _toggleFeeSelection(index, !isSelected),
              ),
            );
          },
        ),
        const SizedBox(height: 120),
      ],
    );
  }

  Widget _buildBottomPaymentBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 15, offset: const Offset(0, -4)),
        ],
      ),
      child: SafeArea(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text('নির্বাচিত মোট', style: TextStyle(fontSize: 11, color: Colors.grey.shade500, fontWeight: FontWeight.bold)),
                const SizedBox(height: 2),
                Text(
                  '৳${_totalSelectedAmount.toStringAsFixed(2)}',
                  style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Color(0xFF1A1D1F)),
                ),
              ],
            ),
            const SizedBox(width: 16),
            ElevatedButton.icon(
              onPressed: (_totalSelectedAmount > 0 && !_isPaying) ? _collectFees : null,
              icon: _isPaying
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Icon(Icons.check_circle_outline, size: 18),
              label: const Text('গ্রহণ করুন', style: TextStyle(letterSpacing: 0.3)),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF00BF6D),
                foregroundColor: Colors.white,
                elevation: 0,
                shadowColor: Colors.transparent,
                padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 20),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                textStyle: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildResultsList() {
    final rollNameQ = _rollNameController.text.trim();
    final studentIdQ = _studentIdController.text.trim();

    if (rollNameQ.isEmpty && studentIdQ.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.person_search, size: 64, color: Colors.grey.shade300),
            const SizedBox(height: 12),
            Text(
              'রোল/নাম অথবা শিক্ষার্থী আইডি লিখুন',
              style: TextStyle(color: Colors.grey.shade500, fontSize: 15),
            ),
            if (_isLoadingStudents) ...[
              const SizedBox(height: 16),
              const CircularProgressIndicator(strokeWidth: 2),
              const SizedBox(height: 8),
              Text(
                'তালিকা প্রস্তুত হচ্ছে…',
                style: TextStyle(color: Colors.grey.shade400, fontSize: 13),
              ),
            ],
          ],
        ),
      );
    }

    if (_isLoadingStudents && _filteredStudents.isEmpty) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 12),
            Text('শিক্ষার্থী খোঁজা হচ্ছে…'),
          ],
        ),
      );
    }

    if (_filteredStudents.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.search_off, size: 64, color: Colors.grey.shade300),
            const SizedBox(height: 12),
            Text(
              'কোনো শিক্ষার্থী পাওয়া যায়নি',
              style: TextStyle(color: Colors.grey.shade500, fontSize: 15),
            ),
            if (_isLoadingStudents)
              Padding(
                padding: const EdgeInsets.only(top: 12),
                child: Text(
                  'তালিকা এখনও লোড হচ্ছে, একটু অপেক্ষা করুন…',
                  style: TextStyle(color: Colors.orange.shade400, fontSize: 13),
                  textAlign: TextAlign.center,
                ),
              ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.symmetric(vertical: 8),
      physics: const BouncingScrollPhysics(),
      itemCount: _filteredStudents.length,
      itemBuilder: (context, index) {
        final student = _filteredStudents[index];
        final photoUrl = student['photo_url']?.toString();
        return Card(
          elevation: 0,
          margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: BorderSide(color: Colors.grey.shade100),
          ),
          child: ListTile(
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            leading: CircleAvatar(
              radius: 24,
              backgroundColor: const Color(0xFFF0FDF4),
              backgroundImage: (photoUrl != null && photoUrl.isNotEmpty) ? NetworkImage(photoUrl) : null,
              child: (photoUrl == null || photoUrl.isEmpty)
                  ? Text(
                      student['roll']?.toString() ?? '?',
                      style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF00BF6D)),
                    )
                  : null,
            ),
            title: Text(
              student['name'] ?? 'Unknown',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF1A1D1F)),
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 4),
                Text(
                  'শ্রেণি: ${student['class_name'] ?? ''}  শাখা: ${student['section_name'] ?? ''}  রোল: ${student['roll'] ?? ''}',
                  style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                ),
                if (student['student_id'] != null)
                  Text(
                    'আইডি: ${student['student_id']}',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF00BF6D)),
                  ),
              ],
            ),
            trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
            onTap: () => _fetchDueFees(student),
          ),
        );
      },
    );
  }
}
