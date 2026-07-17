import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import '../../../../core/network/dio_client.dart';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:path_provider/path_provider.dart';
import 'package:open_filex/open_filex.dart';

class MyCollectionsPage extends StatefulWidget {
  const MyCollectionsPage({super.key});

  @override
  State<MyCollectionsPage> createState() => _MyCollectionsPageState();
}

class _MyCollectionsPageState extends State<MyCollectionsPage> {
  final Dio _dio = DioClient().dio;
  final ScrollController _scrollController = ScrollController();

  bool _isLoading = true;
  bool _isLoadMoreLoading = false;
  String _error = '';

  Map<String, dynamic>? _data;
  List<dynamic> _collections = [];
  int _currentPage = 1;
  int _lastPage = 1;

  DateTime _fromDate = DateTime.now().subtract(const Duration(days: 30));
  DateTime _toDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _fetchCollections();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      if (!_isLoadMoreLoading && _currentPage < _lastPage) {
        _fetchCollections(isLoadMore: true);
      }
    }
  }

  Future<void> _fetchCollections({bool isLoadMore = false}) async {
    if (isLoadMore) {
      setState(() => _isLoadMoreLoading = true);
    } else {
      setState(() {
        _isLoading = true;
        _error = '';
        _currentPage = 1;
        _collections = [];
      });
    }

    try {
      final res = await _dio.get(
        'billing/reports/teacher-collections',
        queryParameters: {
          'from_date': DateFormat('yyyy-MM-dd').format(_fromDate),
          'to_date': DateFormat('yyyy-MM-dd').format(_toDate),
          'page': _currentPage,
        },
      );

      if (mounted) {
        final newData = res.data;
        final List<dynamic> newCollections =
            newData['collections'] as List? ?? [];
        final meta = newData['meta'] ?? {};

        setState(() {
          _data = newData;
          _collections.addAll(newCollections);
          _currentPage = (meta['current_page'] as int? ?? _currentPage) + 1;
          _lastPage = meta['last_page'] as int? ?? 1;
          _isLoading = false;
          _isLoadMoreLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Failed to load collections: $e';
          _isLoading = false;
          _isLoadMoreLoading = false;
        });
      }
    }
  }

  Future<void> _pickDateRange() async {
    final picked = await showDateRangePicker(
      context: context,
      initialDateRange: DateTimeRange(start: _fromDate, end: _toDate),
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
    );
    if (picked != null) {
      setState(() {
        _fromDate = picked.start;
        _toDate = picked.end;
      });
      _fetchCollections();
    }
  }

  String _formatBengaliMonth(String? monthStr) {
    if (monthStr == null || monthStr.isEmpty) return 'N/A';
    try {
      final parts = monthStr.split('-');
      if (parts.length != 2) return monthStr;

      final year = parts[0];
      final month = parts[1];

      const months = {
        '01': 'জানুয়ারী',
        '02': 'ফেব্রুয়ারী',
        '03': 'মার্চ',
        '04': 'এপ্রিল',
        '05': 'মে',
        '06': 'জুন',
        '07': 'জুলাই',
        '08': 'আগস্ট',
        '09': 'সেপ্টেম্বর',
        '10': 'অক্টোবর',
        '11': 'নভেম্বর',
        '12': 'ডিসেম্বর',
      };

      String bengaliYear = year
          .split('')
          .map((e) {
            const numerals = {
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
            return numerals[e] ?? e;
          })
          .join('');

      return '${months[month] ?? month}, $bengaliYear';
    } catch (e) {
      return monthStr;
    }
  }

  Future<void> _downloadReceipt(dynamic paymentId, dynamic receiptNo) async {
    try {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('রিসিট নং $receiptNo ডাউনলোড হচ্ছে...'),
          backgroundColor: const Color(0xFF00BF6D),
          duration: const Duration(seconds: 2),
        ),
      );

      final directory = await getApplicationDocumentsDirectory();
      final filePath = '${directory.path}/Receipt-$receiptNo.pdf';

      final response = await _dio.download(
        'billing/fees/receipt/$paymentId/download',
        filePath,
        options: Options(
          responseType: ResponseType.bytes,
          followRedirects: false,
        ),
      );

      if (response.statusCode == 200) {
        await OpenFilex.open(filePath);
      } else {
        throw 'আপনার লগইন মেয়াদ শেষ হতে পারে অথবা রিসিট টি পাওয়া যায়নি।';
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('ডাউনলোড ব্যর্থ হয়েছে: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7F9),
      appBar: AppBar(
        title: const Text(
          'সংগ্রহের ইতিহাস',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1A1D1F),
        elevation: 1,
        shadowColor: Colors.black.withValues(alpha: 0.1),
        actions: [
          IconButton(
            icon: const Icon(
              Icons.date_range_outlined,
              color: Color(0xFF00BF6D),
            ),
            onPressed: _pickDateRange,
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF00BF6D)),
      );
    }
    if (_error.isNotEmpty) {
      return Center(
        child: Text(_error, style: const TextStyle(color: Colors.red)),
      );
    }
    if (_data == null) return const Center(child: Text('No data found'));

    final summary = _data!['summary'] as List? ?? [];

    return RefreshIndicator(
      onRefresh: () => _fetchCollections(),
      color: const Color(0xFF00BF6D),
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        itemCount:
            _collections.length + 3, // Header, Summary, Title, Collections...
        itemBuilder: (context, index) {
          if (index == 0) return _buildDateHeader();
          if (index == 1) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 20),
                _buildSectionTitle('Summary', Icons.summarize_outlined),
                const SizedBox(height: 12),
                if (summary.isEmpty)
                  _buildEmptyState('No summary available')
                else
                  _buildSummaryList(summary),
                const SizedBox(height: 24),
                _buildSectionTitle(
                  'Detailed Collections',
                  Icons.receipt_long_outlined,
                ),
                const SizedBox(height: 12),
              ],
            );
          }

          if (index < _collections.length + 2) {
            final c = _collections[index - 2];
            return _buildCollectionCard(c);
          }

          if (_isLoadMoreLoading) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 20),
              child: Center(
                child: CircularProgressIndicator(
                  color: Color(0xFF00BF6D),
                  strokeWidth: 2,
                ),
              ),
            );
          }

          return const SizedBox(height: 80);
        },
      ),
    );
  }

  Widget _buildDateHeader() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFF00BF6D).withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.calendar_today_rounded,
              color: Color(0xFF00BF6D),
              size: 18,
            ),
          ),
          const SizedBox(width: 14),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'তারিখ সীমা',
                style: TextStyle(
                  color: Colors.grey.shade500,
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                '${DateFormat('dd MMM, yyyy').format(_fromDate)} - ${DateFormat('dd MMM, yyyy').format(_toDate)}',
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 13,
                  color: Color(0xFF1A1D1F),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title, IconData icon) {
    return Row(
      children: [
        Container(
          width: 4,
          height: 18,
          decoration: BoxDecoration(
            color: const Color(0xFF00BF6D),
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 10),
        Text(
          title,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w900,
            color: Color(0xFF1A1D1F),
            letterSpacing: 0.5,
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyState(String message) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 20),
      child: Center(
        child: Column(
          children: [
            Icon(Icons.inbox_outlined, size: 40, color: Colors.grey.shade300),
            const SizedBox(height: 8),
            Text(
              message,
              style: TextStyle(color: Colors.grey.shade500, fontSize: 13),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryList(List summary) {
    return Column(
      children: summary.map((s) {
        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(12),
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
                      s['category']?.toString() ?? 'Fee',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                        color: Color(0xFF1A1D1F),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Students: ${s['student_count'] ?? 0}',
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey.shade500,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '৳${s['paid']}',
                    style: const TextStyle(
                      color: Color(0xFF00BF6D),
                      fontWeight: FontWeight.w900,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'Due: ৳${s['due']}',
                    style: const TextStyle(
                      color: Colors.red,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildCollectionCard(Map<String, dynamic> c) {
    final student = c['student'] ?? {};
    final List items = c['items'] as List? ?? [];
    final String photoUrl = student['photo_url']?.toString() ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: Column(
          children: [
            // Student Info Header
            InkWell(
              onTap: () =>
                  _downloadReceipt(c['id'], c['receipt_no'] ?? c['id']),
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Row(
                  children: [
                    Hero(
                      tag: 'student_${student['student_id']}_${c['id']}',
                      child: Container(
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: const Color(
                              0xFF00BF6D,
                            ).withValues(alpha: 0.2),
                            width: 2,
                          ),
                        ),
                        child: CircleAvatar(
                          radius: 26,
                          backgroundColor: const Color(0xFFF0FDF4),
                          backgroundImage: photoUrl.isNotEmpty
                              ? CachedNetworkImageProvider(photoUrl)
                              : null,
                          child: photoUrl.isEmpty
                              ? const Icon(
                                  Icons.person,
                                  color: Color(0xFF00BF6D),
                                )
                              : null,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            student['name'] ?? 'Unknown',
                            style: const TextStyle(
                              fontWeight: FontWeight.w900,
                              fontSize: 15,
                              color: Color(0xFF1A1D1F),
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'ID: ${student['student_id']} | রোল: ${student['roll']}',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey.shade600,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            'শ্রেণি: ${student['class_name']} | শাখা: ${student['section_name']}',
                            style: TextStyle(
                              fontSize: 11,
                              color: const Color(0xFF00BF6D),
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Icon(
                      Icons.download_for_offline_outlined,
                      color: Color(0xFF00BF6D),
                      size: 24,
                    ),
                  ],
                ),
              ),
            ),

            // Sub-items (Fees)
            Container(
              color: const Color(0xFFF9FAFB),
              child: Column(
                children: [
                  ...items.map((item) {
                    final double fine =
                        double.tryParse(item['fine']?.toString() ?? '0') ?? 0;
                    final double waiver =
                        double.tryParse(item['waiver']?.toString() ?? '0') ?? 0;

                    return Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 10,
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item['category']?.toString() ?? 'Fee',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 13,
                                    color: Color(0xFF374151),
                                  ),
                                ),
                                if (item['month'] != null)
                                  Text(
                                    _formatBengaliMonth(
                                      item['month']?.toString(),
                                    ),
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: Colors.grey.shade500,
                                    ),
                                  ),
                              ],
                            ),
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                '৳${item['amount']}',
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                  fontSize: 13,
                                  color: Color(0xFF1A1D1F),
                                ),
                              ),
                              if (fine > 0 || waiver > 0)
                                Row(
                                  children: [
                                    if (fine > 0)
                                      Text(
                                        'জরিমানা: ৳$fine ',
                                        style: const TextStyle(
                                          fontSize: 9,
                                          color: Colors.red,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    if (waiver > 0)
                                      Text(
                                        'মওকুফ: ৳$waiver',
                                        style: const TextStyle(
                                          fontSize: 9,
                                          color: Color(0xFF00BF6D),
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                  ],
                                ),
                            ],
                          ),
                        ],
                      ),
                    );
                  }),
                ],
              ),
            ),

            // Footer: Receipt No & Total
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                color: const Color(0xFF00BF6D).withValues(alpha: 0.05),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'রিসিট নম্বর',
                        style: TextStyle(
                          color: Colors.grey.shade500,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        c['receipt_no']?.toString() ?? 'N/A',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                          color: Color(0xFF065F46),
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        'মোট গৃহিত অর্থ',
                        style: TextStyle(
                          color: Colors.grey.shade500,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        '৳${c['amount_paid']}',
                        style: const TextStyle(
                          fontWeight: FontWeight.w900,
                          fontSize: 16,
                          color: Color(0xFF00BF6D),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
