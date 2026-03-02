import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:audioplayers/audioplayers.dart';
import '../../state/notice_state.dart';

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

  @override
  void dispose() {
    _audioPlayer.dispose();
    super.dispose();
  }

  Future<void> _togglePlay(String url) async {
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
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final statsAsync = ref.watch(noticeStatsProvider(widget.noticeId));

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
          final notice = data['notice'];
          final replies = (stats['replies'] as List? ?? []);
          final reads = (stats['reads'] as List? ?? []);
          final totalRecipients = stats['total_recipients'] ?? 0;
          final readCount = stats['read_count'] ?? 0;
          final replyCount = stats['reply_count'] ?? 0;

          return DefaultTabController(
            length: 2,
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(20),
                  color: Colors.white,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        widget.noticeTitle,
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          _StatBox(
                            label: 'মোট প্রাপক',
                            value: '$totalRecipients',
                            color: Colors.orange,
                            icon: Icons.groups_outlined,
                          ),
                          _StatBox(
                            label: 'দেখেছে',
                            value: '$readCount',
                            color: Colors.blue,
                            icon: Icons.visibility_outlined,
                          ),
                          _StatBox(
                            label: 'রিপ্লাই',
                            value: '$replyCount',
                            color: Colors.green,
                            icon: Icons.reply_all_outlined,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  color: Colors.white,
                  child: TabBar(
                    labelColor: Colors.blue[700],
                    unselectedLabelColor: Colors.grey,
                    indicatorColor: Colors.blue[700],
                    tabs: [
                      Tab(text: 'রিপ্লাই তালিকা ($replyCount)'),
                      Tab(text: 'পড়া হয়েছে ($readCount)'),
                    ],
                  ),
                ),
                Expanded(
                  child: TabBarView(
                    children: [
                      _buildRepliesList(replies),
                      _buildReadsList(reads),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(child: Text('ত্রুটি: $err')),
      ),
    );
  }

  Widget _buildRepliesList(List replies) {
    if (replies.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.speaker_notes_off_outlined, size: 64, color: Colors.grey),
            SizedBox(height: 16),
            Text('কোনো রিপ্লাই পাওয়া যায়নি', style: TextStyle(color: Colors.grey)),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: replies.length,
      itemBuilder: (context, index) {
        final reply = replies[index];
        final student = reply['student'];
        final parent = reply['parent'];
        final voiceUrl = reply['voice_url'];
        final isPlaying = _currentlyPlayingUrl == voiceUrl && _audioPlayer.state == PlayerState.playing;

        return Card(
          elevation: 0,
          margin: const EdgeInsets.only(bottom: 12),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
              side: BorderSide(color: Colors.grey[200]!)),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.green[50],
                  child: const Icon(Icons.person, color: Colors.green),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        student != null ? student['name'] ?? 'N/A' : parent?['name'] ?? 'N/A',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      Text(
                        student != null ? 'ক্লাস: ${student['class_name'] ?? ''}' : 'শিক্ষক/অভিভাবক',
                        style: TextStyle(color: Colors.grey[600], fontSize: 12),
                      ),
                    ],
                  ),
                ),
                if (voiceUrl != null)
                  Container(
                    decoration: BoxDecoration(
                      color: isPlaying ? Colors.orange[50] : Colors.blue[50],
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: IconButton(
                      icon: Icon(isPlaying ? Icons.pause : Icons.play_arrow),
                      color: isPlaying ? Colors.orange : Colors.blue,
                      onPressed: () => _togglePlay(voiceUrl),
                    ),
                  ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildReadsList(List reads) {
    if (reads.isEmpty) {
      return const Center(child: Text('কেউ এখনও দেখেনি', style: TextStyle(color: Colors.grey)));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: reads.length,
      itemBuilder: (context, index) {
        final read = reads[index];
        final user = read['user'];
        final readAt = DateTime.tryParse(read['read_at'] ?? '') ?? DateTime.now();

        return ListTile(
          leading: const CircleAvatar(child: Icon(Icons.person_outline)),
          title: Text(user?['name'] ?? 'N/A'),
          subtitle: Text(DateFormat('dd MMM, hh:mm a').format(readAt)),
          trailing: const Icon(Icons.done_all, color: Colors.blue, size: 16),
        );
      },
    );
  }
}

class _StatBox extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final IconData icon;

  const _StatBox({
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        margin: const EdgeInsets.only(right: 8),
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          color: color.withOpacity(0.05),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withOpacity(0.1)),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(height: 8),
            Text(
              value,
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color),
            ),
            Text(
              label,
              style: TextStyle(fontSize: 10, color: Colors.grey[600]),
            ),
          ],
        ),
      ),
    );
  }
}
