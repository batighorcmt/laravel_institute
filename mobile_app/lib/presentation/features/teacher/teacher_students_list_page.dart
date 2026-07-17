import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:dio/dio.dart';
import '../../../core/network/dio_client.dart';
import '../../../data/teacher/teacher_students_repository.dart';

class TeacherStudentsListPage extends StatefulWidget {
  const TeacherStudentsListPage({super.key});

  @override
  State<TeacherStudentsListPage> createState() =>
      _TeacherStudentsListPageState();
}

class _TeacherStudentsListPageState extends State<TeacherStudentsListPage> {
  late final Dio _dio;
  late final TeacherStudentsRepository _repo;

  int _page = 1;
  bool _loading = false;
  bool _hasMore = true;
  List<dynamic> _items = [];
  String? _error;

  List<Map<String, dynamic>> _classes = [];
  String? _selectedClassId;
  List<Map<String, dynamic>> _sections = [];
  String? _selectedSectionId;
  List<Map<String, dynamic>> _groups = [];
  String? _selectedGroupId;
  String? _selectedGender;
  List<Map<String, dynamic>> _years = [];
  String? _selectedYearId;
  List<String> _religions = [];
  String? _selectedReligion;
  List<String> _statuses = [];
  final String _selectedStatus = 'active';

  final TextEditingController _searchCtrl = TextEditingController();
  bool _sectionsLoading = false;
  bool _metaLoading = false;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _repo = TeacherStudentsRepository(_dio);
    _fetchInitialData();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchInitialData() async {
    setState(() => _metaLoading = true);
    try {
      final classes = await _repo.fetchClasses();
      // Fetch years, religions, statuses from multiple potential meta endpoints for reliability
      final metaEndpoints = [
        'teacher/students/meta',
        'teacher/exams/mark-entry/meta',
      ];
      for (final endpoint in metaEndpoints) {
        try {
          final res = await _dio.get(endpoint);
          final data = res.data;
          if (data is Map<String, dynamic>) {
            bool updated = false;
            setState(() {
              if (data['academic_years'] is List && _years.isEmpty) {
                _years = (data['academic_years'] as List)
                    .cast<Map<String, dynamic>>();
                updated = true;
              }
              if (data['religions'] is List && _religions.isEmpty) {
                _religions = (data['religions'] as List).cast<String>();
                updated = true;
              }
              if (data['statuses'] is List && _statuses.isEmpty) {
                _statuses = (data['statuses'] as List).cast<String>();
                updated = true;
              }
            });
            if (updated) break;
          }
        } catch (_) {}
      }

      if (mounted) {
        setState(() {
          _classes = classes;
          _metaLoading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _metaLoading = false);
    }
    _load(reset: true);
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading) return;
    setState(() => _loading = true);
    try {
      final page = reset ? 1 : _page + 1;
      final res = await _repo.fetchStudents(
        page: page,
        search: _searchCtrl.text,
        classId: _selectedClassId,
        sectionId: _selectedSectionId,
        groupId: _selectedGroupId,
        gender: _selectedGender,
        academicYear: _selectedYearId,
        religion: _selectedReligion,
        studentStatus: _selectedStatus,
      );
      final data = (res['data'] as List?) ?? [];
      final meta = (res['meta'] as Map?) ?? {};
      // Robust pagination detection: handle several meta shapes
      int current =
          (meta['current_page'] as int?) ??
          (meta['currentPage'] as int?) ??
          (meta['page'] as int?) ??
          page;
      int? last = (meta['last_page'] as int?) ?? (meta['lastPage'] as int?);
      bool nextPage = false;
      if (meta['next_page_url'] != null) {
        nextPage = true;
      } else if (last != null) {
        nextPage = current < last;
      } else if (meta.containsKey('total') && meta.containsKey('per_page')) {
        final total = (meta['total'] as num?)?.toInt();
        final per = (meta['per_page'] as num?)?.toInt();
        if (total != null && per != null && per > 0) {
          final computedLast = ((total + per - 1) / per).ceil();
          nextPage = current < computedLast;
        } else {
          nextPage = data.isNotEmpty;
        }
      } else {
        // Fallback heuristic: if we received exactly a full page of items
        // assume there may be more. Prefer using meta.per_page when
        // available, otherwise default to 40.
        final fallbackPerPage = (meta['per_page'] as int?) ?? 40;
        nextPage = data.isNotEmpty && data.length >= fallbackPerPage;
      }

      setState(() {
        _page = page;
        _hasMore = nextPage;
        _error = null;
        if (reset) {
          _items = data;
        } else {
          _items.addAll(data);
        }
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _hasMore = false;
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _call(String phone) async {
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Students')),
      body: Column(
        children: [
          if (_loading || _metaLoading || _sectionsLoading)
            const LinearProgressIndicator(minHeight: 2),

          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 4.0),
            child: Column(
              children: [
                // Row 1: Search and Class
                Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: TextField(
                        controller: _searchCtrl,
                        style: const TextStyle(fontSize: 12),
                        decoration: InputDecoration(
                          hintText: 'Search...',
                          prefixIcon: const Icon(Icons.search, size: 16),
                          border: const OutlineInputBorder(),
                          isDense: true,
                          contentPadding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 8,
                          ),
                          suffixIcon: _searchCtrl.text.isNotEmpty
                              ? IconButton(
                                  icon: const Icon(Icons.clear, size: 16),
                                  onPressed: () {
                                    _searchCtrl.clear();
                                    _load(reset: true);
                                  },
                                )
                              : null,
                        ),
                        onSubmitted: (_) => _load(reset: true),
                      ),
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        initialValue: _selectedClassId,
                        isExpanded: true,
                        hint: const Text(
                          'Class',
                          style: TextStyle(fontSize: 11),
                        ),
                        style: const TextStyle(
                          fontSize: 11,
                          color: Colors.black,
                        ),
                        decoration: const InputDecoration(
                          isDense: true,
                          border: OutlineInputBorder(),
                          contentPadding: EdgeInsets.symmetric(
                            horizontal: 6,
                            vertical: 8,
                          ),
                        ),
                        items: [
                          const DropdownMenuItem<String>(
                            value: null,
                            child: Text(
                              'Class: All',
                              style: TextStyle(fontSize: 11),
                            ),
                          ),
                          ..._classes.map(
                            (c) => DropdownMenuItem<String>(
                              value: c['id']?.toString(),
                              child: Text(
                                c['name']?.toString() ?? '',
                                style: const TextStyle(fontSize: 11),
                              ),
                            ),
                          ),
                        ],
                        onChanged: _metaLoading
                            ? null
                            : (val) async {
                                setState(() {
                                  _selectedClassId = val;
                                  _selectedSectionId = null;
                                  _selectedGroupId = null;
                                  _sections = [];
                                  _groups = [];
                                  _sectionsLoading = (val != null);
                                });
                                if (val != null) {
                                  try {
                                    final sections = await _repo.fetchSections(
                                      classId: val,
                                    );
                                    final groups = await _repo
                                        .fetchGroupsForClass(val);
                                    if (mounted) {
                                      setState(() {
                                        _sections = sections;
                                        _groups = groups;
                                        _sectionsLoading = false;
                                      });
                                    }
                                  } catch (_) {
                                    if (mounted) {
                                      setState(() => _sectionsLoading = false);
                                    }
                                  }
                                }
                                _load(reset: true);
                              },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                // Row 2: Section, Group, Religion
                Row(
                  children: [
                    Expanded(
                      child: _metaDropdown(
                        'Section',
                        _selectedSectionId,
                        _sections,
                        (val) {
                          setState(() => _selectedSectionId = val);
                          _load(reset: true);
                        },
                      ),
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: _metaDropdown('Group', _selectedGroupId, _groups, (
                        val,
                      ) {
                        setState(() => _selectedGroupId = val);
                        _load(reset: true);
                      }),
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        initialValue: _selectedReligion,
                        isExpanded: true,
                        hint: const Text(
                          'Religion',
                          style: TextStyle(fontSize: 11),
                        ),
                        style: const TextStyle(
                          fontSize: 11,
                          color: Colors.black,
                        ),
                        decoration: const InputDecoration(
                          isDense: true,
                          border: OutlineInputBorder(),
                          contentPadding: EdgeInsets.symmetric(
                            horizontal: 6,
                            vertical: 8,
                          ),
                        ),
                        items: [
                          const DropdownMenuItem<String>(
                            value: null,
                            child: Text(
                              'Rel: All',
                              style: TextStyle(fontSize: 11),
                            ),
                          ),
                          ..._religions.map(
                            (r) => DropdownMenuItem<String>(
                              value: r,
                              child: Text(
                                r,
                                style: const TextStyle(fontSize: 11),
                              ),
                            ),
                          ),
                        ],
                        onChanged: (v) {
                          setState(() => _selectedReligion = v);
                          _load(reset: true);
                        },
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          Expanded(
            child: _loading && _items.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                ? Center(
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(_error!),
                          const SizedBox(height: 8),
                          ElevatedButton(
                            onPressed: () => _load(reset: true),
                            child: const Text('Retry'),
                          ),
                        ],
                      ),
                    ),
                  )
                : _items.isEmpty
                ? const Center(child: Text('No students found'))
                : RefreshIndicator(
                    onRefresh: () => _load(reset: true),
                    child: ListView.separated(
                      itemCount: _items.length + (_hasMore ? 1 : 0),
                      separatorBuilder: (_, _) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        if (index >= _items.length) {
                          if (!_loading) _load();
                          return const Padding(
                            padding: EdgeInsets.all(16.0),
                            child: Center(child: CircularProgressIndicator()),
                          );
                        }
                        final it = _items[index] as Map<String, dynamic>;
                        final photoUrl = it['photo_url'] as String?;
                        final name = it['name'] as String? ?? '';
                        final roll = it['roll']?.toString() ?? '';
                        final cls = it['class'] as String? ?? '';
                        final section = it['section'] as String? ?? '';
                        final phone = it['phone'] as String? ?? '';
                        final id = it['id']?.toString() ?? '';
                        return ListTile(
                          leading: CircleAvatar(
                            backgroundImage:
                                photoUrl != null && photoUrl.isNotEmpty
                                ? CachedNetworkImageProvider(photoUrl)
                                : null,
                            child: (photoUrl == null || photoUrl.isEmpty)
                                ? const Icon(Icons.person)
                                : null,
                          ),
                          title: Text(name),
                          subtitle: Text('Roll: $roll  •  $cls-$section'),
                          trailing: IconButton(
                            icon: const Icon(Icons.call),
                            onPressed: phone.isNotEmpty
                                ? () => _call(phone)
                                : null,
                          ),
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => TeacherStudentProfilePage(
                                  studentId: id,
                                  initialData: it,
                                ),
                              ),
                            );
                          },
                        );
                      },
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _metaDropdown(
    String label,
    String? value,
    List<Map<String, dynamic>> items,
    ValueChanged<String?> onChanged,
  ) {
    return DropdownButtonFormField<String>(
      initialValue: value,
      isExpanded: true,
      hint: Text(label, style: const TextStyle(fontSize: 11)),
      style: const TextStyle(fontSize: 11, color: Colors.black),
      decoration: const InputDecoration(
        isDense: true,
        border: OutlineInputBorder(),
        contentPadding: EdgeInsets.symmetric(horizontal: 6, vertical: 8),
      ),
      items: [
        DropdownMenuItem<String>(
          value: null,
          child: Text('$label: All', style: const TextStyle(fontSize: 11)),
        ),
        ...items.map(
          (i) => DropdownMenuItem<String>(
            value: i['id']?.toString(),
            child: Text(
              i['name']?.toString() ?? '',
              style: const TextStyle(fontSize: 11),
            ),
          ),
        ),
      ],
      onChanged: onChanged,
    );
  }
}

class TeacherStudentProfilePage extends StatefulWidget {
  final String studentId;
  final Map<String, dynamic>? initialData;
  const TeacherStudentProfilePage({
    super.key,
    required this.studentId,
    this.initialData,
  });

  @override
  State<TeacherStudentProfilePage> createState() =>
      _TeacherStudentProfilePageState();
}

class _TeacherStudentProfilePageState extends State<TeacherStudentProfilePage> {
  static const Color _brand = Color(0xFF00BF6D);
  static const Color _brandDark = Color(0xFF049655);
  static const Color _bg = Color(0xFFF5F7F9);
  static const Color _ink = Color(0xFF1A1D1F);
  static const Color _muted = Color(0xFF6B7280);

  static const Color _hueAcademic = Color(0xFF00BF6D);
  static const Color _huePersonal = Color(0xFF4F46E5);
  static const Color _hueFamily = Color(0xFF2962FF);
  static const Color _hueAddress = Color(0xFF0FA3A3);
  static const Color _hueHistory = Color(0xFF8B5CF6);

  late final Dio _dio;
  late final TeacherStudentsRepository _repo;
  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _repo = TeacherStudentsRepository(_dio);
    _load();
  }

  Future<void> _load() async {
    // Prefer showing initialData immediately to avoid UX "404" flashes.
    setState(() => _loading = true);
    if (widget.initialData != null) {
      setState(() {
        _data = Map<String, dynamic>.from(widget.initialData!);
        _error = null;
        _loading = false;
      });

      // Attempt a background refresh if we have a valid id.
      if (widget.studentId.trim().isEmpty) return;
      try {
        final res = await _repo.fetchStudentProfile(widget.studentId);
        final inner = res['data'];
        final Map<String, dynamic> normalized = inner is Map<String, dynamic>
            ? inner
            : res;
        if (mounted) {
          setState(() {
            _data = normalized;
            _error = null;
          });
        }
      } catch (_) {
        // Keep initialData visible; don't overwrite with an error.
      }
      return;
    }

    try {
      if (widget.studentId.trim().isEmpty) {
        setState(() => _error = 'No student id provided');
        return;
      }
      final res = await _repo.fetchStudentProfile(widget.studentId);
      final inner = res['data'];
      final Map<String, dynamic> normalized = inner is Map<String, dynamic>
          ? inner
          : res;
      setState(() {
        _data = normalized;
        _error = null;
      });
    } catch (e) {
      setState(() => _error = 'Failed to load profile: ${e.toString()}');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // Helper: pick first non-empty value among keys, searching nested maps too
  String _pick(Map<String, dynamic> src, List<String> keys) {
    bool hasVal(dynamic v) => v != null && v.toString().trim().isNotEmpty;

    String stringify(dynamic v) {
      if (v == null) return '';
      if (v is String) return v.trim();
      if (v is num || v is bool) return v.toString();
      if (v is Map) {
        final m = v.cast<String, dynamic>();
        for (final k in ['name', 'title', 'code', 'label', 'value', 'en']) {
          final vv = m[k];
          if (hasVal(vv)) return vv.toString();
        }
      }
      return v.toString();
    }

    dynamic searchKey(dynamic node, String key) {
      if (node is Map<String, dynamic>) {
        if (hasVal(node[key])) return node[key];
        for (final nest in const [
          'student',
          'profile',
          'guardian',
          'guardians',
          'parent',
          'parents',
          'academic',
          'contact',
          'address',
          'addresses',
          'class',
          'section',
          'data',
          'present',
          'permanent',
        ]) {
          final nm = node[nest];
          if (nm is Map<String, dynamic>) {
            final v2 = searchKey(nm, key);
            if (hasVal(v2)) return v2;
          }
        }
        for (final e in node.values) {
          if (e is Map<String, dynamic> || e is List) {
            final v3 = searchKey(e, key);
            if (hasVal(v3)) return v3;
          }
        }
      } else if (node is List) {
        for (final it in node) {
          final v = searchKey(it, key);
          if (hasVal(v)) return v;
        }
      }
      return null;
    }

    for (final k in keys) {
      final v = searchKey(src, k);
      if (hasVal(v)) return stringify(v);
    }
    return '';
  }

  @override
  Widget build(BuildContext context) {
    final d = _data ?? {};
    final photoUrl = _pick(d, const [
      'photo_url',
      'photo',
      'image',
      'avatar_url',
    ]);
    final name = _combine(
      _tc(_pick(d, const ['name', 'student_name', 'full_name', 'studentName'])),
      _pick(d, const [
        'name_bn',
        'student_name_bn',
        'full_name_bn',
        'bn_name',
        'studentNameBn',
      ]),
    );
    final cls = _pick(d, const ['class', 'class_name']);
    final section = _pick(d, const ['section', 'section_name']);
    final roll = _pick(d, const ['roll', 'class_roll']);
    final studentCode = _pick(d, const [
      'student_id',
      'student_code',
      'admission_no',
      'registration_no',
    ]);
    final group = _pick(d, const ['group', 'group_name']);
    final shift = _pick(d, const ['shift']);
    final medium = _pick(d, const ['medium']);
    final session = _pick(d, const ['session', 'academic_session']);
    final year = _pick(d, const ['academic_year', 'year']);
    final optionalSubject = _pick(d, const [
      'optional_subject',
      'optionalSubject',
    ]);
    final schoolName = _combine(
      _pick(d, const ['school_name', 'school']),
      _pick(d, const ['school_name_bn', 'school_bn']),
    );
    final classTeacher = _pick(d, const ['class_teacher', 'teacher_name']);
    final classTeacherPhone = _pick(d, const [
      'class_teacher_phone',
      'teacher_phone',
    ]);

    final att = d['attendance_stats'] is Map
        ? Map<String, dynamic>.from(d['attendance_stats'] as Map)
        : null;
    final history = d['enrollment_history'] is List
        ? d['enrollment_history'] as List
        : [];
    final memberships = d['memberships'] is List
        ? d['memberships'] as List
        : [];

    final gender = _tc(_pick(d, const ['gender', 'gender_name', 'genderName']));
    final bloodGroup = _tc(
      _pick(d, const ['blood_group', 'bloodGroup', 'blood']),
    );
    final dob = _pick(d, const [
      'date_of_birth',
      'dob',
      'birth_date',
      'dateOfBirth',
    ]);
    final religion = _tc(
      _pick(d, const ['religion', 'religion_name', 'religionName']),
    );

    final email = _pick(d, const ['email']);

    final fatherName = _combine(
      _tc(
        _pick(d, const [
          'father_name',
          'father',
          'father_name_en',
          'fatherName',
        ]),
      ),
      _pick(d, const ['father_name_bn', 'fatherNameBn']),
    );
    final motherName = _combine(
      _tc(
        _pick(d, const [
          'mother_name',
          'mother',
          'mother_name_en',
          'motherName',
        ]),
      ),
      _pick(d, const ['mother_name_bn', 'motherNameBn']),
    );
    String fatherPhone = _pick(d, const [
      'father_phone',
      'father_mobile',
      'father_phone_number',
    ]);
    String motherPhone = _pick(d, const [
      'mother_phone',
      'mother_mobile',
      'mother_phone_number',
    ]);
    // Robust fallback: many API resources nest these under `guardians`.
    final guardiansRaw = d['guardians'];
    if ((fatherPhone.isEmpty || motherPhone.isEmpty) && guardiansRaw != null) {
      if (guardiansRaw is Map) {
        fatherPhone = fatherPhone.isNotEmpty
            ? fatherPhone
            : (guardiansRaw['father_phone'] ??
                      guardiansRaw['fatherPhone'] ??
                      guardiansRaw['father_mobile'] ??
                      '')
                  .toString();
        motherPhone = motherPhone.isNotEmpty
            ? motherPhone
            : (guardiansRaw['mother_phone'] ??
                      guardiansRaw['motherPhone'] ??
                      guardiansRaw['mother_mobile'] ??
                      '')
                  .toString();
      } else if (guardiansRaw is List && guardiansRaw.isNotEmpty) {
        final g0 = guardiansRaw.firstWhere((e) => e is Map, orElse: () => null);
        if (g0 is Map) {
          fatherPhone = fatherPhone.isNotEmpty
              ? fatherPhone
              : (g0['father_phone'] ??
                        g0['fatherPhone'] ??
                        g0['father_mobile'] ??
                        '')
                    .toString();
          motherPhone = motherPhone.isNotEmpty
              ? motherPhone
              : (g0['mother_phone'] ??
                        g0['motherPhone'] ??
                        g0['mother_mobile'] ??
                        '')
                    .toString();
        }
      }
    }
    final guardianName = _combine(
      _tc(
        _pick(d, const [
          'guardian_name',
          'local_guardian_name',
          'guardian_name_en',
        ]),
      ),
      _pick(d, const ['guardian_name_bn']),
    );
    final guardianRelation = _tc(_pick(d, const ['guardian_relation']));
    final guardianPhone = _pick(d, const [
      'guardian_phone',
      'phone',
      'guardian_mobile',
      'local_guardian_mobile',
      'guardian_contact',
      'guardian_phone_number',
      'guardian_contact_no',
    ]);

    final hasToday =
        d.containsKey('today_attendance') ||
        (d['today_evaluations'] as List? ?? []).isNotEmpty;

    return Scaffold(
      backgroundColor: _bg,
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _brand))
          : _error != null
          ? _buildErrorState()
          : DefaultTabController(
              length: 4,
              child: NestedScrollView(
                headerSliverBuilder: (context, innerBoxIsScrolled) => [
                  _buildSliverHeader(
                    context: context,
                    photoUrl: photoUrl,
                    name: name,
                    cls: cls,
                    section: section,
                    roll: roll,
                    studentCode: studentCode,
                    gender: gender,
                    group: group,
                    phone: guardianPhone.isNotEmpty
                        ? guardianPhone
                        : fatherPhone,
                    email: email,
                  ),
                  SliverPersistentHeader(
                    pinned: true,
                    delegate: _StickyTabBarDelegate(
                      TabBar(
                        labelColor: _brand,
                        unselectedLabelColor: _muted,
                        indicatorColor: _brand,
                        indicatorWeight: 3,
                        labelStyle: const TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                        ),
                        unselectedLabelStyle: const TextStyle(
                          fontWeight: FontWeight.w500,
                          fontSize: 13,
                        ),
                        tabs: const [
                          Tab(text: 'তথ্য'),
                          Tab(text: 'পরিবার'),
                          Tab(text: 'ঠিকানা'),
                          Tab(text: 'ইতিহাস'),
                        ],
                      ),
                    ),
                  ),
                ],
                body: TabBarView(
                  children: [
                    _buildInfoTab(
                      d: d,
                      studentCode: studentCode,
                      dob: dob,
                      religion: religion,
                      cls: cls,
                      section: section,
                      roll: roll,
                      group: group,
                      shift: shift,
                      medium: medium,
                      session: session,
                      year: year,
                      optionalSubject: optionalSubject,
                      schoolName: schoolName,
                      gender: gender,
                      bloodGroup: bloodGroup,
                      att: att,
                    ),
                    _buildFamilyTab(
                      classTeacher: classTeacher,
                      classTeacherPhone: classTeacherPhone,
                      fatherName: fatherName,
                      motherName: motherName,
                      fatherPhone: fatherPhone,
                      motherPhone: motherPhone,
                      guardianName: guardianName,
                      guardianPhone: guardianPhone,
                      guardianRelation: guardianRelation,
                    ),
                    _buildAddressTab(d),
                    _buildHistoryTab(
                      d: d,
                      history: history,
                      memberships: memberships,
                      hasToday: hasToday,
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  // ───────────────────────────── Error state ─────────────────────────────

  Widget _buildErrorState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.red.withValues(alpha: 0.08),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.wifi_off_rounded,
                color: Colors.redAccent,
                size: 36,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              _error ?? 'কিছু একটা সমস্যা হয়েছে',
              textAlign: TextAlign.center,
              style: const TextStyle(color: _muted),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _load,
              icon: const Icon(Icons.refresh),
              label: const Text('আবার চেষ্টা করুন'),
              style: ElevatedButton.styleFrom(
                backgroundColor: _brand,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                padding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 12,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ───────────────────────────── Header ─────────────────────────────

  Widget _buildSliverHeader({
    required BuildContext context,
    required String photoUrl,
    required String name,
    required String cls,
    required String section,
    required String roll,
    required String studentCode,
    required String gender,
    required String group,
    required String phone,
    required String email,
  }) {
    return SliverAppBar(
      pinned: true,
      expandedHeight: 320,
      backgroundColor: _brand,
      foregroundColor: Colors.white,
      elevation: 0,
      actions: [
        if (_data != null)
          IconButton(
            tooltip: 'Raw data',
            icon: const Icon(Icons.data_object),
            onPressed: () {
              final pretty = const JsonEncoder.withIndent('  ').convert(_data);
              showModalBottomSheet(
                context: context,
                isScrollControlled: true,
                builder: (ctx) => DraggableScrollableSheet(
                  expand: false,
                  initialChildSize: 0.75,
                  maxChildSize: 0.95,
                  builder: (_, controller) => Padding(
                    padding: const EdgeInsets.all(12),
                    child: SingleChildScrollView(
                      controller: controller,
                      child: SelectableText(pretty),
                    ),
                  ),
                ),
              );
            },
          ),
      ],
      flexibleSpace: FlexibleSpaceBar(
        collapseMode: CollapseMode.pin,
        background: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [_brand, _brandDark],
            ),
          ),
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 48, 20, 12),
              child: SingleChildScrollView(
                physics: const ClampingScrollPhysics(),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.5),
                          width: 2,
                        ),
                      ),
                      child: CircleAvatar(
                        radius: 42,
                        backgroundColor: Colors.white.withValues(alpha: 0.2),
                        backgroundImage: photoUrl.isNotEmpty
                            ? CachedNetworkImageProvider(photoUrl)
                            : null,
                        child: photoUrl.isEmpty
                            ? const Icon(
                                Icons.person,
                                size: 42,
                                color: Colors.white,
                              )
                            : null,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      name.isNotEmpty ? name : 'শিক্ষার্থী',
                      textAlign: TextAlign.center,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                        fontSize: 20,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      [
                        if (cls.isNotEmpty || section.isNotEmpty)
                          '$cls - $section',
                        if (roll.isNotEmpty) 'Roll: $roll',
                      ].where((e) => e.isNotEmpty).join('  •  '),
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.85),
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      alignment: WrapAlignment.center,
                      children: [
                        if (studentCode.isNotEmpty)
                          _headerChip(
                            icon: Icons.badge_outlined,
                            label: studentCode,
                          ),
                        if (group.isNotEmpty)
                          _headerChip(
                            icon: Icons.workspaces_outlined,
                            label: group,
                          ),
                        if (gender.isNotEmpty)
                          _headerChip(icon: Icons.wc_outlined, label: gender),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: _quickActionButton(
                            icon: Icons.call_rounded,
                            label: phone.isNotEmpty ? 'কল করুন' : 'নম্বর নেই',
                            onPressed: phone.isEmpty
                                ? null
                                : () async {
                                    final uri = Uri(scheme: 'tel', path: phone);
                                    if (await canLaunchUrl(uri)) {
                                      await launchUrl(uri);
                                    }
                                  },
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: _quickActionButton(
                            icon: Icons.mail_outline_rounded,
                            label: email.isNotEmpty ? 'ইমেইল' : 'ইমেইল নেই',
                            filled: false,
                            onPressed: email.isEmpty
                                ? null
                                : () async {
                                    final uri = Uri(
                                      scheme: 'mailto',
                                      path: email,
                                    );
                                    if (await canLaunchUrl(uri)) {
                                      await launchUrl(uri);
                                    }
                                  },
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _headerChip({required IconData icon, required String label}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: Colors.white),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(color: Colors.white, fontSize: 12),
          ),
        ],
      ),
    );
  }

  Widget _quickActionButton({
    required IconData icon,
    required String label,
    required VoidCallback? onPressed,
    bool filled = true,
  }) {
    if (filled) {
      return ElevatedButton.icon(
        onPressed: onPressed,
        icon: Icon(icon, size: 18),
        label: Text(
          label,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
        ),
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.white,
          foregroundColor: _brandDark,
          disabledBackgroundColor: Colors.white.withValues(alpha: 0.3),
          disabledForegroundColor: Colors.white70,
          elevation: 0,
          padding: const EdgeInsets.symmetric(vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
        ),
      );
    }
    return OutlinedButton.icon(
      onPressed: onPressed,
      icon: Icon(icon, size: 18),
      label: Text(
        label,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
      ),
      style: OutlinedButton.styleFrom(
        foregroundColor: Colors.white,
        disabledForegroundColor: Colors.white54,
        side: BorderSide(color: Colors.white.withValues(alpha: 0.6)),
        padding: const EdgeInsets.symmetric(vertical: 12),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      ),
    );
  }

  // ───────────────────────────── Tab: তথ্য (Info) ─────────────────────────────

  Widget _buildInfoTab({
    required Map<String, dynamic> d,
    required String studentCode,
    required String dob,
    required String religion,
    required String cls,
    required String section,
    required String roll,
    required String group,
    required String shift,
    required String medium,
    required String session,
    required String year,
    required String optionalSubject,
    required String schoolName,
    required String gender,
    required String bloodGroup,
    required Map<String, dynamic>? att,
  }) {
    final statEntries = <MapEntry<String, String>>[
      MapEntry('স্টুডেন্ট আইডি', studentCode),
      MapEntry('জন্ম তারিখ', dob),
      MapEntry('ধর্ম', religion),
      MapEntry('শ্রেণি', cls),
      MapEntry('শাখা', section),
      MapEntry('রোল', roll),
      MapEntry('গ্রুপ', group),
      MapEntry('শিফট', shift),
      MapEntry('মাধ্যম', medium),
      MapEntry('সেশন', session),
      MapEntry('বছর', year),
      MapEntry('ঐচ্ছিক বিষয়', optionalSubject),
      MapEntry('স্কুল', schoolName),
    ].where((e) => e.value.trim().isNotEmpty).toList();

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      children: [
        if (statEntries.isNotEmpty)
          _sectionCard(
            icon: Icons.grid_view_rounded,
            iconColor: _hueAcademic,
            title: 'একাডেমিক তথ্য',
            child: _statGrid(statEntries, _hueAcademic),
          ),
        if (statEntries.isNotEmpty) const SizedBox(height: 14),

        _sectionCard(
          icon: Icons.badge_outlined,
          iconColor: _huePersonal,
          title: 'ব্যক্তিগত তথ্য',
          child: Column(
            children: [
              _infoRow(icon: Icons.wc, label: 'লিঙ্গ', value: gender),
              _infoRow(
                icon: Icons.bloodtype_outlined,
                label: 'রক্তের গ্রুপ',
                value: bloodGroup,
              ),
              _infoRow(
                icon: Icons.cake_outlined,
                label: 'জন্ম তারিখ',
                value: dob,
              ),
              _infoRow(
                icon: Icons.self_improvement,
                label: 'ধর্ম',
                value: religion,
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),

        _sectionCard(
          icon: Icons.school_outlined,
          iconColor: _hueAcademic,
          title: 'ভর্তি ও পূর্ববর্তী তথ্য',
          child: Column(
            children: [
              _infoRow(
                icon: Icons.account_balance_outlined,
                label: 'পূর্ববর্তী স্কুল',
                value: _pick(d, const [
                  'previous_school',
                  'last_school',
                  'prev_school',
                  'previousSchool',
                  'lastSchool',
                ]),
              ),
              Row(
                children: [
                  Expanded(
                    child: _infoRow(
                      icon: Icons.history_edu_outlined,
                      label: 'পাস বছর',
                      value: _pick(d, const [
                        'pass_year',
                        'passing_year',
                        'passYear',
                        'passingYear',
                      ]),
                    ),
                  ),
                  Expanded(
                    child: _infoRow(
                      icon: Icons.assignment_turned_in_outlined,
                      label: 'ফলাফল',
                      value: _pick(d, const [
                        'previous_result',
                        'last_result',
                        'previousResult',
                        'result',
                        'grade',
                      ]),
                    ),
                  ),
                ],
              ),
              _infoRow(
                icon: Icons.notes_outlined,
                label: 'মন্তব্য',
                value: _pick(d, const [
                  'previous_remarks',
                  'remarks',
                  'achievement',
                  'previousRemarks',
                  'notes',
                ]),
              ),
              _infoRow(
                icon: Icons.date_range_outlined,
                label: 'ভর্তির তারিখ',
                value: _pick(d, const [
                  'admission_date',
                  'admissionDate',
                  'admit_date',
                  'date_admitted',
                ]),
              ),
              _infoRow(
                icon: Icons.verified_user_outlined,
                label: 'অবস্থা',
                value: _tc(
                  _pick(d, const [
                    'status',
                    'student_status',
                    'active_status',
                    'studentStatus',
                    'enrollment_status',
                  ]),
                ),
              ),
              _infoRow(
                icon: Icons.account_balance_outlined,
                label: 'স্কুল',
                value: schoolName.isNotEmpty
                    ? schoolName
                    : _pick(d, const [
                        'school_name',
                        'schoolName',
                        'institute_name',
                      ]),
              ),
              if (optionalSubject.isNotEmpty)
                _infoRow(
                  icon: Icons.subject_outlined,
                  label: 'ঐচ্ছিক বিষয়',
                  value: optionalSubject,
                ),
            ],
          ),
        ),

        if (att != null || d.containsKey('working_days')) ...[
          const SizedBox(height: 14),
          _sectionCard(
            icon: Icons.fact_check_outlined,
            iconColor: _hueAcademic,
            title: 'হাজিরার সারসংক্ষেপ',
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _attendanceStat(
                      'উপস্থিত',
                      (att?['present'] ?? '0').toString(),
                      const Color(0xFF16A34A),
                    ),
                    _attendanceStat(
                      'অনুপস্থিত',
                      (att?['absent'] ?? '0').toString(),
                      const Color(0xFFDC2626),
                    ),
                    _attendanceStat(
                      'বিলম্ব',
                      (att?['late'] ?? '0').toString(),
                      const Color(0xFFD97706),
                    ),
                    _attendanceStat(
                      'ছুটি',
                      (att?['leave'] ?? '0').toString(),
                      const Color(0xFF2563EB),
                    ),
                  ],
                ),
                const Divider(height: 28),
                Text(
                  'মোট কর্মদিবস: ${_pick(d, const ['working_days']).isEmpty ? '0' : _pick(d, const ['working_days'])}',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                    color: _ink,
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  // ───────────────────────────── Tab: পরিবার (Family) ─────────────────────────────

  Widget _buildFamilyTab({
    required String classTeacher,
    required String classTeacherPhone,
    required String fatherName,
    required String motherName,
    required String fatherPhone,
    required String motherPhone,
    required String guardianName,
    required String guardianPhone,
    required String guardianRelation,
  }) {
    final hasAny =
        classTeacher.isNotEmpty ||
        fatherName.isNotEmpty ||
        motherName.isNotEmpty ||
        guardianName.isNotEmpty;

    if (!hasAny) return _emptyTab('পরিবার সংক্রান্ত কোনো তথ্য পাওয়া যায়নি');

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      children: [
        if (classTeacher.isNotEmpty) ...[
          _sectionCard(
            icon: Icons.person_pin_outlined,
            iconColor: _hueFamily,
            title: 'শ্রেণি শিক্ষক',
            child: _personTile(
              icon: Icons.person_pin,
              color: _hueFamily,
              name: classTeacher,
              phone: classTeacherPhone,
            ),
          ),
          const SizedBox(height: 14),
        ],

        _sectionCard(
          icon: Icons.family_restroom_outlined,
          iconColor: _hueFamily,
          title: 'অভিভাবকের তথ্য',
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: _miniPersonCard(
                      icon: Icons.man,
                      title: 'বাবা',
                      subtitle: fatherName,
                      color: const Color(0xFF2563EB),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _miniPersonCard(
                      icon: Icons.woman,
                      title: 'মা',
                      subtitle: motherName,
                      color: const Color(0xFFDB2777),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              _infoRow(
                icon: Icons.call_outlined,
                label: 'বাবার ফোন',
                value: fatherPhone,
                onTap: fatherPhone.trim().isEmpty
                    ? null
                    : () async {
                        final uri = Uri(scheme: 'tel', path: fatherPhone);
                        if (await canLaunchUrl(uri)) {
                          await launchUrl(uri);
                        }
                      },
              ),
              _infoRow(
                icon: Icons.call_outlined,
                label: 'মায়ের ফোন',
                value: motherPhone,
                onTap: motherPhone.trim().isEmpty
                    ? null
                    : () async {
                        final uri = Uri(scheme: 'tel', path: motherPhone);
                        if (await canLaunchUrl(uri)) {
                          await launchUrl(uri);
                        }
                      },
              ),
              _infoRow(
                icon: Icons.group_outlined,
                label: 'অভিভাবক',
                value: guardianName,
              ),
              _infoRow(
                icon: Icons.call_outlined,
                label: 'অভিভাবকের ফোন',
                value: guardianPhone,
                onTap: guardianPhone.trim().isEmpty
                    ? null
                    : () async {
                        final uri = Uri(scheme: 'tel', path: guardianPhone);
                        if (await canLaunchUrl(uri)) {
                          await launchUrl(uri);
                        }
                      },
              ),
              _infoRow(
                icon: Icons.diversity_3_outlined,
                label: 'সম্পর্ক',
                value: guardianRelation,
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _personTile({
    required IconData icon,
    required Color color,
    required String name,
    required String phone,
  }) {
    return Row(
      children: [
        CircleAvatar(
          radius: 22,
          backgroundColor: color.withValues(alpha: 0.12),
          child: Icon(icon, color: color),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                name,
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  color: _ink,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                phone.isNotEmpty ? phone : 'ফোন নম্বর নেই',
                style: const TextStyle(color: _muted, fontSize: 13),
              ),
            ],
          ),
        ),
        if (phone.isNotEmpty)
          IconButton(
            icon: const Icon(Icons.call_rounded, color: _brand),
            onPressed: () async {
              final uri = Uri(scheme: 'tel', path: phone);
              if (await canLaunchUrl(uri)) {
                await launchUrl(uri);
              }
            },
          ),
      ],
    );
  }

  Widget _miniPersonCard({
    required IconData icon,
    required String title,
    required String subtitle,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.18)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 16,
            backgroundColor: color.withValues(alpha: 0.15),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(height: 8),
          Text(
            title,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.w600,
              fontSize: 12,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            subtitle.isNotEmpty ? subtitle : 'N/A',
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              color: _ink,
              fontWeight: FontWeight.w600,
              fontSize: 13,
            ),
          ),
        ],
      ),
    );
  }

  // ───────────────────────────── Tab: ঠিকানা (Address) ─────────────────────────────

  Widget _buildAddressTab(Map<String, dynamic> d) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      children: [
        _sectionCard(
          icon: Icons.place_outlined,
          iconColor: _hueAddress,
          title: 'বর্তমান ঠিকানা',
          child: Column(
            children: [
              _infoRow(
                icon: Icons.location_city_outlined,
                label: 'গ্রাম',
                value: _pick(d, const [
                  'present_village',
                  'village',
                  'gram',
                  'present_gram',
                ]),
              ),
              _infoRow(
                icon: Icons.home_work_outlined,
                label: 'পাড়া/মহল্লা',
                value: _pick(d, const [
                  'present_para_moholla',
                  'para_moholla',
                  'para',
                  'moholla',
                ]),
              ),
              _infoRow(
                icon: Icons.local_post_office_outlined,
                label: 'ডাকঘর',
                value: _pick(d, const [
                  'present_post_office',
                  'post_office',
                  'po',
                  'post',
                ]),
              ),
              _infoRow(
                icon: Icons.map_outlined,
                label: 'উপজেলা',
                value: _pick(d, const [
                  'present_upazilla',
                  'present_upazila',
                  'upazila',
                  'upazilla',
                  'thana',
                ]),
              ),
              _infoRow(
                icon: Icons.location_on_outlined,
                label: 'জেলা',
                value: _pick(d, const [
                  'present_district',
                  'district',
                  'zilla',
                  'zila',
                ]),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        _sectionCard(
          icon: Icons.home_outlined,
          iconColor: _hueAddress,
          title: 'স্থায়ী ঠিকানা',
          child: Column(
            children: [
              _infoRow(
                icon: Icons.location_city_outlined,
                label: 'গ্রাম',
                value: _pick(d, const [
                  'permanent_village',
                  'perm_village',
                  'permanent_gram',
                  'permanent_gram_bn',
                  'perm_gram',
                ]),
              ),
              _infoRow(
                icon: Icons.home_work_outlined,
                label: 'পাড়া/মহল্লা',
                value: _pick(d, const [
                  'permanent_para_moholla',
                  'perm_para_moholla',
                  'perm_para',
                  'perm_moholla',
                  'permanent_para',
                ]),
              ),
              _infoRow(
                icon: Icons.local_post_office_outlined,
                label: 'ডাকঘর',
                value: _pick(d, const [
                  'permanent_post_office',
                  'perm_post_office',
                  'perm_po',
                  'perm_post',
                  'permanent_po',
                ]),
              ),
              _infoRow(
                icon: Icons.map_outlined,
                label: 'উপজেলা',
                value: _pick(d, const [
                  'permanent_upazilla',
                  'perm_upazilla',
                  'perm_upazila',
                  'permanent_upazila',
                  'perm_thana',
                  'permanent_thana',
                ]),
              ),
              _infoRow(
                icon: Icons.location_on_outlined,
                label: 'জেলা',
                value: _pick(d, const [
                  'permanent_district',
                  'perm_district',
                  'perm_zilla',
                  'perm_zila',
                  'permanent_zilla',
                  'permanent_zila',
                ]),
              ),
            ],
          ),
        ),
      ],
    );
  }

  // ───────────────────────────── Tab: ইতিহাস (History) ─────────────────────────────

  Widget _buildHistoryTab({
    required Map<String, dynamic> d,
    required List history,
    required List memberships,
    required bool hasToday,
  }) {
    if (history.isEmpty && memberships.isEmpty && !hasToday) {
      return _emptyTab('ইতিহাস সংক্রান্ত কোনো তথ্য পাওয়া যায়নি');
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      children: [
        if (hasToday) ...[
          _sectionCard(
            icon: Icons.today_outlined,
            iconColor: _hueHistory,
            title: 'আজকের অবস্থা',
            child: Column(
              children: [
                if (d['today_attendance'] != null &&
                    d['today_attendance']['class'] != null)
                  _infoRow(
                    icon: Icons.check_circle_outline,
                    label: 'আজকের হাজিরা',
                    value: _tc(
                      (d['today_attendance']['class']['status'] ?? 'N/A')
                          .toString(),
                    ),
                  ),
                if ((d['today_evaluations'] as List? ?? []).isNotEmpty)
                  ...(d['today_evaluations'] as List).map((ev) {
                    final Map<String, dynamic> row = ev is Map
                        ? Map<String, dynamic>.from(ev)
                        : {};
                    return ListTile(
                      dense: true,
                      contentPadding: EdgeInsets.zero,
                      leading: Icon(
                        Icons.menu_book_outlined,
                        color: _hueHistory,
                        size: 20,
                      ),
                      title: Text(
                        '${row['subject'] ?? 'Subject'} (Period ${row['period'] ?? ''})',
                      ),
                      subtitle: Text('Status: ${row['status'] ?? 'Pending'}'),
                    );
                  }),
              ],
            ),
          ),
          const SizedBox(height: 14),
        ],

        if (memberships.isNotEmpty) ...[
          _sectionCard(
            icon: Icons.groups_outlined,
            iconColor: _hueHistory,
            title: 'টিম ও কার্যক্রম',
            child: Column(
              children: memberships.map((tm) {
                final Map<String, dynamic> row = tm is Map
                    ? Map<String, dynamic>.from(tm)
                    : {};
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: CircleAvatar(
                    backgroundColor: _hueHistory.withValues(alpha: 0.12),
                    child: Icon(Icons.groups, color: _hueHistory),
                  ),
                  title: Text(
                    _pick(row, const ['name', 'team_name']),
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text('অবস্থা: ${_pick(row, const ['status'])}'),
                  trailing: _pick(row, const ['joined_at']).isNotEmpty
                      ? Text(
                          _pick(row, const ['joined_at']).split(' ').first,
                          style: const TextStyle(fontSize: 12, color: _muted),
                        )
                      : null,
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 14),
        ],

        if (history.isNotEmpty)
          _sectionCard(
            icon: Icons.history_outlined,
            iconColor: _hueHistory,
            title: 'ভর্তির ইতিহাস',
            child: Theme(
              data: Theme.of(
                context,
              ).copyWith(dividerColor: Colors.transparent),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: DataTable(
                  columnSpacing: 24,
                  horizontalMargin: 0,
                  headingTextStyle: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: _ink,
                  ),
                  columns: const [
                    DataColumn(label: Text('বছর')),
                    DataColumn(label: Text('শ্রেণি')),
                    DataColumn(label: Text('শাখা')),
                    DataColumn(label: Text('রোল')),
                  ],
                  rows: history.map((en) {
                    final Map<String, dynamic> row = en is Map
                        ? Map<String, dynamic>.from(en)
                        : {};
                    return DataRow(
                      cells: [
                        DataCell(
                          Text(
                            _pick(row, const [
                              'academic_year',
                              'year',
                              'session',
                            ]),
                          ),
                        ),
                        DataCell(
                          Text(_pick(row, const ['class', 'class_name'])),
                        ),
                        DataCell(
                          Text(_pick(row, const ['section', 'section_name'])),
                        ),
                        DataCell(Text(_pick(row, const ['roll', 'roll_no']))),
                      ],
                    );
                  }).toList(),
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _emptyTab(String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: _brand.withValues(alpha: 0.08),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.inbox_outlined, color: _brand, size: 32),
            ),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: _muted),
            ),
          ],
        ),
      ),
    );
  }

  // ───────────────────────────── Shared building blocks ─────────────────────────────

  Widget _sectionCard({
    required IconData icon,
    required Color iconColor,
    required String title,
    required Widget child,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0F000000),
            blurRadius: 16,
            offset: Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: iconColor, size: 18),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                    color: _ink,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }

  Widget _statGrid(List<MapEntry<String, String>> entries, Color tint) {
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: entries.map((e) {
        return Container(
          width: (MediaQuery.of(context).size.width - 32 - 32 - 10) / 2,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: tint.withValues(alpha: 0.06),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: tint.withValues(alpha: 0.15)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                e.key,
                style: const TextStyle(
                  color: _muted,
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                e.value,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: _ink,
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _infoRow({
    required IconData icon,
    required String label,
    required String value,
    VoidCallback? onTap,
  }) {
    final display = value.trim().isNotEmpty ? value : 'N/A';
    final active = onTap != null;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(
                  color: active
                      ? _brand.withValues(alpha: 0.1)
                      : const Color(0xFFF3F4F6),
                  borderRadius: BorderRadius.circular(9),
                ),
                child: Icon(icon, size: 16, color: active ? _brand : _muted),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      label,
                      style: const TextStyle(
                        fontSize: 12,
                        color: _muted,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      display,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: active ? _brandDark : _ink,
                      ),
                    ),
                  ],
                ),
              ),
              if (active)
                const Icon(Icons.call_rounded, size: 18, color: _brand),
            ],
          ),
        ),
      ),
    );
  }

  Widget _attendanceStat(String label, String value, Color color) {
    return Column(
      children: [
        Container(
          width: 52,
          height: 52,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            shape: BoxShape.circle,
          ),
          child: Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: const TextStyle(
            fontSize: 12,
            color: _muted,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  // Title Case helper for ASCII words (keeps non-Latin intact)
  String _tc(String s) {
    final str = s.trim();
    if (str.isEmpty) return '';
    return str
        .split(RegExp(r"\s+"))
        .map(
          (w) => w.isEmpty
              ? w
              : w[0].toUpperCase() +
                    (w.length > 1 ? w.substring(1).toLowerCase() : ''),
        )
        .join(' ');
  }

  String _combine(String en, String bn, {bool joinWithNewline = false}) {
    final a = _tc(en.trim());
    final b = bn.trim();
    if (a.isEmpty && b.isEmpty) return '';
    if (a.isEmpty) return b;
    if (b.isEmpty) return a;
    return joinWithNewline ? '$a\n$b' : '$a ($b)';
  }
}

class _StickyTabBarDelegate extends SliverPersistentHeaderDelegate {
  final TabBar tabBar;
  _StickyTabBarDelegate(this.tabBar);

  @override
  double get minExtent => tabBar.preferredSize.height;
  @override
  double get maxExtent => tabBar.preferredSize.height;

  @override
  Widget build(
    BuildContext context,
    double shrinkOffset,
    bool overlapsContent,
  ) {
    return Container(color: Colors.white, child: tabBar);
  }

  @override
  bool shouldRebuild(covariant _StickyTabBarDelegate oldDelegate) {
    return tabBar != oldDelegate.tabBar;
  }
}
