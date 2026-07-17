import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../state/parent_state.dart';
import '../../../../core/config/env.dart';

class FeesPage extends ConsumerStatefulWidget {
  const FeesPage({super.key});

  @override
  ConsumerState<FeesPage> createState() => _FeesPageState();
}

class _FeesPageState extends ConsumerState<FeesPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isDownloading = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _initiatePayment(
    int academicYearId,
    int studentId,
    List dueFees,
  ) async {
    try {
      final selectedPayload = dueFees
          .map(
            (f) => {
              'student_fee_id': f['id'],
              'amount': f['amount'] - f['paid_amount'],
              'fine_amount': f['fine'],
            },
          )
          .toList();

      final result = await ref
          .read(parentRepositoryProvider)
          .initiateSslPayment(
            studentId: studentId,
            fees: selectedPayload,
            academicYearId: academicYearId,
          );

      if (result['gateway_url'] != null) {
        if (!mounted) return;
        final success = await Navigator.push<bool>(
          context,
          MaterialPageRoute(
            builder: (context) => PaymentWebView(url: result['gateway_url']),
          ),
        );

        if (success == true) {
          ref.invalidate(parentFeesProvider);
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('পেমেন্ট সফলভাবে গ্রহণ করা হয়েছে।'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        throw result['message'] ?? 'পেমেন্ট লিংক পাওয়া যায়নি';
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('পেমেন্ট শুরু করতে সমস্যা হয়েছে: $e')),
      );
    }
  }

  Future<void> _downloadReceipt(
    String? relativeUrl,
    String paymentNumber,
  ) async {
    if (relativeUrl == null || _isDownloading) return;

    setState(() => _isDownloading = true);

    try {
      // Build full URL from configured base (strips trailing /api/v1/ then adds full path)
      final baseUrl = Env.apiBaseUrl.endsWith('/')
          ? Env.apiBaseUrl
          : '${Env.apiBaseUrl}/';
      // Env.apiBaseUrl is like https://xxx.com/api/v1/
      // relativeUrl is like billing/fees/receipt/59/download
      final fullUrl = '$baseUrl$relativeUrl';

      final sp = await SharedPreferences.getInstance();
      final token = sp.getString('auth_token');
      if (token == null) throw 'লগইন তথ্য পাওয়া যায়নি';

      final dir = await getApplicationDocumentsDirectory();
      final savePath = '${dir.path}/Receipt-$paymentNumber.pdf';

      final dio = Dio();
      await dio.download(
        fullUrl,
        savePath,
        options: Options(
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/pdf',
          },
        ),
      );

      final result = await OpenFilex.open(savePath);
      if (result.type != ResultType.done) {
        throw result.message;
      }
    } catch (e) {
      String msg = e.toString();
      if (e is DioException) {
        if (e.response?.data is Map) {
          msg = e.response?.data['error'] ?? e.response?.data['message'] ?? msg;
        } else {
          msg = 'সার্ভারের সাথে সংযোগ হচ্ছে না। নেটওয়ার্ক চেক করুন।';
        }
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('রিসিট ডাউনলোড ব্যর্থ: $msg'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isDownloading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final feesAsync = ref.watch(parentFeesProvider);
    final cs = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('ফিস হিসাব'),
        bottom: TabBar(
          controller: _tabController,
          labelColor: cs.primary,
          unselectedLabelColor: Colors.grey,
          indicatorColor: cs.primary,
          tabs: const [
            Tab(text: 'বকেয়া ফিস'),
            Tab(text: 'পরিশোধের ইতিহাস'),
          ],
        ),
      ),
      body: Stack(
        children: [
          RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(parentFeesProvider);
            },
            child: feesAsync.when(
              data: (data) {
                final dueFees = data['due_fees'] as List;
                final paidFees = data['paid_fees'] as List;
                final student = data['student'];
                final academicYearId = data['academic_year_id'];

                return TabBarView(
                  controller: _tabController,
                  children: [
                    _buildDueTab(dueFees, student['id'], academicYearId),
                    _buildPaidTab(paidFees),
                  ],
                );
              },
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (err, _) => Center(child: Text('লোডিং ত্রুটি: $err')),
            ),
          ),
          if (_isDownloading)
            Container(
              color: Colors.black26,
              child: const Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CircularProgressIndicator(color: Colors.white),
                    SizedBox(height: 10),
                    Text(
                      'রিসিট ডাউনলোড হচ্ছে...',
                      style: TextStyle(color: Colors.white),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildDueTab(List dueFees, int studentId, int? academicYearId) {
    if (dueFees.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.check_circle_outline, size: 64, color: Colors.green),
            SizedBox(height: 16),
            Text('আপনার কোনো বকেয়া ফিস নেই', style: TextStyle(fontSize: 16)),
          ],
        ),
      );
    }

    double totalSelected = 0;
    for (var f in dueFees) {
      totalSelected += (f['total_due'] as num).toDouble();
    }

    return Column(
      children: [
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: dueFees.length,
            itemBuilder: (context, index) {
              final fee = dueFees[index];

              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              fee['category_name'] ?? 'N/A',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 16,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'শেষ তারিখ: ${fee['due_date'] ?? 'N/A'}',
                              style: const TextStyle(
                                color: Colors.grey,
                                fontSize: 13,
                              ),
                            ),
                            if ((fee['fine'] as num) > 0)
                              Text(
                                'জরিমানা: ৳${fee['fine']}',
                                style: const TextStyle(
                                  color: Colors.red,
                                  fontSize: 12,
                                ),
                              ),
                          ],
                        ),
                      ),
                      Text(
                        '৳${fee['total_due']}',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.indigo,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 10,
                offset: const Offset(0, -5),
              ),
            ],
          ),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text(
                      'মোট বকেয়া পেমেন্ট',
                      style: TextStyle(color: Colors.grey),
                    ),
                    Text(
                      '৳${totalSelected.toStringAsFixed(2)}',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
              FilledButton(
                style: FilledButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 24,
                    vertical: 12,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                onPressed: totalSelected > 0 && academicYearId != null
                    ? () => _initiatePayment(academicYearId, studentId, dueFees)
                    : null,
                child: const Text('পেমেন্ট করুন'),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildPaidTab(List paidFees) {
    if (paidFees.isEmpty) {
      return const Center(child: Text('আপনার কোনো পেমেন্ট ইতিহাস নেই'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: paidFees.length,
      itemBuilder: (context, index) {
        final payment = paidFees[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            onTap: () => _downloadReceipt(
              payment['receipt_url'] as String?,
              payment['payment_number'] as String,
            ),
            leading: const CircleAvatar(
              backgroundColor: Colors.green,
              child: Icon(Icons.receipt_long, color: Colors.white),
            ),
            title: Text('পেমেন্ট আইডি: ${payment['payment_number']}'),
            subtitle: Text('তারিখ: ${payment['received_at'] ?? 'N/A'}'),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  '৳${payment['amount_paid']}',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                const Text(
                  'রিসিট ডাউনলোড করুন',
                  style: TextStyle(fontSize: 9, color: Colors.blue),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class PaymentWebView extends StatefulWidget {
  final String url;
  const PaymentWebView({super.key, required this.url});

  @override
  State<PaymentWebView> createState() => _PaymentWebViewState();
}

class _PaymentWebViewState extends State<PaymentWebView> {
  double _progress = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('SSLCommerz পেমেন্ট'),
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => Navigator.pop(context, false),
        ),
      ),
      body: Column(
        children: [
          if (_progress < 1.0)
            LinearProgressIndicator(value: _progress, minHeight: 4),
          Expanded(
            child: InAppWebView(
              initialUrlRequest: URLRequest(url: WebUri(widget.url)),
              initialSettings: InAppWebViewSettings(
                javaScriptEnabled: true,
                domStorageEnabled: true,
                thirdPartyCookiesEnabled: true,
                mixedContentMode:
                    MixedContentMode.MIXED_CONTENT_ALWAYS_ALLOW, // For Android
                allowsBackForwardNavigationGestures: true, // For iOS
                useShouldOverrideUrlLoading:
                    true, // Vital for custom URL schemes
              ),
              shouldOverrideUrlLoading: (controller, navigationAction) async {
                final uri = navigationAction.request.url;
                if (uri == null) return NavigationActionPolicy.ALLOW;

                final urlString = uri.toString();
                final scheme = uri.scheme.toLowerCase();

                // If not standard web link, let OS handle it (e.g. bkash://, nagad://, intent://)
                if (![
                  'http',
                  'https',
                  'about',
                  'data',
                  'javascript',
                  'file',
                ].contains(scheme)) {
                  try {
                    await launchUrl(uri, mode: LaunchMode.externalApplication);
                  } catch (e) {
                    debugPrint('Could not launch external app for $urlString');
                  }
                  return NavigationActionPolicy
                      .CANCEL; // Block webview rendering since external app opened
                }

                // Also intercept explicit intent URLs wrapped inside http if needed, though rare
                if (urlString.startsWith('intent://')) {
                  try {
                    await launchUrl(
                      Uri.parse(urlString),
                      mode: LaunchMode.externalApplication,
                    );
                  } catch (e) {
                    debugPrint('Could not launch intent app for $urlString');
                  }
                  return NavigationActionPolicy.CANCEL;
                }

                return NavigationActionPolicy.ALLOW;
              },
              onProgressChanged: (controller, progress) {
                setState(() => _progress = progress / 100);
              },
              onLoadStop: (controller, url) {
                final urlStr = url.toString();
                if (urlStr.contains('/payment/ssl/success')) {
                  Navigator.pop(context, true);
                } else if (urlStr.contains('/payment/ssl/fail') ||
                    urlStr.contains('/payment/ssl/cancel')) {
                  Navigator.pop(context, false);
                }
              },
            ),
          ),
        ],
      ),
    );
  }
}
