import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../data/teacher/teacher_directory_repository.dart';
import '../../../core/network/dio_client.dart';

class TeacherDirectoryPage extends StatefulWidget {
  const TeacherDirectoryPage({super.key});

  @override
  State<TeacherDirectoryPage> createState() => _TeacherDirectoryPageState();
}

class _TeacherDirectoryPageState extends State<TeacherDirectoryPage> {
  final _repo = TeacherDirectoryRepository();
  late Future<List<Map<String, dynamic>>> _future;
  final _searchCtl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _future = _repo.fetchTeachers();
  }

  @override
  void dispose() {
    _searchCtl.dispose();
    super.dispose();
  }

  void _doSearch() {
    setState(() {
      _future = _repo.fetchTeachers(search: _searchCtl.text.trim());
    });
  }

  Future<void> _refresh() async {
    _searchCtl.clear();
    _doSearch();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Teachers'),
      ),
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
                    onSubmitted: (_) => _doSearch(),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  tooltip: 'Clear',
                  onPressed: () {
                    _searchCtl.clear();
                    _doSearch();
                  },
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _refresh,
              child: FutureBuilder<List<Map<String, dynamic>>>(
                future: _future,
                builder: (context, snapshot) {
                  if (snapshot.connectionState != ConnectionState.done) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snapshot.hasError) {
                    return Center(child: Text('Error: ${snapshot.error}'));
                  }
                  final items = snapshot.data ?? [];
                  if (items.isEmpty) {
                    return const Center(child: Text('No teachers found'));
                  }
                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const Divider(height: 0),
                    itemBuilder: (context, index) {
                      final t = items[index];
                      final name = (t['name'] ?? '').toString();
                      final desig = (t['designation'] ?? '').toString();
                      final phone = (t['phone'] ?? '').toString();
                      final serial = (t['serial_number'] ?? '').toString();
                      final photo = (t['photo'] ?? '').toString();
                      return ListTile(
                        leading: _TeacherAvatar(name: name, photo: photo),
                        title: Text('$serial. $name'),
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
                                    icon: const Icon(Icons.call,color: Colors.green),
                                    onPressed: () => _callNumber(phone),
                                  ),
                                ],
                              ),
                          ],
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

  Future<void> _callNumber(String phone) async {
    if (phone.isEmpty) return;
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Cannot call $phone')),
      );
    }
  }
}

class _TeacherAvatar extends StatelessWidget {
  final String name;
  final String photo;
  const _TeacherAvatar({required this.name, required this.photo});

  @override
  Widget build(BuildContext context) {
    final base = DioClient().dio.options.baseUrl;
    final imageUrl = (photo.isNotEmpty) ? (base + photo) : null;
    return CircleAvatar(
      radius: 24,
      backgroundImage: (imageUrl != null) ? NetworkImage(imageUrl) : null,
      child: imageUrl == null
          ? Text(
              name.isNotEmpty ? name[0].toUpperCase() : '?',
              style: const TextStyle(fontSize: 20,fontWeight: FontWeight.bold),
            )
          : null,
    );
  }
}
