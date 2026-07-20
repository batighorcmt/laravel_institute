import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/dio_client.dart';
import '../../state/auth_state.dart';
import 'staff_self_attendance_page.dart';

/// Minimal staff dashboard — for now, staff accounts exist purely so they
/// can log in and give their own daily attendance (see
/// StaffSelfAttendancePage). More staff-facing features can be added here
/// later without touching the login/routing plumbing.
class StaffDashboardPage extends ConsumerStatefulWidget {
  const StaffDashboardPage({super.key});

  @override
  ConsumerState<StaffDashboardPage> createState() =>
      _StaffDashboardPageState();
}

class _StaffDashboardPageState extends ConsumerState<StaffDashboardPage> {
  final Dio _dio = DioClient().dio;
  Map<String, dynamic>? _todayRecord;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _fetchTodayRecord();
  }

  Future<void> _fetchTodayRecord() async {
    setState(() => _loading = true);
    try {
      final today = DateTime.now();
      final ymd =
          '${today.year}-${today.month.toString().padLeft(2, '0')}-${today.day.toString().padLeft(2, '0')}';
      final resp = await _dio.get('staff/attendance');
      final data = resp.data;
      List list = [];
      if (data is List) list = data;
      if (data is Map && data['data'] is List) list = data['data'];
      Map<String, dynamic>? todayRec;
      for (final raw in list) {
        if (raw is Map<String, dynamic>) {
          final dateField = (raw['date'] ?? '').toString();
          if (dateField.startsWith(ymd)) {
            todayRec = raw;
            break;
          }
        }
      }
      if (mounted) setState(() => _todayRecord = todayRec);
    } catch (_) {
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(authProvider).asData?.value;
    final name = profile?.name ?? '';
    final photo = profile?.photoUrl;
    final designation = profile?.staffDesignation ?? '';
    final phone = profile?.staffPhone ?? profile?.mobile ?? '';
    String? schoolName;
    if (profile != null) {
      for (final r in profile.roles) {
        if (r.schoolName != null && r.schoolName!.isNotEmpty) {
          schoolName = r.schoolName;
          break;
        }
      }
    }

    final selfStatus = _todayRecord?['status']?.toString();
    String statusLabel = 'হাজিরা দেওয়া হয়নি';
    Color statusColor = Colors.grey;
    if (selfStatus == 'present') {
      statusLabel = 'উপস্থিত';
      statusColor = Colors.green;
    } else if (selfStatus == 'late') {
      statusLabel = 'বিলম্ব';
      statusColor = Colors.orange;
    } else if (selfStatus == 'absent') {
      statusLabel = 'অনুপস্থিত';
      statusColor = Colors.red;
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('স্টাফ প্যানেল'),
        actions: [
          IconButton(
            tooltip: 'Reload',
            icon: const Icon(Icons.refresh),
            onPressed: _fetchTodayRecord,
          ),
          IconButton(
            tooltip: 'Logout',
            icon: const Icon(Icons.logout, color: Colors.red),
            onPressed: () async {
              await ref.read(authProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchTodayRecord,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              elevation: 2,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CircleAvatar(
                      radius: 32,
                      backgroundColor: const Color(0xFFE0F2FE),
                      backgroundImage: (photo != null && photo.isNotEmpty)
                          ? NetworkImage(photo)
                          : null,
                      child: (photo == null || photo.isEmpty)
                          ? Text(
                              name.isNotEmpty ? name[0].toUpperCase() : 'S',
                              style: const TextStyle(
                                fontSize: 28,
                                color: Color(0xFF1A1D1F),
                              ),
                            )
                          : null,
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            name,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          if (designation.isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Text(
                              designation,
                              style: TextStyle(
                                color: Colors.grey[700],
                                fontSize: 14,
                              ),
                            ),
                          ],
                          if (phone.isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Text(
                              phone,
                              style: const TextStyle(
                                color: Color(0xFF4B5563),
                                fontSize: 13,
                              ),
                            ),
                          ],
                          const SizedBox(height: 6),
                          if (schoolName != null)
                            Text(
                              schoolName,
                              style: const TextStyle(
                                color: Color(0xFF1F2937),
                                fontWeight: FontWeight.w600,
                                fontSize: 14,
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            Card(
              elevation: 1,
              child: InkWell(
                borderRadius: BorderRadius.circular(8),
                onTap: () async {
                  await Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const StaffSelfAttendancePage(),
                    ),
                  );
                  _fetchTodayRecord();
                },
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.how_to_reg_outlined,
                        size: 32,
                        color: Colors.blue,
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'হাজিরা (Self Attendance)',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 8),
                            if (_loading)
                              const SizedBox(
                                height: 16,
                                width: 16,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            else
                              Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 10,
                                      vertical: 4,
                                    ),
                                    decoration: BoxDecoration(
                                      color: statusColor.withValues(
                                        alpha: 0.12,
                                      ),
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: Text(
                                      statusLabel,
                                      style: TextStyle(
                                        color: statusColor,
                                        fontWeight: FontWeight.bold,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ),
                                  if (_todayRecord?['check_in_time'] !=
                                      null) ...[
                                    const SizedBox(width: 8),
                                    Text(
                                      'ইন: ${_todayRecord?['check_in_time']}',
                                      style: const TextStyle(fontSize: 12),
                                    ),
                                  ],
                                  if (_todayRecord?['check_out_time'] !=
                                      null) ...[
                                    const SizedBox(width: 8),
                                    Text(
                                      'আউট: ${_todayRecord?['check_out_time']}',
                                      style: const TextStyle(fontSize: 12),
                                    ),
                                  ],
                                ],
                              ),
                          ],
                        ),
                      ),
                      const Icon(Icons.chevron_right),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
