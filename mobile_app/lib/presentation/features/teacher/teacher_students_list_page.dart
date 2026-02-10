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
  final _searchCtrl = TextEditingController();
  late final Dio _dio;
  late final TeacherStudentsRepository _repo;

  int _page = 1;
  bool _loading = false;
  bool _hasMore = true;
  List<dynamic> _items = [];
  String? _error;

  String? _classId;
  String? _sectionId;
  String? _groupId;
  String? _gender;
  List<Map<String, dynamic>> _classes = [];
  List<Map<String, dynamic>> _sections = [];
  List<Map<String, dynamic>> _groups = [];
  List<String> _genderOptions = [];

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _repo = TeacherStudentsRepository(_dio);
    _preloadFilters();
  }

  Future<void> _preloadFilters() async {
    try {
      final classes = await _repo.fetchClasses();
      // Do not use attendance-meta as a fallback. Use the teacher meta
      // (DB-backed) result returned from the repository.
      setState(() {
        _classes = classes;
        _groups = [];
        _genderOptions = [];
      });
    } catch (_) {}
  }

  // Removed attendance-meta fallback. We rely on the repository's
  // `fetchClasses()` which uses the teacher meta or principal DB endpoint.

  Future<void> _load({bool reset = false}) async {
    if (_loading) return;
    setState(() => _loading = true);
    try {
      final page = reset ? 1 : _page + 1;
      final res = await _repo.fetchStudents(
        page: page,
        search: _searchCtrl.text.trim(),
        classId: _classId,
        sectionId: _sectionId,
        groupId: _groupId,
        gender: _gender,
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
          _deriveFiltersFromItems();
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

  void _deriveFiltersFromItems() {
    // Only derive groups as a fallback; keep classes/sections from meta
    final groups = <String>{};
    for (final it in _items) {
      if (it is Map<String, dynamic>) {
        final g = (it['group'] ?? '').toString();
        if (g.isNotEmpty) groups.add(g);
      }
    }
    _groups = groups.map((e) => {'id': e, 'name': e}).toList()
      ..sort((a, b) => (a['name'] as String).compareTo(b['name'] as String));
  }

  void _onSearch() => _load(reset: true);

  Future<void> _call(String phone) async {
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  String _capitalize(String s) {
    if (s.isEmpty) return s;
    return s[0].toUpperCase() + s.substring(1);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Students')),
      body: Column(
        children: [
          // Row 1: Class + Section
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 8, 8, 0),
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _classId,
                    decoration: const InputDecoration(
                      labelText: 'Class',
                      isDense: true,
                      border: OutlineInputBorder(),
                    ),
                    items: [
                      const DropdownMenuItem<String>(
                        value: null,
                        child: Text(''),
                      ),
                      ..._classes.map(
                        (c) => DropdownMenuItem<String>(
                          value: c['id']?.toString(),
                          child: Text(c['name']?.toString() ?? 'Class'),
                        ),
                      ),
                    ],
                    onChanged: (v) async {
                      setState(() {
                        _classId = v;
                        _sectionId = null;
                        _sections = [];
                        _groupId = null;
                        _groups = [];
                        _gender = null;
                        _genderOptions = [];
                      });
                      final sections = await _repo.fetchSections(classId: v);
                      final groups = v != null && v.isNotEmpty
                          ? await _repo.fetchGroupsForClass(v)
                          : <Map<String, dynamic>>[];
                      final genders = v != null && v.isNotEmpty
                          ? await _repo.fetchGendersForClass(v)
                          : <String>[];
                      if (mounted) {
                        setState(() {
                          _sections = sections;
                          _groups = groups;
                          _genderOptions = genders;
                        });
                      }
                      _load(reset: true);
                    },
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _sectionId,
                    decoration: const InputDecoration(
                      labelText: 'Section',
                      isDense: true,
                      border: OutlineInputBorder(),
                    ),
                    items: [
                      const DropdownMenuItem<String>(
                        value: null,
                        child: Text(''),
                      ),
                      ..._sections.map(
                        (s) => DropdownMenuItem<String>(
                          value: s['id']?.toString(),
                          child: Text(s['name']?.toString() ?? 'Section'),
                        ),
                      ),
                    ],
                    onChanged: (v) {
                      setState(() => _sectionId = v);
                      _load(reset: true);
                    },
                  ),
                ),
              ],
            ),
          ),
          // Row 2: Group + Gender (dropdowns, default blank)
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 8, 8, 0),
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _groupId,
                    decoration: const InputDecoration(
                      labelText: 'Group',
                      isDense: true,
                      border: OutlineInputBorder(),
                    ),
                    items: [
                      const DropdownMenuItem<String>(
                        value: null,
                        child: Text(''),
                      ),
                      ..._groups.map(
                        (g) => DropdownMenuItem<String>(
                          value: g['id']?.toString(),
                          child: Text(g['name']?.toString() ?? 'Group'),
                        ),
                      ),
                    ],
                    onChanged: _groups.isEmpty
                        ? null
                        : (v) {
                            setState(() => _groupId = v);
                            _load(reset: true);
                          },
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _gender,
                    decoration: const InputDecoration(
                      labelText: 'Gender',
                      isDense: true,
                      border: OutlineInputBorder(),
                    ),
                    items: [
                      const DropdownMenuItem<String>(
                        value: null,
                        child: Text(''),
                      ),
                      ..._genderOptions.map(
                        (g) => DropdownMenuItem<String>(
                          value: g,
                          child: Text(_capitalize(g)),
                        ),
                      ),
                    ],
                    onChanged: (v) {
                      setState(() => _gender = v);
                      _load(reset: true);
                    },
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchCtrl,
                    decoration: InputDecoration(
                      hintText: 'Search name/roll/id/phone',
                      prefixIcon: const Icon(Icons.search),
                      border: const OutlineInputBorder(),
                      isDense: true,
                    ),
                    onSubmitted: (_) => _onSearch(),
                  ),
                ),
                const SizedBox(width: 8),
                SizedBox(
                  height: 48,
                  child: ElevatedButton(
                    onPressed: _onSearch,
                    style: ElevatedButton.styleFrom(
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(4),
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                    ),
                    child: const Text('Search'),
                  ),
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
                : ListView.separated(
                    itemCount: _items.length + (_hasMore ? 1 : 0),
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      if (index >= _items.length) {
                        // Load more trigger
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
        ],
      ),
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
        if (mounted)
          setState(() {
            _data = normalized;
            _error = null;
          });
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
    bool _hasVal(dynamic v) => v != null && v.toString().trim().isNotEmpty;

    String _stringify(dynamic v) {
      if (v == null) return '';
      if (v is String) return v.trim();
      if (v is num || v is bool) return v.toString();
      if (v is Map) {
        final m = v.cast<String, dynamic>();
        for (final k in ['name', 'title', 'code', 'label', 'value', 'en']) {
          final vv = m[k];
          if (_hasVal(vv)) return vv.toString();
        }
      }
      return v.toString();
    }

    dynamic _searchKey(dynamic node, String key) {
      if (node is Map<String, dynamic>) {
        if (_hasVal(node[key])) return node[key];
        for (final nest in const [
          'student',
          'profile',
          'guardian',
          'parents',
          'academic',
          'contact',
          'addresses',
          'class',
          'section',
          'data',
          'present',
          'permanent',
        ]) {
          final nm = node[nest];
          if (nm is Map<String, dynamic>) {
            final v2 = _searchKey(nm, key);
            if (_hasVal(v2)) return v2;
          }
        }
        for (final e in node.values) {
          if (e is Map<String, dynamic> || e is List) {
            final v3 = _searchKey(e, key);
            if (_hasVal(v3)) return v3;
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
      final v = _searchKey(src, k);
      if (_hasVal(v)) return _stringify(v);
    }
    return '';
  }

  Widget _infoTile(String label, String value, IconData icon) {
    final show = value.trim().isNotEmpty ? value : 'N/A';
    return ListTile(
      leading: Icon(icon),
      title: Text(label),
      subtitle: Text(show),
      dense: true,
      contentPadding: const EdgeInsets.symmetric(horizontal: 12.0),
    );
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
      _tc(_pick(d, const ['name', 'student_name', 'full_name'])),
      _pick(d, const ['name_bn', 'student_name_bn', 'full_name_bn', 'bn_name']),
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

    final gender = _tc(_pick(d, const ['gender', 'gender_name']));
    final bloodGroup = _tc(_pick(d, const ['blood_group']));
    final dob = _pick(d, const ['date_of_birth', 'dob']);
    final religion = _tc(_pick(d, const ['religion', 'religion_name']));

    final phone = _pick(d, const [
      'phone',
      'mobile',
      'contact_no',
      'contact_number',
      'phone_number',
    ]);
    final email = _pick(d, const ['email']);
    final presentAddressComposed = _composeAddress(d, 'present');
    final presentAddressFallback = _combine(
      _pick(d, const [
        'present_address',
        'current_address',
        'address',
        'addresses_present',
        'present',
      ]),
      _pick(d, const [
        'present_address_bn',
        'current_address_bn',
        'address_bn',
        'addresses_present_bn',
      ]),
      joinWithNewline: true,
    );
    final presentAddress = presentAddressComposed.isNotEmpty
        ? presentAddressComposed
        : presentAddressFallback;

    final permanentAddressComposed = _composeAddress(d, 'permanent');
    final permanentAddressFallback = _combine(
      _pick(d, const ['permanent_address', 'addresses_permanent', 'permanent']),
      _pick(d, const ['permanent_address_bn', 'addresses_permanent_bn']),
      joinWithNewline: true,
    );
    final permanentAddress = permanentAddressComposed.isNotEmpty
        ? permanentAddressComposed
        : permanentAddressFallback;

    final fatherName = _combine(
      _tc(_pick(d, const ['father_name', 'father', 'father_name_en'])),
      _pick(d, const ['father_name_bn']),
    );
    final motherName = _combine(
      _tc(_pick(d, const ['mother_name', 'mother', 'mother_name_en'])),
      _pick(d, const ['mother_name_bn']),
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
      'guardian_mobile',
      'local_guardian_mobile',
      'guardian_contact',
      'guardian_phone_number',
      'guardian_contact_no',
    ]);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Student Profile'),
        actions: [
          if (_data != null)
            IconButton(
              tooltip: 'Raw',
              icon: const Icon(Icons.data_object),
              onPressed: () {
                final pretty = const JsonEncoder.withIndent(
                  '  ',
                ).convert(_data);
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
      ),
      body: _loading
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
                      onPressed: _load,
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              ),
            )
          : SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Stylish gradient header with avatar and chips
                  _buildHeader(
                    context,
                    photoUrl,
                    name,
                    cls,
                    section,
                    roll,
                    studentCode,
                    gender,
                    group,
                  ),

                  const SizedBox(height: 12),

                  // Quick actions row
                  _buildQuickActions(
                    context,
                    phone: guardianPhone,
                    email: email,
                  ),

                  const SizedBox(height: 12),

                  // Info grid pills (colorful mini-cards)
                  _buildInfoPills(context, {
                    'Student ID': studentCode,
                    'Class': cls,
                    'Section': section,
                    'Roll': roll,
                    'Group': group,
                    'Shift': shift,
                    'Medium': medium,
                    'Session': session,
                    'Year': year,
                  }),

                  const SizedBox(height: 12),

                  // Personal Information - colorful card
                  _sectionCard(
                    title: 'Personal Information',
                    gradient: const LinearGradient(
                      colors: [Color(0xFFff9966), Color(0xFFff5e62)],
                    ),
                    children: [
                      _infoRow(
                        icon: Icons.transgender,
                        label: 'Gender',
                        value: gender,
                      ),
                      _infoRow(
                        icon: Icons.bloodtype,
                        label: 'Blood Group',
                        value: bloodGroup,
                      ),
                      _infoRow(
                        icon: Icons.cake,
                        label: 'Date of Birth',
                        value: dob,
                      ),
                      _infoRow(
                        icon: Icons.self_improvement,
                        label: 'Religion',
                        value: religion,
                      ),
                    ],
                  ),

                  const SizedBox(height: 12),

                  // Guardian Information - mini cards within
                  _sectionCard(
                    title: 'Guardian Information',
                    gradient: const LinearGradient(
                      colors: [Color(0xFF56ab2f), Color(0xFFa8e063)],
                    ),
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: _miniCard(
                              context,
                              icon: Icons.man,
                              title: 'Father',
                              subtitle: fatherName,
                              color: const Color(0xFF81c784),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _miniCard(
                              context,
                              icon: Icons.woman,
                              title: 'Mother',
                              subtitle: motherName,
                              color: const Color(0xFF64b5f6),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      _infoRow(
                        icon: Icons.family_restroom,
                        label: 'Guardian',
                        value: guardianName,
                      ),
                      _infoRow(
                        icon: Icons.call,
                        label: 'Father Phone',
                        value: fatherPhone,
                        onTap: fatherPhone.trim().isEmpty
                            ? null
                            : () async {
                                final uri = Uri(
                                  scheme: 'tel',
                                  path: fatherPhone,
                                );
                                if (await canLaunchUrl(uri)) {
                                  await launchUrl(uri);
                                }
                              },
                      ),
                      _infoRow(
                        icon: Icons.call,
                        label: 'Mother Phone',
                        value: motherPhone,
                        onTap: motherPhone.trim().isEmpty
                            ? null
                            : () async {
                                final uri = Uri(
                                  scheme: 'tel',
                                  path: motherPhone,
                                );
                                if (await canLaunchUrl(uri)) {
                                  await launchUrl(uri);
                                }
                              },
                      ),
                      _infoRow(
                        icon: Icons.call,
                        label: 'Guardian Phone',
                        value: guardianPhone,
                        onTap: guardianPhone.trim().isEmpty
                            ? null
                            : () async {
                                final uri = Uri(
                                  scheme: 'tel',
                                  path: guardianPhone,
                                );
                                if (await canLaunchUrl(uri)) {
                                  await launchUrl(uri);
                                }
                              },
                      ),
                      _infoRow(
                        icon: Icons.group,
                        label: 'Relation',
                        value: guardianRelation,
                      ),
                    ],
                  ),

                  const SizedBox(height: 12),

                  // Contact Information
                  _sectionCard(
                    title: 'Contact Information',
                    gradient: const LinearGradient(
                      colors: [Color(0xFF36d1dc), Color(0xFF5b86e5)],
                    ),
                    children: [
                      _infoRow(
                        icon: Icons.call,
                        label: 'Phone',
                        value: guardianPhone,
                        onTap: guardianPhone.trim().isEmpty
                            ? null
                            : () async {
                                final uri = Uri(
                                  scheme: 'tel',
                                  path: guardianPhone,
                                );
                                if (await canLaunchUrl(uri)) {
                                  await launchUrl(uri);
                                }
                              },
                      ),
                      _infoRow(icon: Icons.email, label: 'Email', value: email),
                      _infoRow(
                        icon: Icons.place,
                        label: 'Present Address',
                        value: presentAddress,
                      ),
                      _infoRow(
                        icon: Icons.home,
                        label: 'Permanent Address',
                        value: permanentAddress,
                      ),
                    ],
                  ),

                  const SizedBox(height: 12),

                  // Admission & Previous
                  _sectionCard(
                    title: 'Admission & Previous',
                    gradient: const LinearGradient(
                      colors: [Color(0xFFf7971e), Color(0xFFffd200)],
                    ),
                    children: [
                      _infoRow(
                        icon: Icons.numbers,
                        label: 'Student ID',
                        value: studentCode,
                      ),
                      _infoRow(
                        icon: Icons.apartment,
                        label: 'Previous School',
                        value: _tc(_pick(d, const ['previous_school'])),
                      ),
                      _infoRow(
                        icon: Icons.event_available,
                        label: 'Pass Year',
                        value: _pick(d, const ['pass_year']),
                      ),
                      _infoRow(
                        icon: Icons.analytics,
                        label: 'Previous Result',
                        value: _pick(d, const ['previous_result']),
                      ),
                      _infoRow(
                        icon: Icons.notes,
                        label: 'Previous Remarks',
                        value: _pick(d, const ['previous_remarks']),
                      ),
                      _infoRow(
                        icon: Icons.date_range,
                        label: 'Admission Date',
                        value: _pick(d, const ['admission_date']),
                      ),
                      _infoRow(
                        icon: Icons.verified_user,
                        label: 'Status',
                        value: _tc(_pick(d, const ['status'])),
                      ),
                    ],
                  ),

                  const SizedBox(height: 24),
                ],
              ),
            ),
    );
  }

  Widget _buildHeader(
    BuildContext context,
    String photoUrl,
    String name,
    String cls,
    String section,
    String roll,
    String studentCode,
    String gender,
    String group,
  ) {
    final theme = Theme.of(context);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 32, 16, 16),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF8E2DE2), Color(0xFF4A00E0)],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
      ),
      child: Column(
        children: [
          CircleAvatar(
            radius: 48,
            backgroundImage: photoUrl.isNotEmpty
                ? CachedNetworkImageProvider(photoUrl)
                : null,
            child: photoUrl.isEmpty
                ? const Icon(Icons.person, size: 48, color: Colors.white)
                : null,
            backgroundColor: Colors.white.withOpacity(0.2),
          ),
          const SizedBox(height: 12),
          Text(
            name.isNotEmpty ? name : 'Student',
            style: theme.textTheme.titleLarge?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            [
              if (cls.isNotEmpty || section.isNotEmpty) '$cls - $section',
              if (roll.isNotEmpty) 'Roll: $roll',
            ].where((e) => e.isNotEmpty).join('  •  '),
            style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white70),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            alignment: WrapAlignment.center,
            children: [
              if (studentCode.isNotEmpty)
                _chip(
                  icon: Icons.badge,
                  label: studentCode,
                  color: const Color(0xFFffd54f),
                ),
              if (group.isNotEmpty)
                _chip(
                  icon: Icons.group_work,
                  label: group,
                  color: const Color(0xFF80cbc4),
                ),
              if (gender.isNotEmpty)
                _chip(
                  icon: Icons.transgender,
                  label: gender,
                  color: const Color(0xFFf48fb1),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _chip({
    required IconData icon,
    required String label,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.18),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white24),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: Colors.white),
          const SizedBox(width: 6),
          Text(label, style: const TextStyle(color: Colors.white)),
        ],
      ),
    );
  }

  Widget _buildQuickActions(
    BuildContext context, {
    required String phone,
    required String email,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          Expanded(
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF00c853),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              onPressed: phone.isEmpty
                  ? null
                  : () async {
                      final uri = Uri(scheme: 'tel', path: phone);
                      if (await canLaunchUrl(uri)) {
                        await launchUrl(uri);
                      }
                    },
              icon: const Icon(Icons.call),
              label: Text(phone.isNotEmpty ? 'Call $phone' : 'No phone'),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF2962ff),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              onPressed: email.isEmpty
                  ? null
                  : () async {
                      final uri = Uri(scheme: 'mailto', path: email);
                      if (await canLaunchUrl(uri)) {
                        await launchUrl(uri);
                      }
                    },
              icon: const Icon(Icons.email),
              label: Text(email.isNotEmpty ? 'Email $email' : 'No email'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoPills(BuildContext context, Map<String, String> data) {
    final colors = [
      const Color(0xFF7F00FF),
      const Color(0xFF00BFA6),
      const Color(0xFFFF6F00),
      const Color(0xFFAA00FF),
      const Color(0xFF2962FF),
      const Color(0xFFD50000),
      const Color(0xFF00C853),
      const Color(0xFFFF4081),
      const Color(0xFF5D4037),
    ];
    int i = 0;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Wrap(
        spacing: 10,
        runSpacing: 10,
        children: data.entries.map((e) {
          final value = e.value.trim();
          if (value.isEmpty) return const SizedBox.shrink();
          final color = colors[i++ % colors.length];
          return Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [color.withOpacity(0.85), color.withOpacity(0.65)],
              ),
              borderRadius: BorderRadius.circular(14),
              boxShadow: [
                BoxShadow(
                  color: color.withOpacity(0.25),
                  blurRadius: 12,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  e.key,
                  style: const TextStyle(
                    color: Colors.white70,
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _miniCard(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 18,
            backgroundColor: color.withOpacity(0.25),
            child: Icon(icon, color: _darken(color, 0.1), size: 18),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    color: _darken(color, 0.1),
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle.isNotEmpty ? subtitle : 'N/A',
                  style: TextStyle(color: _darken(color, 0.2)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // Local color helper to avoid extension usage
  Color _darken(Color c, double amount) {
    assert(amount >= 0 && amount <= 1);
    final f = 1 - amount;
    return Color.fromARGB(
      c.alpha,
      (c.red * f).round(),
      (c.green * f).round(),
      (c.blue * f).round(),
    );
  }

  Widget _sectionCard({
    required String title,
    required LinearGradient gradient,
    required List<Widget> children,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: const [
            BoxShadow(
              color: Color(0x11000000),
              blurRadius: 12,
              offset: Offset(0, 6),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(16),
                  topRight: Radius.circular(16),
                ),
                gradient: gradient,
              ),
              child: Text(
                title,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            const SizedBox(height: 6),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
              child: Column(children: children),
            ),
          ],
        ),
      ),
    );
  }

  Widget _infoRow({
    required IconData icon,
    required String label,
    required String value,
    VoidCallback? onTap,
  }) {
    final display = value.trim().isNotEmpty ? value : 'N/A';
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: InkWell(
        onTap: onTap,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.only(top: 2),
              child: Icon(
                icon,
                color: onTap != null ? Colors.blue : Colors.black54,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    display,
                    style: TextStyle(
                      color: onTap != null ? Colors.blue : Colors.black87,
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

  // Compose address in the format: Village, Post Office, Upazila, District
  String _composeAddress(Map<String, dynamic> src, String scope) {
    String pv = _tc(
      _pick(src, [
        '${scope}_village',
        'village_${scope}',
        'addresses_${scope}_village',
        '${scope}_address_village',
        'village',
        'gram',
        '${scope}_para_moholla',
        'para_moholla_${scope}',
        'para_moholla',
      ]),
    );
    String pvBn = _pick(src, [
      '${scope}_village_bn',
      'village_${scope}_bn',
      'addresses_${scope}_village_bn',
      '${scope}_address_village_bn',
      'village_bn',
      'gram_bn',
    ]);
    final village = _combine(pv, pvBn);

    String po = _tc(
      _pick(src, [
        '${scope}_post_office',
        'post_office_${scope}',
        'addresses_${scope}_post_office',
        '${scope}_post',
        'post_${scope}',
        '${scope}_po',
        'dakghor',
        'post_office',
        'post',
        'po',
      ]),
    );
    String poBn = _pick(src, [
      '${scope}_post_office_bn',
      'post_office_${scope}_bn',
      'addresses_${scope}_post_office_bn',
      '${scope}_post_bn',
      'post_${scope}_bn',
      '${scope}_po_bn',
      'dakghor_bn',
      'post_office_bn',
      'post_bn',
      'po_bn',
    ]);
    final post = _combine(po, poBn);

    String upa = _tc(
      _pick(src, [
        '${scope}_upazila',
        'upazila_${scope}',
        'addresses_${scope}_upazila',
        '${scope}_thana',
        'thana_${scope}',
        '${scope}_subdistrict',
        'subdistrict_${scope}',
        '${scope}_sub_district',
        '${scope}_upazilla',
        'upazilla_${scope}',
        'upazila',
        'thana',
        'subdistrict',
        'sub_district',
        'upazilla',
      ]),
    );
    String upaBn = _pick(src, [
      '${scope}_upazila_bn',
      'upazila_${scope}_bn',
      'addresses_${scope}_upazila_bn',
      '${scope}_thana_bn',
      'thana_${scope}_bn',
      '${scope}_subdistrict_bn',
      'subdistrict_${scope}_bn',
      '${scope}_sub_district_bn',
      'upazila_bn',
      'thana_bn',
      'subdistrict_bn',
      'sub_district_bn',
    ]);
    final upazila = _combine(upa, upaBn);

    String dist = _tc(
      _pick(src, [
        '${scope}_district',
        'district_${scope}',
        'addresses_${scope}_district',
        '${scope}_zilla',
        'zilla_${scope}',
        'district',
        'zilla',
      ]),
    );
    String distBn = _pick(src, [
      '${scope}_district_bn',
      'district_${scope}_bn',
      'addresses_${scope}_district_bn',
      '${scope}_zilla_bn',
      'zilla_${scope}_bn',
      'district_bn',
      'zilla_bn',
    ]);
    final district = _combine(dist, distBn);

    final parts = [
      village,
      post,
      upazila,
      district,
    ].where((e) => e.trim().isNotEmpty).toList();
    return parts.join(', ');
  }
}
