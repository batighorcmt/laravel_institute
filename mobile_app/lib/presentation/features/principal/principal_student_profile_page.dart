import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:dio/dio.dart';
import '../../../core/network/dio_client.dart';
import '../../../data/teacher/teacher_students_repository.dart';

class PrincipalStudentProfilePage extends StatefulWidget {
  final String? studentId;
  final dynamic initialData;
  const PrincipalStudentProfilePage({
    super.key,
    this.studentId,
    this.initialData,
  });

  @override
  State<PrincipalStudentProfilePage> createState() =>
      _PrincipalStudentProfilePageState();
}

class _PrincipalStudentProfilePageState
    extends State<PrincipalStudentProfilePage> {
  late final Dio _dio;
  late final TeacherStudentsRepository _repo;
  Map<String, dynamic>? _data;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _repo = TeacherStudentsRepository(_dio);
    _load();
  }

  Future<void> _load() async {
    if (widget.studentId == null && widget.initialData == null) return;
    setState(() => _loading = true);
    try {
      // If initial data is provided (from the modal), prefer showing it immediately
      // so the user sees content even if the backend lookup would 404.
      if (widget.initialData != null) {
        if (widget.initialData is Map<String, dynamic>) {
          setState(() {
            _data = widget.initialData as Map<String, dynamic>;
            _error = null;
          });
        } else if (widget.initialData is Map) {
          setState(() {
            _data = Map<String, dynamic>.from(widget.initialData as Map);
            _error = null;
          });
        }
        // Do not force a remote fetch automatically; allow the user to Retry.
        return;
      }

      // Otherwise attempt to fetch by ID.
      if (widget.studentId != null) {
        final res = await _repo.fetchStudentProfile(widget.studentId!);
        final inner = res['data'];
        final Map<String, dynamic> normalized = inner is Map<String, dynamic>
            ? inner
            : res;
        setState(() {
          _data = normalized;
          _error = null;
        });
        return;
      }
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _pick(Map<String, dynamic> src, List<String> keys) {
    bool _hasVal(dynamic v) => v != null && v.toString().trim().isNotEmpty;
    dynamic _searchKey(dynamic node, String key) {
      if (node is Map) {
        if (_hasVal(node[key])) return node[key];
        for (final v in node.values) {
          if (v is Map || v is List) {
            final r = _searchKey(v, key);
            if (_hasVal(r)) return r;
          }
        }
      } else if (node is List) {
        for (final it in node) {
          final v = _searchKey(it, key);
          if (_hasVal(v)) return v;
        }
      }
      return null;
    }

    for (final k in keys) {
      final v = _searchKey(_data ?? {}, k);
      if (_hasVal(v)) return v.toString();
    }
    return '';
  }

  Future<void> _call(String phone) async {
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri))
      await launchUrl(uri);
    else
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Cannot place call')));
  }

  @override
  Widget build(BuildContext context) {
    final d = _data ?? {};
    final photo = _pick(d, ['photo_url', 'photo', 'image', 'avatar']);
    final name = _pick(d, ['name', 'full_name', 'student_name']);
    final father = _pick(d, ['father_name', 'father', 'father_name_en']);
    final mother = _pick(d, ['mother_name', 'mother', 'mother_name_en']);
    final guardianPhone = _pick(d, [
      'guardian_phone',
      'guardian_mobile',
      'phone',
      'mobile',
    ]);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Student Profile'),
        actions: [
          IconButton(
            icon: const Icon(Icons.call),
            onPressed: guardianPhone.isNotEmpty
                ? () => _call(guardianPhone)
                : null,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(_error!),
                  const SizedBox(height: 8),
                  ElevatedButton(onPressed: _load, child: const Text('Retry')),
                ],
              ),
            )
          : SingleChildScrollView(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    if (photo.isNotEmpty)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: CachedNetworkImage(
                          imageUrl: photo,
                          width: 120,
                          height: 120,
                          fit: BoxFit.cover,
                          placeholder: (c, u) => Container(
                            width: 120,
                            height: 120,
                            color: Colors.grey.shade200,
                          ),
                          errorWidget: (c, u, e) => Container(
                            width: 120,
                            height: 120,
                            color: Colors.grey.shade200,
                          ),
                        ),
                      ),
                    if (photo.isEmpty)
                      Container(
                        width: 120,
                        height: 120,
                        color: Colors.grey.shade200,
                        child: const Icon(Icons.person, size: 64),
                      ),
                    const SizedBox(height: 12),
                    Text(
                      name.isNotEmpty ? name : 'Student',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 6),
                    if (father.isNotEmpty) Text('পিতা: $father'),
                    if (mother.isNotEmpty) Text('মাতা: $mother'),
                    const SizedBox(height: 12),
                    ElevatedButton.icon(
                      onPressed: guardianPhone.isNotEmpty
                          ? () => _call(guardianPhone)
                          : null,
                      icon: const Icon(Icons.call),
                      label: Text(
                        guardianPhone.isNotEmpty
                            ? 'কল $guardianPhone'
                            : 'No phone',
                      ),
                    ),
                    const SizedBox(height: 16),
                    // show raw data button
                    ElevatedButton(
                      onPressed: () {
                        final pretty = const JsonEncoder.withIndent(
                          '  ',
                        ).convert(d);
                        showModalBottomSheet(
                          context: context,
                          builder: (_) => SingleChildScrollView(
                            child: Padding(
                              padding: const EdgeInsets.all(12),
                              child: Text(pretty),
                            ),
                          ),
                        );
                      },
                      child: const Text('Raw'),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}
