import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:dio/dio.dart';
import '../../../core/network/dio_client.dart';
import '../../../data/teacher/teacher_students_repository.dart';

class PrincipalStudentProfilePage extends StatefulWidget {
  final String studentId;

  const PrincipalStudentProfilePage({
    super.key,
    required this.studentId,
  });

  @override
  State<PrincipalStudentProfilePage> createState() => _PrincipalStudentProfilePageState();
}


class _PrincipalStudentProfilePageState extends State<PrincipalStudentProfilePage> {
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
    // Initial load? Maybe wait for class selection or load all if API supports it.
    // Usually principal wants to see something, but without filters it might be huge.
    // Let's load with no filters initially (pagination handles it).
    _load(reset: true);
  }

  Future<void> _preloadFilters() async {
    try {
      final classes = await _repo.fetchClasses();
      if (mounted) {
        setState(() {
          _classes = classes;
        });
      }
    } catch (_) {}
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading && !reset) return;
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
      final meta = res['meta'] as Map? ?? {};

      // Pagination logic (reused from teacher list)
      int current = (meta['current_page'] as int?) ?? (meta['page'] as int?) ?? page;
      int? last = (meta['last_page'] as int?) ?? (meta['lastPage'] as int?);
      bool nextPage = false;
      if (last != null) {
        nextPage = current < last;
      } else {
         final per = (meta['per_page'] as int?) ?? 15;
         nextPage = data.length >= per;
      }

      if (mounted) {
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
      }
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _onSearch() => _load(reset: true);

  Future<void> _call(String phone) async {
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri)) await launchUrl(uri);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Students List')),
      body: Column(
        children: [
          // Filters
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _classId,
                    isExpanded: true,
                    decoration: const InputDecoration(labelText: 'Class', border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 0)),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('All')),
                      ..._classes.map((c) => DropdownMenuItem(value: c['id']?.toString(), child: Text(c['name']?.toString() ?? ''))),
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
                      _load(reset: true);
                      if (v != null) {
                        // Fetch dependent filters
                        final secs = await _repo.fetchSections(classId: v);
                        final grps = await _repo.fetchGroupsForClass(v);
                        final gens = await _repo.fetchGendersForClass(v);
                        if (mounted) {
                          setState(() {
                            _sections = secs;
                            _groups = grps;
                            _genderOptions = gens;
                          });
                        }
                      }
                    },
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _sectionId,
                    isExpanded: true,
                    decoration: const InputDecoration(labelText: 'Section', border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 0)),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('All')),
                      ..._sections.map((s) => DropdownMenuItem(value: s['id']?.toString(), child: Text(s['name']?.toString() ?? ''))),
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
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _groupId,
                    isExpanded: true,
                    decoration: const InputDecoration(labelText: 'Group', border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 0)),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('All')),
                       ..._groups.map((g) => DropdownMenuItem(value: g['id']?.toString(), child: Text(g['name']?.toString() ?? ''))),
                    ],
                    onChanged: (v) {
                      setState(() => _groupId = v);
                      _load(reset: true);
                    },
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _gender,
                    isExpanded: true,
                    decoration: const InputDecoration(labelText: 'Gender', border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 0)),
                    items: [
                       const DropdownMenuItem(value: null, child: Text('All')),
                       ..._genderOptions.map((g) => DropdownMenuItem(value: g, child: Text(g))),
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
          // Search
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: TextField(
              controller: _searchCtrl,
              decoration: InputDecoration(
                hintText: 'Search...',
                suffixIcon: IconButton(icon: const Icon(Icons.search), onPressed: _onSearch),
                border: const OutlineInputBorder(),
              ),
              onSubmitted: (_) => _onSearch(),
            ),
          ),
          // List
          Expanded(
            child: _loading && _items.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : _items.isEmpty
                    ? const Center(child: Text('No students found'))
                    : ListView.separated(
                        itemCount: _items.length + (_hasMore ? 1 : 0),
                        separatorBuilder: (_, __) => const Divider(height: 1),
                        itemBuilder: (ctx, i) {
                          if (i >= _items.length) {
                            if (!_loading) _load();
                            return const Center(child: Padding(padding: EdgeInsets.all(8), child: CircularProgressIndicator()));
                          }
                          final item = _items[i] as Map<String, dynamic>;
                          return ListTile(
                            leading: CircleAvatar(
                              backgroundImage: item['photo_url'] != null ? NetworkImage(item['photo_url']) : null,
                              child: item['photo_url'] == null ? const Icon(Icons.person) : null,
                            ),
                            title: Text(item['name'] ?? ''),
                            subtitle: Text('Roll: ${item['roll'] ?? '-'} • Class: ${item['class'] ?? ''}'),
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () {
                              Navigator.push(context, MaterialPageRoute(builder: (_) => PrincipalStudentDetailView(studentId: item['id'].toString(), initialData: item)));
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

class PrincipalStudentDetailView extends StatefulWidget {
  final String studentId;
  final Map<String, dynamic>? initialData;
  const PrincipalStudentDetailView({super.key, required this.studentId, this.initialData});

  @override
  State<PrincipalStudentDetailView> createState() => _PrincipalStudentDetailViewState();
}

class _PrincipalStudentDetailViewState extends State<PrincipalStudentDetailView> {
  late final Dio _dio;
  late final TeacherStudentsRepository _repo;
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _repo = TeacherStudentsRepository(_dio);
    _data = widget.initialData;
    _fetchFullProfile();
  }

  Future<void> _fetchFullProfile() async {
    try {
      final res = await _repo.fetchStudentProfile(widget.studentId);
      final raw = res['data'];
       if (mounted) {
        setState(() {
          _data = raw is Map<String, dynamic> ? raw : res;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _loading = false);
    }
  }

  // Method to recursively search for keys
  dynamic _findValue(dynamic data, List<String> keys) {
    if (data == null) return null;
    if (data is Map) {
      for (final key in keys) {
        if (data.containsKey(key) && data[key] != null && data[key].toString().trim().isNotEmpty) {
          return data[key];
        }
      }
      for (final v in data.values) {
        if (v is Map || v is List) {
           final found = _findValue(v, keys);
           if (found != null) return found;
        }
      }
    } else if (data is List) {
      for (final item in data) {
        final found = _findValue(item, keys);
        if (found != null) return found;
      }
    }
    return null;
  }

  String _val(List<String> keys) {
      final v = _findValue(_data, keys);
      return v?.toString().trim() ?? '';
  }

  String _composeAddress(String type) {
    // Try to find address components or full address
    final full = _val(['${type}_address', 'address', '${type}_addr']);
    if (full.isNotEmpty) return full;

    // Components
    final street = _val(['${type}_street', 'street']);
    final post = _val(['${type}_post', 'post_office']);
    final upazila = _val(['${type}_upazila', 'upazila']);
    final district = _val(['${type}_district', 'district']);

    final parts = [street, post, upazila, district].where((e) => e.isNotEmpty).toList();
    if (parts.isNotEmpty) return parts.join(', ');

    return '';
  }

  @override
  Widget build(BuildContext context) {
    final d = _data ?? {};
    // Extract everything
    final name = _val(['name', 'full_name', 'student_name']);
    final photo = _val(['photo_url', 'photo', 'image']);
    final roll = _val(['roll', 'class_roll']);
    final studentId = _val(['student_id', 'admission_no', 'code', 'student_code']);

    final className = _val(['class', 'class_name']);
    final section = _val(['section', 'section_name']);
    final group = _val(['group', 'group_name']);
    final shift = _val(['shift']);
    final medium = _val(['medium']);
    final session = _val(['session', 'academic_session']);
    final year = _val(['academic_year', 'year']);

    final gender = _val(['gender']);
    final blood = _val(['blood_group']);
    final dob = _val(['dob', 'date_of_birth']);
    final religion = _val(['religion']);

    final father = _val(['father_name', 'father']);
    final mother = _val(['mother_name', 'mother']);
    final guardian = _val(['guardian_name', 'guardian']);
    final guardianRel = _val(['guardian_relation']);
    final guardianPhone = _val(['guardian_phone', 'guardian_mobile']);

    final phone = _val(['phone', 'mobile']);
    final email = _val(['email']);

    final presentAddr = _composeAddress('present');
    final permAddr = _composeAddress('permanent');

    return Scaffold(
      appBar: AppBar(title: const Text('Student Profile')),
      body: _loading && d.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
         padding: const EdgeInsets.all(16),
         child: Column(
           children: [
             // Header
             Hero(
               tag: 'avatar_${widget.studentId}',
               child: CircleAvatar(
                 radius: 50,
                 backgroundImage: photo.isNotEmpty ? NetworkImage(photo) : null,
                 child: photo.isEmpty ? const Icon(Icons.person, size: 50) : null,
               ),
             ),
             const SizedBox(height: 10),
             Text(name, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold), textAlign: TextAlign.center),
             const SizedBox(height: 4),
             Text('ID: $studentId', style: const TextStyle(color: Colors.grey)),
              const SizedBox(height: 4),
             Text('$className - $section (Roll: $roll)', style: const TextStyle(fontSize: 16)),
             const SizedBox(height: 20),

             _section('Academic Information', [
               _row('Class', className),
               _row('Section', section),
               _row('Roll', roll),
               _row('Group', group),
               _row('Shift', shift),
               _row('Medium', medium),
               _row('Session', session),
               _row('Year', year),
             ]),

             _section('Personal Information', [
               _row('Gender', gender),
               _row('Blood Group', blood),
               _row('Date of Birth', dob),
               _row('Religion', religion),
             ]),

             _section('Contact & Address', [
               _row('Phone', phone),
               _row('Email', email),
               _row('Present Address', presentAddr),
               _row('Permanent Address', permAddr),
             ]),

             _section('Family Information', [
               _row('Father', father),
               _row('Mother', mother),
               _row('Guardian', guardian),
               _row('Relation', guardianRel),
               _row('Guardian Phone', guardianPhone),
             ]),

             if (guardianPhone.isNotEmpty)
               Padding(
                 padding: const EdgeInsets.symmetric(vertical: 20),
                 child: SizedBox(
                   width: double.infinity,
                   height: 50,
                   child: ElevatedButton.icon(
                     icon: const Icon(Icons.call),
                     label: Text('Call Guardian: $guardianPhone'),
                     onPressed: () => launchUrl(Uri.parse('tel:$guardianPhone')),
                   ),
                 ),
                ),
           ],
         ),
      ),
    );
  }

  Widget _section(String title, List<Widget> children) {
    final validChildren = children.whereType<Padding>().toList();
    if (validChildren.isEmpty) return const SizedBox.shrink();

    return Card(
      elevation: 2,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.blueGrey)),
            const Divider(height: 24),
            ...validChildren,
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value) {
    if (value.isEmpty || value == 'null') return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 130, child: Text(label, style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.grey))),
          Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w500))),
        ],
      ),
    );
  }
}
