import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:intl/intl.dart';
import '../../../../core/network/dio_client.dart';
import 'dart:developer' as developer;
import 'dart:io';
import 'package:path_provider/path_provider.dart';
import 'package:open_filex/open_filex.dart';

class DetailedDueReportPage extends StatefulWidget {
  const DetailedDueReportPage({super.key});

  @override
  State<DetailedDueReportPage> createState() => _DetailedDueReportPageState();
}

class _DetailedDueReportPageState extends State<DetailedDueReportPage> {
  final Dio _dio = DioClient().dio;
  bool _loading = false;
  bool _downloading = false;
  List<dynamic> _fees = [];

  List<dynamic> _classes = [];
  List<dynamic> _sections = [];
  List<dynamic> _categories = [];
  List<dynamic> _academicYears = [];

  String? _selectedYearId;
  String? _selectedClassId;
  String? _selectedSectionId;
  String? _selectedCategoryId;
  String? _selectedMonth; // yyyy-MM
  String _selectedStatus = 'all';
  String _studentId = '';

  static const _bnMonths = {
    1: 'জানুয়ারি',
    2: 'ফেব্রুয়ারি',
    3: 'মার্চ',
    4: 'এপ্রিল',
    5: 'মে',
    6: 'জুন',
    7: 'জুলাই',
    8: 'আগস্ট',
    9: 'সেপ্টেম্বর',
    10: 'অক্টোবর',
    11: 'নভেম্বর',
    12: 'ডিসেম্বর',
  };

  @override
  void initState() {
    super.initState();
    _fetchMeta();
  }

  Future<void> _fetchMeta() async {
    try {
      setState(() => _loading = true);
      final responses = await Future.wait([
        _dio.get('meta/school'),
        _dio.get('meta/classes'),
        _dio.get('billing/config'),
      ]);

      if (mounted) {
        setState(() {
          final schoolData = responses[0].data;
          _academicYears = schoolData['academic_years'] ?? [];
          if (schoolData['current_academic_year'] != null) {
            _selectedYearId = schoolData['current_academic_year']['id']
                .toString();
          } else if (_academicYears.isNotEmpty) {
            _selectedYearId = _academicYears[0]['id'].toString();
          }

          _classes = responses[1].data;
          _categories = responses[2].data['categories'] ?? [];
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _loading = false);
      developer.log('Error fetching meta: $e');
    }
  }

  Future<void> _onClassChanged(String? classId) async {
    setState(() {
      _selectedClassId = classId;
      _selectedSectionId = null;
      _sections = [];
    });

    if (classId != null && classId.isNotEmpty) {
      try {
        final resp = await _dio.get(
          'meta/sections',
          queryParameters: {'class_id': classId},
        );
        if (mounted) {
          setState(() {
            _sections = resp.data;
          });
        }
      } catch (e) {
        developer.log('Error fetching sections: $e');
      }
    }
  }

  Future<void> _fetchReport() async {
    if (_selectedYearId == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('শিক্ষাবর্ষ নির্বাচন করুন')));
      return;
    }
    if (_selectedClassId == null || _selectedClassId!.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('শ্রেণি নির্বাচন করুন')));
      return;
    }
    if (_selectedSectionId == null || _selectedSectionId!.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('শাখা নির্বাচন করুন')));
      return;
    }

    try {
      setState(() => _loading = true);
      final params = {
        'academic_year_id': _selectedYearId,
        'class_id': _selectedClassId ?? '',
        'section_id': _selectedSectionId ?? '',
        'fee_category_id': _selectedCategoryId ?? '',
        'month': _selectedMonth ?? '',
        'student_id': _studentId,
        'status': _selectedStatus,
      };

      final resp = await _dio.get(
        'billing/reports/detailed-dues',
        queryParameters: params,
      );
      if (mounted) {
        setState(() {
          _fees = resp.data;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _loading = false);
      developer.log('Error fetching report: $e');
    }
  }

  void _resetFilters() {
    setState(() {
      _selectedClassId = null;
      _selectedSectionId = null;
      _selectedCategoryId = '';
      _selectedMonth = null;
      _selectedStatus = 'all';
      _fees = [];
      _studentId = '';

      // Reset to current academic year
      final currentYear = _academicYears.firstWhere(
        (y) => y['is_current'] == 1 || y['is_current'] == true,
        orElse: () => _academicYears.isNotEmpty ? _academicYears[0] : null,
      );
      if (currentYear != null) {
        _selectedYearId = currentYear['id'].toString();
      }
    });
  }

  Future<void> _pickMonth() async {
    final now = DateTime.now();
    final List<int> years = List.generate(10, (index) => 2020 + index);
    int selectedYear = _selectedMonth != null
        ? int.parse(_selectedMonth!.split('-')[0])
        : now.year;

    final result = await showDialog<String>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('মাস নির্বাচন করুন'),
              content: SizedBox(
                width: double.maxFinite,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    DropdownButton<int>(
                      value: selectedYear,
                      isExpanded: true,
                      items: years
                          .map(
                            (y) => DropdownMenuItem(
                              value: y,
                              child: Text(_toBengaliDigits(y.toString())),
                            ),
                          )
                          .toList(),
                      onChanged: (v) {
                        if (v != null) setDialogState(() => selectedYear = v);
                      },
                    ),
                    const Divider(),
                    Expanded(
                      child: GridView.builder(
                        shrinkWrap: true,
                        gridDelegate:
                            const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 3,
                              childAspectRatio: 2,
                            ),
                        itemCount: 12,
                        itemBuilder: (context, index) {
                          final m = index + 1;
                          final isSelected =
                              _selectedMonth ==
                              '$selectedYear-${m.toString().padLeft(2, '0')}';
                          return InkWell(
                            onTap: () => Navigator.pop(
                              context,
                              '$selectedYear-${m.toString().padLeft(2, '0')}',
                            ),
                            child: Container(
                              alignment: Alignment.center,
                              margin: const EdgeInsets.all(2),
                              decoration: BoxDecoration(
                                color: isSelected ? Colors.red.shade100 : null,
                                border: Border.all(
                                  color: isSelected
                                      ? Colors.red
                                      : Colors.grey.shade300,
                                ),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(_bnMonths[m] ?? ''),
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context, 'clear'),
                  child: const Text(
                    'ক্লিয়ার',
                    style: TextStyle(color: Colors.red),
                  ),
                ),
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('বন্ধ করুন'),
                ),
              ],
            );
          },
        );
      },
    );

    if (result == 'clear') {
      setState(() => _selectedMonth = null);
    } else if (result != null) {
      setState(() => _selectedMonth = result);
    }
  }

  Future<void> _downloadPdf() async {
    if (_selectedYearId == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('শিক্ষাবর্ষ নির্বাচন করুন')));
      return;
    }

    try {
      setState(() => _downloading = true);
      final params = {
        'academic_year_id': _selectedYearId,
        'class_id': _selectedClassId ?? '',
        'section_id': _selectedSectionId ?? '',
        'fee_category_id': _selectedCategoryId ?? '',
        'month': _selectedMonth ?? '',
        'student_id': _studentId,
        'status': _selectedStatus,
      };

      final response = await _dio.get(
        'billing/reports/detailed-dues/pdf',
        queryParameters: params,
        options: Options(
          responseType: ResponseType.bytes,
          headers: {'Accept': 'application/pdf'},
        ),
      );

      final directory = await getApplicationDocumentsDirectory();
      final filePath =
          '${directory.path}/Detailed_Due_Report_${DateTime.now().millisecondsSinceEpoch}.pdf';
      final file = File(filePath);
      await file.writeAsBytes(response.data);

      setState(() => _downloading = false);

      if (mounted) {
        // Automatically open the downloaded PDF
        await OpenFilex.open(filePath);

        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('পিডিএফ ডাউনলোড সফল হয়েছে এবং ওপেন হচ্ছে...'),
            duration: Duration(seconds: 2),
          ),
        );
      }
    } catch (e) {
      setState(() => _downloading = false);
      developer.log('PDF Download Error: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('পিডিএফ ডাউনলোডে সমস্যা হয়েছে।')),
        );
      }
    }
  }

  String _formatNumber(dynamic num) {
    if (num == null) return '০';
    final parsed = double.tryParse(num.toString()) ?? 0;
    return NumberFormat.decimalPattern('bn-BD').format(parsed);
  }

  String _toBengaliDigits(String input) {
    const englishToBengali = {
      '0': '০',
      '1': '১',
      '2': '২',
      '3': '৩',
      '4': '৪',
      '5': '৫',
      '6': '৬',
      '7': '৭',
      '8': '৮',
      '9': '৯',
    };
    return input
        .split('')
        .map((char) => englishToBengali[char] ?? char)
        .join('');
  }

  String _formatMonth(String? val) {
    if (val == null || val.isEmpty) return '';
    try {
      final parts = val.split('-');
      final year = int.parse(parts[0]);
      final month = int.parse(parts[1]);
      final bnMonth = _bnMonths[month] ?? month.toString();
      final bnYear = _toBengaliDigits(year.toString());
      return '$bnMonth $bnYear';
    } catch (_) {
      return val;
    }
  }

  double _calculateDue(dynamic fee) {
    final amount = double.tryParse(fee['amount']?.toString() ?? '0') ?? 0;
    final fine = double.tryParse(fee['fine_amount']?.toString() ?? '0') ?? 0;
    final paid = double.tryParse(fee['paid_amount']?.toString() ?? '0') ?? 0;
    final waiver = double.tryParse(fee['fine_waiver']?.toString() ?? '0') ?? 0;
    return (amount - paid) + (fine - waiver);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('বকেয়া আদায় রিপোর্ট'),
        actions: [
          if (_fees.isNotEmpty)
            IconButton(
              icon: _downloading
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.picture_as_pdf),
              tooltip: 'ডাউনলোড পিডিএফ',
              onPressed: _downloading ? null : _downloadPdf,
            ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'রিফ্রেশ',
            onPressed: _fetchReport,
          ),
        ],
      ),
      body: Column(
        children: [
          _buildFilters(),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _fees.isEmpty
                ? const Center(child: Text('কোন তথ্য পাওয়া যায়নি।'))
                : _buildReportList(),
          ),
          if (_fees.isNotEmpty) _buildSummaryFooter(),
        ],
      ),
    );
  }

  Widget _buildFilters() {
    return Container(
      padding: const EdgeInsets.all(12),
      margin: const EdgeInsets.all(12),
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
      child: ExpansionTile(
        title: const Text(
          'ফিল্টার করুন',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        initiallyExpanded: _fees.isEmpty,
        children: [
          LayoutBuilder(
            builder: (context, constraints) {
              return Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _buildDropdown(
                    label: 'শিক্ষাবর্ষ',
                    width: (constraints.maxWidth - 8) / 2,
                    value: _selectedYearId,
                    items: _academicYears
                        .map(
                          (y) => DropdownMenuItem(
                            value: y['id'].toString(),
                            child: Text(y['name_bn'] ?? y['name'] ?? ''),
                          ),
                        )
                        .toList(),
                    onChanged: (v) => setState(() => _selectedYearId = v),
                  ),
                  _buildDropdown(
                    label: 'শ্রেণি',
                    width: (constraints.maxWidth - 8) / 2,
                    value: _selectedClassId,
                    items: _classes
                        .map(
                          (c) => DropdownMenuItem(
                            value: c['id'].toString(),
                            child: Text(c['bangla_name'] ?? c['name'] ?? ''),
                          ),
                        )
                        .toList(),
                    onChanged: _onClassChanged,
                  ),
                  _buildDropdown(
                    label: 'শাখা',
                    width: (constraints.maxWidth - 8) / 2,
                    value: _selectedSectionId,
                    items: _sections
                        .map(
                          (s) => DropdownMenuItem(
                            value: s['id'].toString(),
                            child: Text(s['bangla_name'] ?? s['name'] ?? ''),
                          ),
                        )
                        .toList(),
                    onChanged: (v) => setState(() => _selectedSectionId = v),
                  ),
                  _buildDropdown(
                    label: 'ক্যাটাগরি',
                    width: (constraints.maxWidth - 8) / 2,
                    value: _selectedCategoryId,
                    items: [
                      const DropdownMenuItem(
                        value: '',
                        child: Text('সকল ক্যাটাগরি'),
                      ),
                      ..._categories.map(
                        (cat) => DropdownMenuItem(
                          value: cat['id'].toString(),
                          child: Text(cat['name'] ?? ''),
                        ),
                      ),
                    ],
                    onChanged: (v) => setState(() => _selectedCategoryId = v),
                  ),
                  SizedBox(
                    width: (constraints.maxWidth - 8) / 2,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'মাসের নাম',
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey,
                          ),
                        ),
                        const SizedBox(height: 4),
                        InkWell(
                          onTap: _pickMonth,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              border: Border.all(color: Colors.grey.shade300),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Text(
                                    _selectedMonth == null
                                        ? 'নির্বাচন করুন'
                                        : _formatMonth(_selectedMonth),
                                    style: const TextStyle(fontSize: 12),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                                if (_selectedMonth != null)
                                  GestureDetector(
                                    onTap: () =>
                                        setState(() => _selectedMonth = null),
                                    child: const Icon(
                                      Icons.close,
                                      size: 16,
                                      color: Colors.red,
                                    ),
                                  )
                                else
                                  const Icon(Icons.calendar_today, size: 16),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  _buildDropdown(
                    label: 'অবস্থা',
                    width: (constraints.maxWidth - 8) / 2,
                    value: _selectedStatus,
                    items: const [
                      DropdownMenuItem(value: 'all', child: Text('সবগুলো')),
                      DropdownMenuItem(value: 'due', child: Text('বকেয়া (সব)')),
                      DropdownMenuItem(
                        value: 'unpaid',
                        child: Text('অপরিশোধিত'),
                      ),
                      DropdownMenuItem(value: 'partial', child: Text('আংশিক')),
                      DropdownMenuItem(value: 'paid', child: Text('পরিশোধিত')),
                    ],
                    onChanged: (v) => setState(() => _selectedStatus = v!),
                  ),
                ],
              );
            },
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: _fetchReport,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red.shade600,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  child: const Text('রিপোর্ট অনুসন্ধান করুন'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                flex: 1,
                child: OutlinedButton(
                  onPressed: _resetFilters,
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    side: BorderSide(color: Colors.red.shade600),
                    foregroundColor: Colors.red.shade600,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  child: const Text('রিসেট'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDropdown({
    required String label,
    required List<DropdownMenuItem<String>> items,
    required String? value,
    required void Function(String?) onChanged,
    required double width,
  }) {
    return SizedBox(
      width: width,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.bold,
              color: Colors.grey,
            ),
          ),
          const SizedBox(height: 4),
          DropdownButtonFormField<String>(
            isExpanded: true,
            initialValue: value,
            items: items,
            onChanged: onChanged,
            decoration: InputDecoration(
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 8,
                vertical: 0,
              ),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            style: const TextStyle(fontSize: 12, color: Colors.black),
          ),
        ],
      ),
    );
  }

  Widget _buildReportList() {
    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      itemCount: _fees.length,
      itemBuilder: (context, index) {
        final fee = _fees[index];
        final due = _calculateDue(fee);

        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            fee['student_name_bn'] ??
                                fee['student_name_en'] ??
                                'Unknown',
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
                            ),
                          ),
                          Text(
                            'ID: ${fee['student_code']} | Roll: ${fee['roll_no']}',
                            style: const TextStyle(
                              color: Colors.grey,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                    _buildStatusBadge(fee['status']),
                  ],
                ),
                const Divider(height: 16),
                Row(
                  children: [
                    _buildInfoColumn('ক্যাটাগরি', fee['category_name']),
                    _buildInfoColumn('মাস', _formatMonth(fee['month'])),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildAmountInfo('নির্ধারিত', fee['amount']),
                    _buildAmountInfo('জরিমানা', fee['fine_amount']),
                    _buildAmountInfo('পরিশোধিত', fee['paid_amount']),
                    _buildAmountInfo('বকেয়া', due, isDue: true),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildInfoColumn(String label, String? value) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey)),
          Text(
            value ?? '-',
            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
          ),
        ],
      ),
    );
  }

  Widget _buildAmountInfo(String label, dynamic value, {bool isDue = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey)),
        Text(
          '৳${_formatNumber(value)}',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 14,
            color: isDue ? Colors.red : Colors.black,
          ),
        ),
      ],
    );
  }

  Widget _buildStatusBadge(String? status) {
    Color color = Colors.grey;
    String text = status ?? 'Unknown';

    if (status == 'paid') {
      color = Colors.green;
      text = 'পরিশোধিত';
    } else if (status == 'partial') {
      color = Colors.orange;
      text = 'আংশিক';
    } else if (status == 'unpaid') {
      color = Colors.red;
      text = 'অপরিশোধিত';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.bold,
          color: color,
        ),
      ),
    );
  }

  Widget _buildSummaryFooter() {
    double totalDue = 0;
    double totalPaid = 0;
    for (var fee in _fees) {
      totalDue += _calculateDue(fee);
      totalPaid += double.tryParse(fee['paid_amount']?.toString() ?? '0') ?? 0;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      decoration: const BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black12,
            blurRadius: 4,
            offset: Offset(0, -2),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          _buildSummaryItem('মোট পরিশোধিত', totalPaid, Colors.green),
          _buildSummaryItem('মোট বকেয়া', totalDue, Colors.red),
        ],
      ),
    );
  }

  Widget _buildSummaryItem(String label, double amount, Color color) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
        ),
        Text(
          '৳${_formatNumber(amount)}',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
      ],
    );
  }
}
