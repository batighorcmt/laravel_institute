import 'dart:convert';
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:geolocator/geolocator.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/network/dio_client.dart';
import '../../state/auth_state.dart';
import '../../../data/auth/auth_repository.dart';

class SelfAttendancePage extends ConsumerStatefulWidget {
  const SelfAttendancePage({super.key});

  @override
  ConsumerState<SelfAttendancePage> createState() => _SelfAttendancePageState();
}

class _SelfAttendancePageState extends ConsumerState<SelfAttendancePage> {
  final Dio _dio = DioClient().dio;
  final ImagePicker _picker = ImagePicker();
  bool _busy = false;
  String? _error;
  Map<String, dynamic>? _todayRecord;
  Map<String, dynamic>? _settings;
  bool _loadingSettings = true;
  int? _schoolId;
  XFile? _photo;
  Position? _position;

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    final dateStr =
        '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}  ${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}';
    return Scaffold(
      appBar: AppBar(title: const Text('Self Attendance')),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFFF7F4FF), Color(0xFFEDE7FF)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Now: $dateStr', style: const TextStyle(color: Colors.grey)),
              const SizedBox(height: 12),
              _StatusHeader(record: _todayRecord),
              const SizedBox(height: 8),
              _SettingsBanner(settings: _settings, loading: _loadingSettings),
              const SizedBox(height: 12),
              const SizedBox(height: 12),
              // Action buttons
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor:
                            (!_busy &&
                                !_isTodayCheckedIn &&
                                !_isTodayCompleted &&
                                _schoolId != null)
                            ? Theme.of(context).colorScheme.primary
                            : Colors.grey.shade400,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                      onPressed:
                          (!_busy &&
                              !_isTodayCheckedIn &&
                              !_isTodayCompleted &&
                              _schoolId != null)
                          ? _startCheckInFlow
                          : null,
                      child: _busy
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text(
                              'Check In',
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                        side: BorderSide(
                          color:
                              (!_busy &&
                                  _schoolId != null &&
                                  _isTodayCheckedIn &&
                                  !_isTodayCompleted)
                              ? Theme.of(context).colorScheme.primary
                              : Colors.grey.shade400,
                        ),
                      ),
                      onPressed:
                          (!_busy &&
                              _isTodayCheckedIn &&
                              !_isTodayCompleted &&
                              _schoolId != null)
                          ? _startCheckOutFlow
                          : null,
                      child: const Text(
                        'Check Out',
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ],
              ),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Text(
                    _error!,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  // Capture photo via camera
  Future<void> _capturePhoto() async {
    try {
      final camStatus = await Permission.camera.request();
      if (!camStatus.isGranted) {
        setState(() => _error = 'Camera permission denied');
        return;
      }
      final image = await _picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 75,
      );
      if (image == null) {
        setState(() => _error = 'No photo captured');
        return;
      }
      if (!File(image.path).existsSync()) {
        setState(() => _error = 'Captured file missing at ${image.path}');
        return;
      }
      setState(() => _photo = image);
    } catch (e) {
      setState(() => _error = 'Camera error: $e');
    }
  }

  // Get current location
  Future<void> _getLocation() async {
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        setState(() => _error = 'Location service disabled');
        return;
      }
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        setState(() => _error = 'Location permission denied');
        return;
      }
      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );
      setState(() => _position = pos);
    } catch (e) {
      setState(() => _error = 'Location error: $e');
    }
  }

  Future<void> _startCheckInFlow() async {
    try {
      setState(() {
        _busy = true;
        _error = null;
      });
      await _capturePhoto();
      if (_photo == null) return;
      await _getLocation();
      if (_position == null) {
        setState(() => _error = 'Location not found');
        return;
      }
      await _submitCheckIn();
      await _fetchTodayRecord();
      await _showSuccessModal('চেক ইন সফল (অভিনন্দন)');
    } finally {
      if (mounted) {
        setState(() {
          _busy = false;
        });
      }
    }
  }

  Future<void> _startCheckOutFlow() async {
    try {
      setState(() {
        _busy = true;
        _error = null;
      });
      await _capturePhoto();
      if (_photo == null) return;
      await _getLocation();
      if (_position == null) {
        setState(() => _error = 'Location not found');
        return;
      }
      await _submitCheckout();
      await _fetchTodayRecord();
      await _showSuccessModal('চেক আউট সফল (অভিনন্দন)');
    } finally {
      if (mounted) {
        setState(() {
          _busy = false;
        });
      }
    }
  }

  Future<void> _showSuccessModal(String message) async {
    if (!mounted) return;
    await showDialog<void>(
      context: context,
      barrierDismissible: true,
      builder: (context) {
        return AlertDialog(
          title: const Text('Success'),
          content: Text(message),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('OK'),
            ),
          ],
        );
      },
    );
  }

  Future<void> _submitCheckIn() async {
    final schoolId = _schoolId;
    if (schoolId == null) {
      setState(
        () => _error = 'School ID পাওয়া যায়নি! দয়া করে পুনরায় লগইন করুন।',
      );
      return;
    }
    if (_photo == null || _position == null) return;
    final fileName = 'self_${DateTime.now().millisecondsSinceEpoch}.jpg';
    final form = FormData.fromMap({
      'lat': _position!.latitude,
      'lng': _position!.longitude,
      'photo': await MultipartFile.fromFile(_photo!.path, filename: fileName),
      'school_id': schoolId,
    });
    try {
      final resp = await _dio.post('teacher/attendance', data: form);
      // API returns resource or {message, data: resource} on duplicate
      dynamic payload = resp.data;
      Map<String, dynamic>? record;
      if (payload is Map) {
        if (payload['data'] is Map) {
          record = Map<String, dynamic>.from(payload['data']);
        } else if (payload['check_in_time'] != null) {
          record = Map<String, dynamic>.from(payload);
        }
      }
      if (record != null) {
        setState(() {
          _todayRecord = record;
        });
      }
      final msg = (payload is Map && payload['message'] is String)
          ? payload['message']
          : 'Check-in saved';
      _successSnack(msg);
    } on DioException catch (e) {
      _handleDioError(e);
      if (e.type == DioExceptionType.connectionError || e.response == null) {
        await _queueOffline('checkin');
      }
    }
  }

  Future<void> _submitCheckout() async {
    final schoolId = _schoolId;
    if (schoolId == null) {
      setState(
        () => _error = 'School ID পাওয়া যায়নি! দয়া করে পুনরায় লগইন করুন।',
      );
      return;
    }
    if (_photo == null || _position == null) return;
    final fileName = 'self_${DateTime.now().millisecondsSinceEpoch}.jpg';
    final form = FormData.fromMap({
      'lat': _position!.latitude,
      'lng': _position!.longitude,
      'photo': await MultipartFile.fromFile(_photo!.path, filename: fileName),
      'school_id': schoolId,
    });
    try {
      final resp = await _dio.post('teacher/attendance/checkout', data: form);
      dynamic payload = resp.data;
      Map<String, dynamic>? record;
      if (payload is Map) {
        if (payload['data'] is Map) {
          record = Map<String, dynamic>.from(payload['data']);
        } else if (payload['check_out_time'] != null) {
          record = Map<String, dynamic>.from(payload);
        }
      }
      if (record != null) {
        setState(() {
          _todayRecord = record;
        });
      }
      final msg = (payload is Map && payload['message'] is String)
          ? payload['message']
          : 'Check-out saved';
      _successSnack(msg);
    } on DioException catch (e) {
      _handleDioError(e);
      if (e.type == DioExceptionType.connectionError || e.response == null) {
        await _queueOffline('checkout');
      }
    }
  }

  Future<void> _fetchTodayRecord() async {
    try {
      final today = DateTime.now();
      final ymd =
          '${today.year}-${today.month.toString().padLeft(2, '0')}-${today.day.toString().padLeft(2, '0')}';
      final resp = await _dio.get('teacher/attendance');
      final data = resp.data;
      List list = [];
      if (data is List) list = data;
      if (data is Map && data['data'] is List) list = data['data'];
      Map<String, dynamic>? todayRec;
      for (final raw in list) {
        if (raw is Map<String, dynamic>) {
          final dateField =
              (raw['date'] ?? raw['attendance_date'] ?? raw['day'] ?? '')
                  .toString();
          if (dateField.startsWith(ymd)) {
            todayRec = raw;
            break;
          }
        }
      }
      setState(() {
        _todayRecord = todayRec;
      });
    } catch (_) {}
  }

  Future<void> _fetchSettings() async {
    try {
      final resp = await _dio.get('teacher/attendance/settings');
      final data = resp.data;
      Map<String, dynamic>? s;
      if (data is Map && data['data'] is Map) {
        s = Map<String, dynamic>.from(data['data']);
      }
      setState(() {
        _settings = s;
        _loadingSettings = false;
        final sid = s != null ? (s['school_id'] as num?)?.toInt() : null;
        if (sid != null) _schoolId = sid;
      });
    } catch (_) {
      setState(() {
        _loadingSettings = false;
      });
    }
  }

  static const _queueKey = 'attendance_offline_queue_v1';
  Future<void> _flushOfflineQueue() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_queueKey);
    if (raw == null) return;
    List list;
    try {
      list = List.from(jsonDecode(raw));
    } catch (_) {
      return;
    }
    if (list.isEmpty) return;
    final remaining = [];
    for (final item in list) {
      if (item is Map) {
        try {
          final type = item['type'];
          if (type == null) {
            continue;
          }
          // Resolve school_id similarly for offline retries
          final profile = ref.read(authProvider).value;
          int? schoolId;
          if (profile != null) {
            for (final r in profile.roles) {
              if (r.role == 'teacher' && r.schoolId != null) {
                schoolId = r.schoolId;
                break;
              }
            }
          }
          final lat = item['lat'];
          final lng = item['lng'];
          final path = item['photoPath'];
          if (lat == null || lng == null || path == null) {
            remaining.add(item);
            continue;
          }
          final form = FormData.fromMap({
            'lat': lat,
            'lng': lng,
            'photo': await MultipartFile.fromFile(
              path,
              filename: 'retry_${DateTime.now().millisecondsSinceEpoch}.jpg',
            ),
            if (schoolId != null) 'school_id': schoolId,
          });
          await _dio.post(
            type == 'checkin'
                ? 'teacher/attendance'
                : 'teacher/attendance/checkout',
            data: form,
          );
        } catch (_) {
          remaining.add(item);
        }
      }
    }
    if (remaining.isEmpty) {
      await prefs.remove(_queueKey);
      if (mounted) _successSnack('Offline queue flushed');
    } else {
      await prefs.setString(_queueKey, jsonEncode(remaining));
    }
  }

  Future<void> _queueOffline(String type) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_queueKey);
    List list;
    try {
      list = raw != null ? List.from(jsonDecode(raw)) : [];
    } catch (_) {
      list = [];
    }
    // Also store school_id for context (best effort)
    final profile = ref.read(authProvider).value;
    int? schoolId;
    if (profile != null) {
      for (final r in profile.roles) {
        if (r.role == 'teacher' && r.schoolId != null) {
          schoolId = r.schoolId;
          break;
        }
      }
    }
    list.add({
      'type': type,
      'lat': _position?.latitude,
      'lng': _position?.longitude,
      'photoPath': _photo?.path,
      'captured': DateTime.now().toIso8601String(),
      'school_id': schoolId,
    });
    await prefs.setString(_queueKey, jsonEncode(list));
    if (mounted) _successSnack('Saved offline, will retry later');
  }

  bool get _isTodayCheckedIn {
    final r = _todayRecord;
    if (r == null) return false;
    return r['check_in_time'] != null &&
        (r['check_in_time'].toString().isNotEmpty);
  }

  bool get _isTodayCheckedOut {
    final r = _todayRecord;
    if (r == null) return false;
    return r['check_out_time'] != null &&
        (r['check_out_time'].toString().isNotEmpty);
  }

  bool get _isTodayCompleted => _isTodayCheckedIn && _isTodayCheckedOut;

  // (reserved for future date-based queries)
  // String _ymd(DateTime d) => '${d.year}-${d.month.toString().padLeft(2,'0')}-${d.day.toString().padLeft(2,'0')}';

  void _successSnack(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  void _handleDioError(DioException e) {
    String serverMsg =
        (e.response?.data is Map &&
            (e.response!.data as Map)['message'] != null)
        ? (e.response!.data['message']).toString()
        : e.message ?? 'Network error';
    // Graceful Bengali message for missing school context
    if (e.response?.statusCode == 422 &&
        serverMsg.toLowerCase().contains('school')) {
      serverMsg = 'স্কুল কনটেক্সট পাওয়া যায়নি – দয়া করে আবার চেষ্টা করুন।';
    }
    if (mounted) setState(() => _error = serverMsg);
  }

  @override
  void initState() {
    super.initState();
    _fetchTodayRecord();
    _fetchSettings();
    _flushOfflineQueue();
    _resolveSchoolId();
  }

  Future<void> _resolveSchoolId() async {
    try {
      final profile = ref.read(authProvider).value;
      int? sid;
      if (profile != null) {
        for (final r in profile.roles) {
          if (r.role == 'teacher' && r.schoolId != null) {
            sid = r.schoolId;
            break;
          }
        }
      }
      if (sid == null) {
        final data = await AuthRepository().me();
        final roles = (data['roles'] as List?) ?? [];
        for (final e in roles) {
          if (e is Map && e['role'] == 'teacher' && e['school_id'] != null) {
            sid = (e['school_id'] as num).toInt();
            break;
          }
        }
      }
      // Last resort: try to extract from today's attendance records
      if (sid == null) {
        try {
          final resp = await _dio.get('teacher/attendance');
          final data = resp.data;
          List list = [];
          if (data is List) list = data;
          if (data is Map && data['data'] is List) list = data['data'];
          if (list.isNotEmpty) {
            final first = list.first;
            if (first is Map && first['school_id'] != null) {
              sid = (first['school_id'] as num).toInt();
            }
          }
        } catch (_) {}
      }
      if (mounted) {
        setState(() {
          _schoolId = sid;
          if (sid == null) {
            _error = 'School ID not found. Please contact admin.';
          }
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _error = 'Failed to resolve School ID. Please check network.';
        });
      }
    }
  }
}

class _StatusHeader extends StatelessWidget {
  final Map<String, dynamic>? record;
  const _StatusHeader({required this.record});

  String _englishStatus(String? status) {
    switch ((status ?? '').toLowerCase()) {
      case 'present':
        return 'উপস্থিত';
      case 'late':
        return 'বিলম্ব';
      case 'absent':
        return 'অনুপস্থিত';
      default:
        return status ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    if (record == null) return const SizedBox.shrink();
    final checkIn = record!['check_in_time'] ?? '-';
    final checkOut = record!['check_out_time'] ?? '-';
    final status = _englishStatus(record!['status']?.toString());
    return Card(
      elevation: 0,
      color: Theme.of(context).colorScheme.surface,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              "Today's Attendance",
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text('Check-in: $checkIn'),
            Text('Check-out: $checkOut'),
            if (status.isNotEmpty) Text('Status: $status'),
          ],
        ),
      ),
    );
  }
}

class _SettingsBanner extends StatelessWidget {
  final Map<String, dynamic>? settings;
  final bool loading;
  const _SettingsBanner({required this.settings, required this.loading});
  @override
  Widget build(BuildContext context) {
    if (loading) {
      return Shimmer.fromColors(
        baseColor: Colors.grey.shade300,
        highlightColor: Colors.grey.shade100,
        child: Container(
          height: 60,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      );
    }
    if (settings == null) return const SizedBox.shrink();
    final start = settings!['check_in_start'];
    final end = settings!['check_in_end'];
    final late = settings!['late_threshold'];
    return Card(
      elevation: 0,
      color: Theme.of(context).colorScheme.surface,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            const Icon(Icons.schedule, color: Colors.deepPurple),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Check-in window: $start - $end',
                    style: const TextStyle(fontSize: 13),
                  ),
                  Text(
                    'Late after: $late',
                    style: const TextStyle(
                      fontSize: 12,
                      color: Colors.redAccent,
                    ),
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
