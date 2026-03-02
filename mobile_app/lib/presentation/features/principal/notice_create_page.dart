import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dropdown_search/dropdown_search.dart';
import 'package:intl/intl.dart';
import '../../state/notice_state.dart';

class NoticeCreatePage extends ConsumerStatefulWidget {
  const NoticeCreatePage({super.key});

  @override
  ConsumerState<NoticeCreatePage> createState() => _NoticeCreatePageState();
}

class _NoticeCreatePageState extends ConsumerState<NoticeCreatePage> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _body = TextEditingController();
  
  String _audienceType = 'all'; // all, teachers, students
  bool _replyRequired = false;
  DateTime? _publishAt;
  
  List<dynamic> _selectedClasses = [];
  List<dynamic> _selectedSections = [];
  List<dynamic> _selectedGroups = [];
  List<dynamic> _selectedStudents = [];
  
  bool _saving = false;

  @override
  void dispose() {
    _title.dispose();
    _body.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_formKey.currentState?.validate() ?? false) {
      setState(() => _saving = true);
      try {
        final Map<String, dynamic> data = {
          'title': _title.text,
          'body': _body.text,
          'audience_type': _audienceType,
          'reply_required': _replyRequired,
          'publish_at': _publishAt?.toIso8601String(),
          'targets': _buildTargets(),
        };

        await ref.read(noticeRepositoryProvider).createNotice(data);
        if (mounted) {
           Navigator.of(context).pop();
           ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('নোটিশ সফলভাবে তৈরি হয়েছে')));
        }
      } catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('ত্রুটি: $e')));
      } finally {
        if (mounted) setState(() => _saving = false);
      }
    }
  }

  List<Map<String, dynamic>> _buildTargets() {
    final List<Map<String, dynamic>> targets = [];
    
    // Simplistic additive targeting: we send what's selected
    for (final c in _selectedClasses) {
      targets.add({'type': 'Class', 'id': c['id']});
    }
    for (final s in _selectedSections) {
      targets.add({'type': 'Section', 'id': s['id']});
    }
    for (final g in _selectedGroups) {
      targets.add({'type': 'Group', 'id': g['id']});
    }
    for (final st in _selectedStudents) {
      targets.add({'type': 'Student', 'id': st['id']});
    }
    
    return targets;
  }

  @override
  Widget build(BuildContext context) {
    final classesAsync = ref.watch(metaClassesProvider);
    final sectionsAsync = ref.watch(metaSectionsProvider);
    final groupsAsync = ref.watch(metaGroupsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('নতুন নোটিশ')),
      body: _saving ? const Center(child: CircularProgressIndicator()) : SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextFormField(
                controller: _title,
                decoration: InputDecoration(
                  labelText: 'শিরোনাম *',
                  hintText: 'নোটিশের শিরোনাম লিখুন',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  prefixIcon: const Icon(Icons.title),
                ),
                validator: (v) => (v ?? '').isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _body,
                decoration: InputDecoration(
                  labelText: 'বিস্তারিত বিবরণ *',
                  hintText: 'বিস্তারিত তথ্য এখানে লিখুন...',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  alignLabelWithHint: true,
                ),
                maxLines: 6,
                validator: (v) => (v ?? '').isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 16),
              
              const Text(' প্রাপক নির্বাচন:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              const SizedBox(height: 8),
              
              DropdownButtonFormField<String>(
                value: _audienceType,
                decoration: InputDecoration(
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                ),
                items: const [
                  DropdownMenuItem(value: 'all', child: Text('সবাইকে (শিক্ষক ও শিক্ষার্থী)')),
                  DropdownMenuItem(value: 'teachers', child: Text('শুধুমাত্র শিক্ষকদের জন্য')),
                  DropdownMenuItem(value: 'students', child: Text('শুধুমাত্র শিক্ষার্থীদের জন্য')),
                ],
                onChanged: (v) => setState(() => _audienceType = v!),
              ),
              const SizedBox(height: 16),
              
              SwitchListTile(
                 title: const Text('ভয়েস রিপ্লাই প্রয়োজন?'),
                 value: _replyRequired,
                 onChanged: (v) => setState(() => _replyRequired = v),
              ),

              const Divider(),
              const Text('টার্গেট এরিয়া (ঐচ্ছিক):', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 12),

              // Classes Selector
              classesAsync.when(
                 data: (classes) => DropdownSearch<dynamic>.multiSelection(
                    dropdownDecoratorProps: const DropDownDecoratorProps(
                       dropdownSearchDecoration: InputDecoration(labelText: 'শ্রেণি নির্বাচন করুন', border: OutlineInputBorder()),
                    ),
                    items: classes,
                    itemAsString: (c) => c['name']?.toString() ?? '',
                    selectedItems: _selectedClasses,
                    onChanged: (v) => setState(() {
                       _selectedClasses = v;
                       // Reset sections if classes change? No, let user add whatever.
                    }),
                 ),
                 loading: () => const LinearProgressIndicator(),
                 error: (e, _) => Text('Error: $e'),
              ),
              const SizedBox(height: 12),

              // Sections Selector (Filtered by classes if any selected locally)
              sectionsAsync.when(
                 data: (sections) {
                    final filteredSections = _selectedClasses.isEmpty 
                       ? sections 
                       : sections.where((sec) => _selectedClasses.any((cls) => cls['id'] == sec['class_id'])).toList();

                    return DropdownSearch<dynamic>.multiSelection(
                       dropdownDecoratorProps: const DropDownDecoratorProps(
                          dropdownSearchDecoration: InputDecoration(labelText: 'শাখা/সেকশন (ঐচ্ছিক)', border: OutlineInputBorder()),
                       ),
                       items: filteredSections,
                       itemAsString: (s) => '${s['school_class']?['name'] ?? ''} - ${s['name']}',
                       selectedItems: _selectedSections,
                       onChanged: (v) => setState(() => _selectedSections = v),
                    );
                 },
                 loading: () => const SizedBox(),
                 error: (e, _) => const SizedBox(),
              ),
              const SizedBox(height: 12),

              // Groups Selector
              groupsAsync.when(
                 data: (groups) => DropdownSearch<dynamic>.multiSelection(
                    dropdownDecoratorProps: const DropDownDecoratorProps(
                       dropdownSearchDecoration: InputDecoration(labelText: 'বিভাগ নির্বাচন করুন (ঐচ্ছিক)', border: OutlineInputBorder()),
                    ),
                    items: groups,
                    itemAsString: (g) => g['name']?.toString() ?? '',
                    selectedItems: _selectedGroups,
                    onChanged: (v) => setState(() => _selectedGroups = v),
                 ),
                 loading: () => const SizedBox(),
                 error: (e, _) => const SizedBox(),
              ),
              const SizedBox(height: 12),

              // Specific Student Search
              DropdownSearch<dynamic>.multiSelection(
                asyncItems: (String filter) => ref.read(noticeRepositoryProvider).searchStudents(filter),
                dropdownDecoratorProps: const DropDownDecoratorProps(
                  dropdownSearchDecoration: InputDecoration(labelText: 'নির্দিষ্ট শিক্ষার্থী (সার্চ করুন)', border: OutlineInputBorder()),
                ),
                itemAsString: (s) => '${s['full_name']} (${s['id_no']}) - ${s['school_class']?['name'] ?? ''}',
                onChanged: (v) => setState(() => _selectedStudents = v),
                selectedItems: _selectedStudents,
              ),

              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('তৈরি ও প্রচার করুন', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(height: 32),
            ],
          ),
        ),
      ),
    );
  }
}
