import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:dio/dio.dart';
import '../../../data/teacher/teacher_students_repository.dart';

class TeacherStudentsListPage extends StatefulWidget {
  const TeacherStudentsListPage({super.key});

  @override
  State<TeacherStudentsListPage> createState() =>
      _TeacherStudentsListPageState();
}

class _TeacherStudentsListPageState extends State<TeacherStudentsListPage> {
  final _searchCtrl = TextEditingController();
  final _dio = Dio();
  late final TeacherStudentsRepository _repo;

  int _page = 1;
  bool _loading = false;
  bool _hasMore = true;
  List<dynamic> _items = [];

  String? _classId;
  String? _sectionId;
  String? _groupId;
  String? _gender;

  @override
  void initState() {
    super.initState();
    _repo = TeacherStudentsRepository(_dio);
    _load(reset: true);
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
        _hasMore = (meta['has_more'] as bool?) ?? false;
        if (reset) {
          _items = data;
        } else {
          _items.addAll(data);
        }
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _onSearch() => _load(reset: true);

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
                ElevatedButton(
                  onPressed: _onSearch,
                  child: const Text('Search'),
                ),
              ],
            ),
          ),
          // Simple filter row (placeholders for now)
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            child: Row(
              children: [
                FilterChip(
                  label: const Text('Male'),
                  selected: _gender == 'male',
                  onSelected: (v) {
                    setState(() => _gender = v ? 'male' : null);
                    _load(reset: true);
                  },
                ),
                const SizedBox(width: 8),
                FilterChip(
                  label: const Text('Female'),
                  selected: _gender == 'female',
                  onSelected: (v) {
                    setState(() => _gender = v ? 'female' : null);
                    _load(reset: true);
                  },
                ),
              ],
            ),
          ),
          Expanded(
            child: ListView.separated(
              itemCount: _items.length + (_hasMore ? 1 : 0),
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, index) {
                if (index >= _items.length) {
                  // Load more trigger
                  _load();
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
                    backgroundImage: photoUrl != null && photoUrl.isNotEmpty
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
                    onPressed: phone.isNotEmpty ? () => _call(phone) : null,
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
  final _dio = Dio();
  late final TeacherStudentsRepository _repo;
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _repo = TeacherStudentsRepository(_dio);
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await _repo.fetchStudentProfile(widget.studentId);
      setState(() => _data = res);
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
