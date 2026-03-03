import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../state/notice_state.dart';
import 'package:audioplayers/audioplayers.dart';

class NoticeStatsPage extends ConsumerStatefulWidget {
  final int noticeId;
  final String noticeTitle;

  const NoticeStatsPage({
    super.key,
    required this.noticeId,
    required this.noticeTitle,
  });

  @override
  ConsumerState<NoticeStatsPage> createState() => _NoticeStatsPageState();
}

class _NoticeStatsPageState extends ConsumerState<NoticeStatsPage> {
  final AudioPlayer _audioPlayer = AudioPlayer();
  String? _currentlyPlayingUrl;
  
  // Filters
  String _typeFilter = 'all'; // all, teacher, student
  String? _classFilter;
  String? _sectionFilter;
  String _statusFilter = 'all'; // all, read, unread, replied, not_replied

  @override
  void initState() {
    super.initState();
    _audioPlayer.onPlayerStateChanged.listen((state) {
      if (mounted) {
        if (state == PlayerState.completed || state == PlayerState.stopped) {
          setState(() {
            _currentlyPlayingUrl = null;
          });
        } else {
          setState(() {}); 
        }
      }
    });
  }

  @override
  void dispose() {
    _audioPlayer.dispose();
    super.dispose();
  }

  Future<void> _togglePlay(String url) async {
    try {
      if (_currentlyPlayingUrl == url) {
        if (_audioPlayer.state == PlayerState.playing) {
          await _audioPlayer.pause();
        } else {
          await _audioPlayer.resume();
        }
      } else {
        await _audioPlayer.stop();
        await _audioPlayer.play(UrlSource(url));
        setState(() {
          _currentlyPlayingUrl = url;
        });
      }
    } catch (e) {
      debugPrint('Audio error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final statsAsync = ref.watch(noticeStatsProvider(widget.noticeId));
    final classesAsync = ref.watch(metaClassesProvider);
    final sectionsAsync = ref.watch(metaSectionsProvider);

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text('এক্টিভিটি ট্র্যাকিং'),
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
      ),
      body: statsAsync.when(
        data: (data) {
          final stats = data['stats'];
          final List allRecipients = stats['all'] ?? [];
          
          // Apply Filters
          var filteredList = allRecipients.where((r) {
            bool matchesType = _typeFilter == 'all' || r['type'] == _typeFilter;
            bool matchesClass = _classFilter == null || r['class_name'] == _classFilter;
            bool matchesSection = _sectionFilter == null || r['section_name'] == _sectionFilter;
            bool matchesStatus = _statusFilter == 'all' || 
                                 (_statusFilter == 'read' && (r['status'] == 'read' || r['status'] == 'replied')) ||
                                 (_statusFilter == 'replied' && r['status'] == 'replied') ||
                                 (_statusFilter == 'unread' && r['status'] == 'unread') ||
                                 (_statusFilter == 'not_replied' && r['status'] != 'replied');
            
            return matchesType && matchesClass && matchesSection && matchesStatus;
          }).toList();

          return Column(
            children: [
              // Summary Header
              _buildHeader(stats, allRecipients),
              
              // Filter Bar
              _buildFilterBar(classesAsync, sectionsAsync),

              // Results List
              Expanded(
                child: filteredList.isEmpty 
                  ? const Center(child: Text('ফলাফল পাওয়া যায়নি'))
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: filteredList.length,
                      itemBuilder: (context, index) {
                        final person = filteredList[index];
                        return _buildPersonCard(person);
                      },
                    ),
              ),
            ],
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(child: Text('ত্রুটি: $err')),
      ),
    );
  }

  Widget _buildHeader(Map stats, List allRecipients) {
    final notRepliedCount = allRecipients.where((r) => r['status'] != 'replied').length;

    return Container(
      padding: const EdgeInsets.all(16),
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(widget.noticeTitle, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 12),
          Row(
            children: [
              _StatMiniBox(label: 'মোট', value: '${stats['total_recipients']}', color: Colors.blue),
              _StatMiniBox(label: 'দেখেছে', value: '${stats['read_count']}', color: Colors.orange),
              _StatMiniBox(label: 'রিপ্লাই', value: '${stats['reply_count']}', color: Colors.green),
              _StatMiniBox(label: 'রিপ্লাই দেয়নি', value: '$notRepliedCount', color: Colors.red),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildFilterBar(AsyncValue classesAsync, AsyncValue sectionsAsync) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      color: Colors.white,
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: [
            // Type Filter
            _buildDropdown(
              value: _typeFilter,
              items: const [
                DropdownMenuItem(value: 'all', child: Text('সবাই')),
                DropdownMenuItem(value: 'teacher', child: Text('শিক্ষক')),
                DropdownMenuItem(value: 'student', child: Text('শিক্ষার্থী')),
              ],
              onChanged: (val) => setState(() {
                _typeFilter = val!;
                if (_typeFilter != 'student') {
                  _classFilter = null;
                  _sectionFilter = null;
                }
              }),
            ),
            
            if (_typeFilter == 'student') ...[
              const SizedBox(width: 8),
              // Class Filter
              _buildDropdown(
                hint: 'সব শ্রেণি',
                value: _classFilter,
                items: [
                  const DropdownMenuItem(value: null, child: Text('সব শ্রেণি')),
                  ...classesAsync.maybeWhen(
                    data: (list) {
                       return list.map<DropdownMenuItem<String?>>((c) {
                         final name = c is Map ? c['name']?.toString() : c.toString();
                         return DropdownMenuItem(value: name, child: Text(name ?? ''));
                       }).toList();
                    },
                    loading: () => [const DropdownMenuItem(value: null, child: Text('লোড হচ্ছে...'))],
                    error: (err, _) => [DropdownMenuItem(value: null, child: Text('ত্রুটি: $err'))],
                    orElse: () => [],
                  ),
                ],
                onChanged: (val) => setState(() {
                  _classFilter = val;
                  _sectionFilter = null;
                }),
              ),
              const SizedBox(width: 8),
              // Section Filter
              _buildDropdown(
                hint: 'সব শাখা',
                value: _sectionFilter,
                items: [
                  const DropdownMenuItem(value: null, child: Text('সব শাখা')),
                  ...sectionsAsync.maybeWhen(
                    data: (list) {
                      final filtered = _classFilter == null 
                          ? list 
                          : list.where((s) => s['class_name'] == _classFilter).toList();
                      return filtered.map<DropdownMenuItem<String?>>((s) {
                        final name = s is Map ? s['name']?.toString() : s.toString();
                        return DropdownMenuItem(value: name, child: Text(name ?? ''));
                      }).toList();
                    },
                    loading: () => [const DropdownMenuItem(value: null, child: Text('লোড হচ্ছে...'))],
                    error: (err, _) => [DropdownMenuItem(value: null, child: Text('ত্রুটি: $err'))],
                    orElse: () => [],
                  ),
                ],
                onChanged: (val) => setState(() => _sectionFilter = val),
              ),
            ],
            
            const SizedBox(width: 8),
            // Status Filter
            _buildDropdown(
              value: _statusFilter,
              items: const [
                DropdownMenuItem(value: 'all', child: Text('অবস্থা: সব')),
                DropdownMenuItem(value: 'replied', child: Text('রিপ্লাই দিয়েছে')),
                DropdownMenuItem(value: 'not_replied', child: Text('রিপ্লাই দেয়নি')),
                DropdownMenuItem(value: 'read', child: Text('পড়া হয়েছে')),
                DropdownMenuItem(value: 'unread', child: Text('দেখা হয়নি')),
              ],
              onChanged: (val) => setState(() => _statusFilter = val!),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDropdown({String? value, required List<DropdownMenuItem<String?>> items, required ValueChanged<String?> onChanged, String? hint}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(8),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String?>(
          value: value,
          hint: hint != null ? Text(hint, style: const TextStyle(fontSize: 12)) : null,
          items: items,
          onChanged: onChanged,
          style: const TextStyle(fontSize: 12, color: Colors.black),
        ),
      ),
    );
  }

  Widget _buildPersonCard(Map person) {
    final status = person['status'];
    Color statusColor = Colors.grey;
    String statusText = 'দেখা হয়নি';
    
    if (status == 'read') {
        statusColor = Colors.blue;
        statusText = 'পড়া হয়েছে';
    } else if (status == 'replied') {
        statusColor = Colors.green;
        statusText = 'রিপ্লাই';
    }

    final voiceUrl = person['reply']?['url'];
    final isPlaying = _currentlyPlayingUrl == voiceUrl && _audioPlayer.state == PlayerState.playing;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 8),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Colors.grey[200]!),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            CircleAvatar(
              radius: 20,
              backgroundColor: Colors.blue[50],
              backgroundImage: person['photo_url'] != null ? NetworkImage(person['photo_url']) : null,
              child: person['photo_url'] == null 
                ? Icon(person['type'] == 'teacher' ? Icons.person_pin : Icons.person, color: Colors.blue)
                : null,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Flexible(child: Text(person['name'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold), overflow: TextOverflow.ellipsis)),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: statusColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(statusText, style: TextStyle(fontSize: 10, color: statusColor, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    person['details'] ?? 'N/A',
                    style: TextStyle(fontSize: 11, color: Colors.grey[600]),
                  ),
                ],
              ),
            ),
            if (voiceUrl != null)
              IconButton(
                icon: Icon(isPlaying ? Icons.pause_circle : Icons.play_circle, color: isPlaying ? Colors.orange : Colors.blue),
                onPressed: () => _togglePlay(voiceUrl),
              ),
          ],
        ),
      ),
    );
  }
}

class _StatMiniBox extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  const _StatMiniBox({required this.label, required this.value, required this.color});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        margin: const EdgeInsets.only(right: 8),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: color.withOpacity(0.05),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: color)),
            Text(label, style: TextStyle(fontSize: 10, color: Colors.grey[600]), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}
