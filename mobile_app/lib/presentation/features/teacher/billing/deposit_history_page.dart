import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import 'package:intl/intl.dart';

class DepositHistoryPage extends StatefulWidget {
  const DepositHistoryPage({super.key});

  @override
  State<DepositHistoryPage> createState() => _DepositHistoryPageState();
}

class _DepositHistoryPageState extends State<DepositHistoryPage> {
  final Dio _dio = DioClient().dio;
  final ScrollController _scrollController = ScrollController();
  
  bool _isLoading = true;
  bool _isLoadingMore = false;
  String _error = '';
  
  List<dynamic> _deposits = [];
  int _currentPage = 1;
  int _lastPage = 1;

  // Filters
  DateTime _fromDate = DateTime(DateTime.now().year, DateTime.now().month, 1);
  DateTime _toDate = DateTime.now();
  String? _status;
  int? _selectedCategoryId;
  String? _selectedMonth;

  List<dynamic> _categories = [];

  @override
  void initState() {
    super.initState();
    _fetchCategories();
    _fetchHistory();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      if (!_isLoadingMore && _currentPage < _lastPage) {
        _fetchHistory(loadMore: true);
      }
    }
  }

  Future<void> _fetchCategories() async {
    try {
      final res = await _dio.get('billing/fees/categories');
      if (mounted) setState(() => _categories = res.data ?? []);
    } catch (_) {}
  }

  Future<void> _fetchHistory({bool loadMore = false, bool reset = false}) async {
    if (reset) {
      _currentPage = 1;
      _deposits.clear();
    }

    setState(() {
      if (loadMore) _isLoadingMore = true;
      else _isLoading = true;
      _error = '';
    });

    try {
      final res = await _dio.get('billing/reports/teacher-deposit-history', queryParameters: {
        'from_date': DateFormat('yyyy-MM-dd').format(_fromDate),
        'to_date': DateFormat('yyyy-MM-dd').format(_toDate),
        'fee_category_id': _selectedCategoryId,
        'month': _selectedMonth,
        'status': _status,
        'page': loadMore ? _currentPage + 1 : 1,
      });

      if (mounted) {
        final data = res.data;
        setState(() {
          final List newItems = data['data'] ?? [];
          if (loadMore) {
            _deposits.addAll(newItems);
            _currentPage++;
          } else {
            _deposits = newItems;
            _currentPage = 1;
          }
          _lastPage = data['last_page'] ?? 1;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _error = 'ইতিহাস লোড করতে ব্যর্থ: $e');
    } finally {
      if (mounted) setState(() {
        _isLoading = false;
        _isLoadingMore = false;
      });
    }
  }

  String _formatBengaliMonth(String? monthStr) {
    if (monthStr == null || monthStr.isEmpty) return 'অন্যান্য';
    try {
      final parts = monthStr.split('-');
      if (parts.length != 2) return monthStr;
      final year = parts[0];
      final month = parts[1];
      const months = {'01':'জানুয়ারী','02':'ফেব্রুয়ারী','03':'মার্চ','04':'এপ্রিল','05':'মে','06':'জুন','07':'জুলাই','08':'আগস্ট','09':'সেপ্টেম্বর','10':'অক্টোবর','11':'নভেম্বর','12':'ডিসেম্বর'};
      return '${months[month] ?? month}, $year';
    } catch (_) { return monthStr; }
  }

  String _getBengaliStatus(String status) {
    switch (status.toLowerCase()) {
      case 'pending': return 'অপেক্ষমাণ';
      case 'received': return 'গৃহীত';
      case 'rejected': return 'বাতিল';
      default: return status;
    }
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'received': return const Color(0xFF00BF6D);
      case 'pending': return Colors.orange;
      case 'rejected': return Colors.red;
      default: return Colors.blue;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7F9),
      appBar: AppBar(
        title: const Text('জমা ইতিহাস', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1A1D1F),
        elevation: 0.5,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: () => _fetchHistory(reset: true)),
        ],
      ),
      body: Column(
        children: [
          _buildFilterBar(),
          Expanded(child: _buildList()),
        ],
      ),
    );
  }

  Widget _buildFilterBar() {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Column(
        children: [
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                _FilterChip(
                  label: '${DateFormat('dd MMM').format(_fromDate)} - ${DateFormat('dd MMM').format(_toDate)}',
                  icon: Icons.date_range_outlined,
                  onTap: _pickDateRange,
                ),
                _FilterChip(
                  label: _status == null ? 'অবস্থা' : _getBengaliStatus(_status!),
                  icon: Icons.filter_list,
                  onTap: _showStatusPicker,
                  isSelected: _status != null,
                ),
                _FilterChip(
                  label: _selectedCategoryId == null ? 'ক্যাটাগরি' : _categories.firstWhere((c) => c['id'] == _selectedCategoryId, orElse: () => {'name': '...'})['name'],
                  icon: Icons.category_outlined,
                  onTap: _showCategoryPicker,
                  isSelected: _selectedCategoryId != null,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildList() {
    if (_isLoading && _deposits.isEmpty) return const Center(child: CircularProgressIndicator(color: Color(0xFF00BF6D)));
    if (_error.isNotEmpty && _deposits.isEmpty) return Center(child: Text(_error, style: const TextStyle(color: Colors.red)));

    return RefreshIndicator(
      onRefresh: () => _fetchHistory(reset: true),
      color: const Color(0xFF00BF6D),
      child: ListView.builder(
        controller: _scrollController,
        padding: const EdgeInsets.all(16),
        itemCount: _deposits.length + (_isLoadingMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index == _deposits.length) {
            return const Padding(padding: EdgeInsets.all(16), child: Center(child: CircularProgressIndicator(color: Color(0xFF00BF6D))));
          }
          return _buildDepositCard(_deposits[index]);
        },
      ),
    );
  }

  Widget _buildDepositCard(dynamic dep) {
    final status = dep['status']?.toString() ?? 'pending';
    final color = _getStatusColor(status);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
                child: Icon(Icons.account_balance_wallet_outlined, color: color, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(dep['category_name'] ?? 'ফি', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 2),
                    Text(_formatBengaliMonth(dep['month']), style: TextStyle(fontSize: 12, color: Colors.grey.shade500, fontWeight: FontWeight.w500)),
                  ],
                ),
              ),
              Text('৳${dep['amount']}', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: color)),
            ],
          ),
          const Divider(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildIconLabel(Icons.access_time, DateFormat('dd MMM, yyyy').format(DateTime.parse(dep['deposit_date']))),
                  const SizedBox(height: 4),
                  _buildIconLabel(Icons.person_outline, dep['cashier_name'] ?? 'ক্যাশিয়ার অপেক্ষা'),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(8)),
                child: Text(_getBengaliStatus(status), style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildIconLabel(IconData icon, String label) {
    return Row(
      children: [
        Icon(icon, size: 12, color: Colors.grey),
        const SizedBox(width: 4),
        Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey)),
      ],
    );
  }

  Future<void> _pickDateRange() async {
    final picked = await showDateRangePicker(context: context, initialDateRange: DateTimeRange(start: _fromDate, end: _toDate), firstDate: DateTime(2020), lastDate: DateTime.now());
    if (picked != null) {
      setState(() { _fromDate = picked.start; _toDate = picked.end; });
      _fetchHistory(reset: true);
    }
  }

  void _showStatusPicker() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (context) => ListView(
        shrinkWrap: true,
        padding: const EdgeInsets.all(16),
        children: [
          const Text('অবস্থা ফিল্টার করুন', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
          const SizedBox(height: 16),
          _pickerOption('সব', null, _status == null),
          _pickerOption('অপেক্ষমাণ', 'pending', _status == 'pending'),
          _pickerOption('গৃহীত', 'received', _status == 'received'),
          _pickerOption('বাতিল', 'rejected', _status == 'rejected'),
        ],
      ),
    );
  }

  void _showCategoryPicker() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (context) => ListView(
        shrinkWrap: true,
        padding: const EdgeInsets.all(16),
        children: [
          const Text('ক্যাটাগরি ফিল্টার করুন', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
          const SizedBox(height: 16),
          ListTile(title: const Text('সব'), selected: _selectedCategoryId == null, onTap: () { setState(() => _selectedCategoryId = null); Navigator.pop(context); _fetchHistory(reset: true); }),
          ..._categories.map((c) => ListTile(
            title: Text(c['name']),
            selected: _selectedCategoryId == c['id'],
            onTap: () { setState(() => _selectedCategoryId = c['id']); Navigator.pop(context); _fetchHistory(reset: true); },
          )),
        ],
      ),
    );
  }

  Widget _pickerOption(String label, String? value, bool isSelected) {
    return ListTile(
      title: Text(label),
      trailing: isSelected ? const Icon(Icons.check, color: Color(0xFF00BF6D)) : null,
      onTap: () { setState(() => _status = value); Navigator.pop(context); _fetchHistory(reset: true); },
    );
  }
}

class _FilterChip extends StatelessWidget {
  final String label;
  final IconData icon;
  final VoidCallback onTap;
  final bool isSelected;
  const _FilterChip({required this.label, required this.icon, required this.onTap, this.isSelected = false});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(30),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: isSelected ? const Color(0xFF00BF6D).withOpacity(0.1) : const Color(0xFFF0F2F5),
            borderRadius: BorderRadius.circular(30),
            border: Border.all(color: isSelected ? const Color(0xFF00BF6D) : Colors.transparent),
          ),
          child: Row(
            children: [
              Icon(icon, size: 16, color: isSelected ? const Color(0xFF00BF6D) : const Color(0xFF64748B)),
              const SizedBox(width: 6),
              Text(label, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: isSelected ? const Color(0xFF00BF6D) : const Color(0xFF64748B))),
            ],
          ),
        ),
      ),
    );
  }
}
