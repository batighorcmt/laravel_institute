import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../data/teacher/teacher_directory_repository.dart';
import 'lesson_evaluation_theme.dart';

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
      final rawItems = (data['items'] as List?) ?? const [];
      final items = rawItems
          .map((e) => Map<String, dynamic>.from((e as Map?) ?? const {}))
          .toList();
      final meta = Map<String, dynamic>.from(
        (data['meta'] as Map?) ?? const {},
      );
      _designations = ((data['designations'] as List?) ?? const [])
          .map((e) => e.toString())
          .toList();
      final lastPageValue = meta['last_page'];
      final lastPage = (lastPageValue is int)
          ? lastPageValue
          : int.tryParse(lastPageValue?.toString() ?? '') ?? _currentPage;
      setState(() {
        _items.addAll(items);
        _hasMore = _currentPage < lastPage;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('তথ্য লোড করতে ব্যর্থ: $e')));
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LeColors.bg,
      appBar: AppBar(
        title: const Text(
          'শিক্ষকবৃন্দ',
          style: TextStyle(fontWeight: FontWeight.w700),
        ),
        centerTitle: false,
        elevation: 0,
        flexibleSpace: Container(
          decoration: const BoxDecoration(gradient: LeColors.brandGradient),
        ),
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          Container(
            decoration: const BoxDecoration(gradient: LeColors.brandGradient),
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
            child: Column(
              children: [
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Row(
                    children: [
                      const Padding(
                        padding: EdgeInsets.only(left: 12),
                        child: Icon(Icons.search, color: LeColors.muted),
                      ),
                      Expanded(
                        child: TextField(
                          controller: _searchCtl,
                          decoration: const InputDecoration(
                            hintText: 'নাম বা মোবাইল নম্বর দিয়ে খুঁজুন',
                            border: InputBorder.none,
                            contentPadding: EdgeInsets.symmetric(
                              vertical: 14,
                              horizontal: 10,
                            ),
                          ),
                          textInputAction: TextInputAction.search,
                          onSubmitted: (_) => _loadPage(reset: true),
                        ),
                      ),
                      IconButton(
                        tooltip: 'মুছুন',
                        onPressed: () {
                          _searchCtl.clear();
                          _loadPage(reset: true);
                        },
                        icon: const Icon(Icons.close, color: LeColors.muted),
                      ),
                    ],
                  ),
                ),
                if (_designations.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  SizedBox(
                    height: 38,
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      children: [
                        _DesignationChip(
                          label: 'সকল',
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
                ],
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              color: LeColors.brand,
              onRefresh: _refresh,
              child: _items.isEmpty && !_loading
                  ? ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: const [
                        SizedBox(height: 100),
                        Icon(
                          Icons.people_outline,
                          size: 56,
                          color: LeColors.muted,
                        ),
                        SizedBox(height: 12),
                        Center(
                          child: Text(
                            'কোনো শিক্ষক পাওয়া যায়নি',
                            style: TextStyle(color: LeColors.muted),
                          ),
                        ),
                      ],
                    )
                  : ListView.builder(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
                      itemCount: _items.length + (_hasMore ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index == _items.length) {
                          return Padding(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            child: Center(
                              child: _loading
                                  ? const CircularProgressIndicator(
                                      color: LeColors.brand,
                                    )
                                  : OutlinedButton.icon(
                                      style: OutlinedButton.styleFrom(
                                        foregroundColor: LeColors.brandDark,
                                        side: const BorderSide(
                                          color: LeColors.brand,
                                        ),
                                        shape: RoundedRectangleBorder(
                                          borderRadius:
                                              BorderRadius.circular(10),
                                        ),
                                      ),
                                      onPressed: () {
                                        _currentPage += 1;
                                        _loadPage();
                                      },
                                      icon: const Icon(Icons.expand_more),
                                      label: const Text('আরও দেখুন'),
                                    ),
                            ),
                          );
                        }
                        return _TeacherCard(data: _items[index]);
                      },
                    ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TeacherCard extends StatelessWidget {
  final Map<String, dynamic> data;
  const _TeacherCard({required this.data});

  @override
  Widget build(BuildContext context) {
    final name = (data['name'] ?? '').toString();
    final initials = (data['initials'] ?? '').toString();
    final desig = (data['designation'] ?? '').toString();
    final phone = (data['phone'] ?? '').toString();
    final email = (data['email'] ?? '').toString();
    final photoUrl = (data['photo_url'] ?? '').toString();
    final address = (data['permanent_address'] ?? '').toString();

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _TeacherAvatar(name: name, photoUrl: photoUrl),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        initials.isNotEmpty ? '$name ($initials)' : name,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: LeColors.ink,
                        ),
                      ),
                      if (desig.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 3,
                          ),
                          decoration: BoxDecoration(
                            color: LeColors.brandSoft,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            desig,
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: LeColors.brandDark,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
            if (phone.isNotEmpty || email.isNotEmpty || address.isNotEmpty) ...[
              const Divider(height: 20),
            ],
            if (phone.isNotEmpty)
              _InfoRow(
                icon: Icons.call,
                iconColor: LeColors.completed,
                text: phone,
                trailing: IconButton(
                  constraints: const BoxConstraints(),
                  padding: EdgeInsets.zero,
                  visualDensity: VisualDensity.compact,
                  tooltip: 'কল করুন',
                  icon: const Icon(
                    Icons.phone_forwarded,
                    color: LeColors.completed,
                    size: 20,
                  ),
                  onPressed: () => _callNumber(context, phone),
                ),
              ),
            if (email.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: _InfoRow(
                  icon: Icons.email_outlined,
                  iconColor: LeColors.accent,
                  text: email,
                  trailing: IconButton(
                    constraints: const BoxConstraints(),
                    padding: EdgeInsets.zero,
                    visualDensity: VisualDensity.compact,
                    tooltip: 'ইমেইল করুন',
                    icon: const Icon(
                      Icons.send_outlined,
                      color: LeColors.accent,
                      size: 20,
                    ),
                    onPressed: () => _emailTeacher(context, email),
                  ),
                ),
              ),
            if (address.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: _InfoRow(
                  icon: Icons.location_on_outlined,
                  iconColor: LeColors.total,
                  text: address,
                ),
              ),
          ],
        ),
      ),
    );
  }

  Future<void> _callNumber(BuildContext context, String phone) async {
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      if (!context.mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('$phone নম্বরে কল করা যাচ্ছে না')));
    }
  }

  Future<void> _emailTeacher(BuildContext context, String email) async {
    final uri = Uri(scheme: 'mailto', path: email);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('$email ঠিকানায় ইমেইল করা যাচ্ছে না')),
      );
    }
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String text;
  final Widget? trailing;
  const _InfoRow({
    required this.icon,
    required this.iconColor,
    required this.text,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: iconColor),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: const TextStyle(fontSize: 13, color: LeColors.ink),
          ),
        ),
        if (trailing != null) trailing!,
      ],
    );
  }
}

class _TeacherAvatar extends StatelessWidget {
  final String name;
  final String photoUrl;
  const _TeacherAvatar({required this.name, required this.photoUrl});

  @override
  Widget build(BuildContext context) {
    final imageUrl = photoUrl.isNotEmpty ? photoUrl : null;
    if (imageUrl == null) {
      return CircleAvatar(
        radius: 28,
        backgroundColor: LeColors.brandSoft,
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : '?',
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: LeColors.brandDark,
          ),
        ),
      );
    }
    return CircleAvatar(
      radius: 28,
      backgroundColor: LeColors.brandSoft,
      child: ClipOval(
        child: CachedNetworkImage(
          imageUrl: imageUrl,
          width: 56,
          height: 56,
          fit: BoxFit.cover,
          placeholder: (context, url) => const Center(
            child: SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: LeColors.brand,
              ),
            ),
          ),
          errorWidget: (context, url, error) => Text(
            name.isNotEmpty ? name[0].toUpperCase() : '?',
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: LeColors.brandDark,
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
        showCheckmark: false,
        selectedColor: Colors.white,
        backgroundColor: LeColors.brandDark.withValues(alpha: 0.35),
        labelStyle: TextStyle(
          color: selected ? LeColors.brandDark : Colors.white,
          fontWeight: FontWeight.w600,
          fontSize: 12,
        ),
        side: BorderSide(color: Colors.white.withValues(alpha: 0.6)),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
    );
  }
}
