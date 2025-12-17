import 'package:flutter/material.dart';
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
    _load(reset: true);
  }

  Future<void> _preloadFilters() async {
    try {
      final classes = await _repo.fetchClasses();
      setState(() {
        _classes = classes;
        _groups = [];
        _genderOptions = [];
      });
    } catch (_) {}
  }

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
      setState(() {
        _page = page;
        final current = (meta['current_page'] as int?) ?? page;
        final last =
            (meta['last_page'] as int?) ?? (data.isNotEmpty ? page + 1 : page);
        _hasMore = current < last;
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
                    onChanged: _classes.isEmpty
                        ? null
                        : (v) async {
                            setState(() {
                              _classId = v;
                              _sectionId = null;
                              _sections = [];
                              _groupId = null;
                              _groups = [];
                              _gender = null;
                              _genderOptions = [];
                            });
                            final sections = await _repo.fetchSections(
                              classId: v,
                            );
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
                    onChanged: _sections.isEmpty
                        ? null
                        : (v) {
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
                              builder: (_) =>
                                  TeacherStudentProfilePage(studentId: id),
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
  const TeacherStudentProfilePage({super.key, required this.studentId});

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
    setState(() => _loading = true);
    try {
      final res = await _repo.fetchStudentProfile(widget.studentId);
      setState(() {
        _data = res;
        _error = null;
      });
    } catch (e) {
      setState(() => _error = 'Failed to load profile');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = _data ?? {};
    final photoUrl = d['photo_url'] as String?;
    final name = d['name'] as String? ?? '';
    final cls = d['class'] as String? ?? '';
    final section = d['section'] as String? ?? '';
    final roll = d['roll']?.toString() ?? '';
    final phone = d['phone'] as String? ?? '';

    return Scaffold(
      appBar: AppBar(title: const Text('Student Profile')),
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
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: CircleAvatar(
                      radius: 48,
                      backgroundImage: photoUrl != null && photoUrl.isNotEmpty
                          ? CachedNetworkImageProvider(photoUrl)
                          : null,
                      child: (photoUrl == null || photoUrl.isEmpty)
                          ? const Icon(Icons.person, size: 48)
                          : null,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Center(
                    child: Text(
                      name,
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Center(child: Text('$cls - $section  •  Roll: $roll')),
                  const SizedBox(height: 16),
                  ListTile(
                    leading: const Icon(Icons.call),
                    title: const Text('Phone'),
                    subtitle: Text(phone.isNotEmpty ? phone : 'N/A'),
                    onTap: phone.isNotEmpty
                        ? () async {
                            final uri = Uri(scheme: 'tel', path: phone);
                            if (await canLaunchUrl(uri)) {
                              await launchUrl(uri);
                            }
                          }
                        : null,
                  ),
                  const Divider(),
                  // Additional structured info sections can be added here
                ],
              ),
            ),
    );
  }
}
