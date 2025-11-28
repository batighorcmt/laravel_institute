import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../data/teacher/teacher_directory_repository.dart';
import '../../../core/network/dio_client.dart';

class TeacherDirectoryPage extends StatefulWidget {
  const TeacherDirectoryPage({super.key});

  @override
  State<TeacherDirectoryPage> createState() => _TeacherDirectoryPageState();
}

class _TeacherDirectoryPageState extends State<TeacherDirectoryPage> {
  final _repo = TeacherDirectoryRepository();
  final _searchCtl = TextEditingController();
  String? _designation;
  int _currentPage = 1;
  List<Map<String, dynamic>> _items = [];
  List<String> _designations = [];
  bool _loading = false;
  bool _hasMore = true;

  @override
  void initState() {
    super.initState();
    _loadPage(reset: true);
  }

  @override
  void dispose() {
    _searchCtl.dispose();
    super.dispose();
  }

  Future<void> _loadPage({bool reset = false}) async {
    if (_loading) return;
    setState(() => _loading = true);
    if (reset) {
      _currentPage = 1;
      _items = [];
      _hasMore = true;
    }
    try {
      final data = await _repo.fetchTeachersPage(
        page: _currentPage,
        search: _searchCtl.text.trim().isEmpty ? null : _searchCtl.text.trim(),
        designation: _designation,
      );
      final items = (data['items'] as List<Map<String, dynamic>>);
      final meta = data['meta'] as Map<String, dynamic>;
      _designations = (data['designations'] as List<String>);
      final lastPage = (meta['last_page'] is int)
          ? meta['last_page'] as int
          : _currentPage;
      setState(() {
        _items.addAll(items);
        _hasMore = _currentPage < lastPage;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Load error: $e')));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _refresh() async {
    _searchCtl.clear();
    _designation = null;
    await _loadPage(reset: true);
  }

  void _doSearch() {
    _loadPage(reset: true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Teachers')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchCtl,
                    decoration: InputDecoration(
                      hintText: 'Search name or phone',
                      prefixIcon: const Icon(Icons.search),
                      isDense: true,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    textInputAction: TextInputAction.search,
                    onSubmitted: (_) => _loadPage(reset: true),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  tooltip: 'Clear',
                  onPressed: () {
                    _searchCtl.clear();
                    _loadPage(reset: true);
                  },
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
          ),
          if (_designations.isNotEmpty)
            SizedBox(
              height: 48,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 12),
                children: [
                  _DesignationChip(
                    label: 'All',
                    selected: _designation == null,
                    onTap: () {
                      _designation = null;
                      _loadPage(reset: true);
                    },
                  ),
                  for (final d in _designations)
                    _DesignationChip(
                      label: d,
                      selected: _designation == d,
                      onTap: () {
                        _designation = d;
                        _loadPage(reset: true);
                      },
                    ),
                ],
              ),
            ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _refresh,
              child: ListView.separated(
                physics: const AlwaysScrollableScrollPhysics(),
                itemCount: _items.length + 1,
                separatorBuilder: (_, __) => const Divider(height: 0),
                itemBuilder: (context, index) {
                  if (index == _items.length) {
                    if (!_hasMore) {
                      return Padding(
                        padding: const EdgeInsets.all(16),
                        child: Center(child: Text('Total: ${_items.length}')),
                      );
                    }
                    return Padding(
                      padding: const EdgeInsets.all(16),
                      child: _loading
                          ? const Center(child: CircularProgressIndicator())
                          : ElevatedButton.icon(
                              onPressed: () {
                                _currentPage += 1;
                                _loadPage();
                              },
                              icon: const Icon(Icons.expand_more),
                              label: const Text('Load More'),
                            ),
                    );
                  }
                  final t = _items[index];
                  final name = (t['name'] ?? '').toString();
                  final desig = (t['designation'] ?? '').toString();
                  final phone = (t['phone'] ?? '').toString();
                  final photoUrl = (t['photo_url'] ?? '').toString();
                  return ListTile(
                    leading: _TeacherAvatar(name: name, photoUrl: photoUrl),
                    title: Text(name),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (desig.isNotEmpty) Text(desig),
                        if (phone.isNotEmpty)
                          Row(
                            children: [
                              Flexible(child: Text(phone)),
                              IconButton(
                                tooltip: 'Call',
                                icon: const Icon(
                                  Icons.call,
                                  color: Colors.green,
                                ),
                                onPressed: () => _callNumber(phone),
                              ),
                            ],
                          ),
                      ],
                    ),
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _callNumber(String phone) async {
    if (phone.isEmpty) return;
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Cannot call $phone')));
    }
  }
}

class _TeacherAvatar extends StatelessWidget {
  final String name;
  final String photoUrl;
  const _TeacherAvatar({required this.name, required this.photoUrl});

  @override
  Widget build(BuildContext context) {
    final imageUrl = (photoUrl.isNotEmpty) ? photoUrl : null;
    if (imageUrl == null) {
      return CircleAvatar(
        radius: 24,
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : '?',
          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
        ),
      );
    }
    return CircleAvatar(
      radius: 24,
      backgroundColor: Colors.grey[200],
      child: ClipOval(
        child: CachedNetworkImage(
          imageUrl: imageUrl,
          width: 48,
          height: 48,
          fit: BoxFit.cover,
          placeholder: (context, url) => const Center(
            child: SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          ),
          errorWidget: (context, url, error) => CircleAvatar(
            radius: 24,
            child: Text(
              name.isNotEmpty ? name[0].toUpperCase() : '?',
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
          ),
        ),
      ),
    );
  }
}

class _DesignationChip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onTap;
  const _DesignationChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => onTap(),
      ),
    );
  }
}
