import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import 'package:intl/intl.dart';

class CashDepositPage extends StatefulWidget {
  const CashDepositPage({super.key});

  @override
  State<CashDepositPage> createState() => _CashDepositPageState();
}

class _CashDepositPageState extends State<CashDepositPage> {
  final Dio _dio = DioClient().dio;
  bool _isLoading = true;
  String _error = '';
  
  Map<String, dynamic>? _data;
  List<dynamic> _breakdown = [];
  final List<TextEditingController> _controllers = [];
  
  DateTime _fromDate = DateTime(DateTime.now().year, DateTime.now().month, 1);
  DateTime _toDate = DateTime.now();

  bool _isDepositing = false;
  double _totalToDeposit = 0;

  @override
  void initState() {
    super.initState();
    _fetchTransferStats();
  }

  @override
  void dispose() {
    for (var controller in _controllers) {
      controller.dispose();
    }
    super.dispose();
  }

  String _formatBengaliMonth(String? monthStr) {
    if (monthStr == null || monthStr.isEmpty) return 'অন্যান্য';
    try {
      final parts = monthStr.split('-');
      if (parts.length != 2) return monthStr;
      
      final year = parts[0];
      final month = parts[1];
      
      const months = {
        '01': 'জানুয়ারী', '02': 'ফেব্রুয়ারী', '03': 'মার্চ', '04': 'এপ্রিল',
        '05': 'মে', '06': 'জুন', '07': 'জুলাই', '08': 'আগস্ট',
        '09': 'সেপ্টেম্বর', '10': 'অক্টোবর', '11': 'নভেম্বর', '12': 'ডিসেম্বর'
      };

      String bengaliYear = year.split('').map((e) {
        const numerals = {'0': '০', '1': '১', '2': '২', '3': '৩', '4': '৪', '5': '৫', '6': '৬', '7': '৭', '8': '৮', '9': '৯'};
        return numerals[e] ?? e;
      }).join('');

      return '${months[month] ?? month}, $bengaliYear';
    } catch (e) {
      return monthStr;
    }
  }

  Future<void> _fetchTransferStats() async {
    setState(() {
      _isLoading = true;
      _error = '';
    });
    try {
      final res = await _dio.get('billing/reports/teacher-cash-transfer', queryParameters: {
        'from_date': DateFormat('yyyy-MM-dd').format(_fromDate),
        'to_date': DateFormat('yyyy-MM-dd').format(_toDate),
      });
      if (mounted) {
        final data = res.data;
        final breakdown = data['breakdown'] as List? ?? [];
        
        // Dispose old ones
        for (var c in _controllers) {
          c.dispose();
        }
        _controllers.clear();
        
        // Filter out breakdown items with 0 remaining to request
        _breakdown = breakdown.where((item) => (double.tryParse(item['remaining_to_request']?.toString() ?? '0') ?? 0) > 0).toList();

        for (var item in _breakdown) {
          final amt = double.tryParse(item['remaining_to_request']?.toString() ?? '0') ?? 0;
          _controllers.add(TextEditingController(text: amt.toStringAsFixed(0)));
        }

        setState(() {
          _data = data;
          _calculateTotal();
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = 'Failed to load data: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _calculateTotal() {
    double total = 0;
    for (var controller in _controllers) {
      total += double.tryParse(controller.text) ?? 0;
    }
    setState(() {
      _totalToDeposit = total;
    });
  }

  Future<void> _pickDateRange() async {
    final picked = await showDateRangePicker(
      context: context,
      initialDateRange: DateTimeRange(start: _fromDate, end: _toDate),
      firstDate: DateTime(2020),
      lastDate: DateTime(DateTime.now().year + 1),
    );
    if (picked != null) {
      setState(() {
        _fromDate = picked.start;
        _toDate = picked.end;
      });
      _fetchTransferStats();
    }
  }

  Future<void> _showConfirmation() async {
    if (_totalToDeposit <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('জমা দেওয়ার জন্য কোনো পরিমাণ নেই')));
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('নিশ্চিত করুন', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text('আপনি কি মোট ৳${_totalToDeposit.toStringAsFixed(2)} টাকা ক্যাশিয়ারের নিকট জমা দেওয়ার আবেদন করতে চান?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('না', style: TextStyle(color: Colors.grey))),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF00BF6D), foregroundColor: Colors.white),
            child: const Text('হ্যাঁ'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      _submitDeposit();
    }
  }

  Future<void> _submitDeposit() async {
    final List<Map<String, dynamic>> items = [];
    for (int i = 0; i < _breakdown.length; i++) {
        final amt = double.tryParse(_controllers[i].text) ?? 0;
        final maxAmt = double.tryParse(_breakdown[i]['remaining_to_request']?.toString() ?? '0') ?? 0;

        if (amt > maxAmt) {
          final catName = _breakdown[i]['category_name'] ?? 'ফি';
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('$catName খাতে ৳$maxAmt এর বেশি জমা দেওয়া সম্ভব নয়।'),
              backgroundColor: Colors.red,
            ),
          );
          return;
        }

        if (amt > 0) {
            items.add({
                'amount': amt,
                'fee_category_id': _breakdown[i]['fee_category_id'],
                'month': _breakdown[i]['month']
            });
        }
    }

    if (items.isEmpty) return;

    setState(() => _isDepositing = true);
    try {
      await _dio.post('billing/reports/teacher-cash-transfer/deposit', data: {'items': items});
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('জমা আবেদন সফলভাবে ক্যাশিয়ারের নিকট পাঠানো হয়েছে!'), backgroundColor: Colors.green)
        );
        _fetchTransferStats(); // Refresh
      }
    } catch (e) {
      if (mounted) {
        String msg = 'আবেদন ব্যর্থ হয়েছে';
        if (e is DioException && e.response?.data is Map) {
          msg = e.response?.data['message'] ?? msg;
        }
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg), backgroundColor: Colors.red));
      }
    } finally {
      if (mounted) setState(() => _isDepositing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7F9),
      appBar: AppBar(
        title: const Text('ক্যাশ ডিপোজিট', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1A1D1F),
        elevation: 1,
        actions: [
          IconButton(
            icon: const Icon(Icons.date_range_outlined, color: Color(0xFF00BF6D)),
            onPressed: _pickDateRange,
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) return const Center(child: CircularProgressIndicator(color: Color(0xFF00BF6D)));
    if (_error.isNotEmpty) return Center(child: Text(_error, style: const TextStyle(color: Colors.red)));

    return RefreshIndicator(
      onRefresh: _fetchTransferStats,
      color: const Color(0xFF00BF6D),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildDateBanner(),
            const SizedBox(height: 20),
            Row(
              children: [
                _StatCard(title: 'সংগৃহীত', amount: _data?['total_collected']?.toString() ?? '0', color: Colors.blue),
                const SizedBox(width: 12),
                _StatCard(title: 'জমাকৃত (গৃহীত)', amount: _data?['total_received']?.toString() ?? '0', color: Colors.green),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(child: _buildRemainingCard()),
                if ((double.tryParse(_data?['total_pending']?.toString() ?? '0') ?? 0) > 0) ...[
                  const SizedBox(width: 12),
                  Expanded(child: _buildPendingCard()),
                ],
              ],
            ),
            const SizedBox(height: 28),
            _buildSectionTitle('খাত ভিত্তিক বকেয়া নগদ', Icons.account_balance_wallet_outlined),
            const SizedBox(height: 12),
            if (_breakdown.isEmpty)
              _buildEmptyState('জমা দেওয়ার মতো কোনো নগদ অবশিষ্ট নেই')
            else
              ..._breakdown.asMap().entries.map((entry) => _buildBreakdownRow(entry.key, entry.value)).toList(),
            const SizedBox(height: 28),
            _buildSubmissionSection(),
            const SizedBox(height: 80),
          ],
        ),
      ),
    );
  }

  Widget _buildDateBanner() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10)],
      ),
      child: Row(
        children: [
          const Icon(Icons.calendar_month, color: Color(0xFF00BF6D), size: 18),
          const SizedBox(width: 12),
          Text(
            '${DateFormat('dd MMM, yyyy').format(_fromDate)} - ${DateFormat('dd MMM, yyyy').format(_toDate)}',
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF374151)),
          ),
        ],
      ),
    );
  }

  Widget _buildRemainingCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: [Colors.red.shade400, Colors.red.shade600]),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.red.withOpacity(0.3), blurRadius: 15, offset: const Offset(0, 8))],
      ),
      child: Column(
        children: [
          const Text('হাতে অবশিষ্ট নগদ', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600, letterSpacing: 0.5)),
          const SizedBox(height: 8),
          Text(
            '৳${_data?['total_remaining'] ?? '0'}',
            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white),
          ),
        ],
      ),
    );
  }

  Widget _buildPendingCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: [Colors.amber.shade600, Colors.orange.shade700]),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.orange.withOpacity(0.3), blurRadius: 15, offset: const Offset(0, 8))],
      ),
      child: Column(
        children: [
          const Text('জমা আবেদন', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600, letterSpacing: 0.5)),
          const SizedBox(height: 8),
          Text(
            '৳${_data?['total_pending'] ?? '0'}',
            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 20, color: const Color(0xFF1A1D1F)),
        const SizedBox(width: 8),
        Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1A1D1F))),
      ],
    );
  }

  Widget _buildBreakdownRow(int index, Map<String, dynamic> item) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade100),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['category_name'] ?? 'Fee',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1A1D1F)),
                  maxLines: 1, overflow: TextOverflow.ellipsis,
                ),
                Text(
                  _formatBengaliMonth(item['month']),
                  style: TextStyle(fontSize: 11, color: Colors.grey.shade500, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          SizedBox(
            width: 120,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                TextField(
                  controller: _controllers[index],
                  keyboardType: TextInputType.number,
                  style: const TextStyle(fontWeight: FontWeight.w900, color: Color(0xFF00BF6D), fontSize: 15),
                  onChanged: (_) => _calculateTotal(),
                  decoration: InputDecoration(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    filled: true,
                    fillColor: const Color(0xFFF0FDF4),
                    prefixText: '৳ ',
                    prefixStyle: const TextStyle(color: Color(0xFF00BF6D), fontWeight: FontWeight.bold),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'সর্বোচ্চ: ${_breakdown[index]['remaining_to_request']}',
                  style: TextStyle(fontSize: 9, color: Colors.red.shade400, fontWeight: FontWeight.bold),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSubmissionSection() {
    return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 20)],
        ),
        child: Column(
            children: [
                Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                        const Text('মোট জমা দিতে চান:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                        Text('৳${_totalToDeposit.toStringAsFixed(0)}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 24, color: Color(0xFF00BF6D))),
                    ],
                ),
                const SizedBox(height: 20),
                SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                        onPressed: (_isDepositing || _totalToDeposit <= 0) ? null : _showConfirmation,
                        style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            backgroundColor: const Color(0xFF00BF6D),
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            elevation: 0,
                        ),
                        child: _isDepositing
                            ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Text('প্রেরণ করুন', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, letterSpacing: 0.5)),
                    ),
                ),
            ],
        ),
    );
  }

  Widget _buildEmptyState(String message) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 40),
      child: Center(
        child: Column(
          children: [
            Icon(Icons.check_circle_outline, size: 50, color: Colors.green.withOpacity(0.3)),
            const SizedBox(height: 12),
            Text(message, style: TextStyle(color: Colors.grey.shade500, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String amount;
  final Color color;
  const _StatCard({required this.title, required this.amount, required this.color});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: color.withOpacity(0.05),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withOpacity(0.1)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text('৳$amount', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: color)),
          ],
        ),
      ),
    );
  }
}
