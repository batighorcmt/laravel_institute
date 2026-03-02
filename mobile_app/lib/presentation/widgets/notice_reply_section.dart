import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/notice/notice_repository.dart';
import '../state/notice_state.dart';
import 'voice_recorder.dart';

class NoticeReplySection extends ConsumerStatefulWidget {
  final int noticeId;

  const NoticeReplySection({
    super.key,
    required this.noticeId,
  });

  @override
  ConsumerState<NoticeReplySection> createState() => _NoticeReplySectionState();
}

class _NoticeReplySectionState extends ConsumerState<NoticeReplySection> {
  bool _isSent = false;
  bool _isSending = false;
  bool _showRecorder = false;

  Future<void> _handleVoiceCompleted(String filePath, int duration) async {
    setState(() => _isSending = true);
    try {
      await ref.read(noticeRepositoryProvider).submitVoiceReply(
        widget.noticeId, 
        filePath, 
        duration.toDouble(),
      );
      setState(() {
        _isSending = false;
        _isSent = true;
        _showRecorder = false;
      });
      // Optionally invalidate list to update counts if displayed
      ref.invalidate(noticesListProvider);
    } catch (e) {
      setState(() => _isSending = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('ত্রুটি: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isSent) {
      return Container(
        padding: const EdgeInsets.all(16),
        width: double.infinity,
        decoration: BoxDecoration(
          color: Colors.blue.withOpacity(0.05),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.blue.withOpacity(0.2)),
        ),
        child: const Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.check_circle, color: Colors.blue),
            SizedBox(width: 8),
            Text(
              'আপনার রিপ্লাই পাঠানো হয়েছে',
              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue),
            ),
          ],
        ),
      );
    }

    if (_isSending) {
      return Container(
        padding: const EdgeInsets.all(24),
        width: double.infinity,
        child: const Center(
          child: Column(
            children: [
              CircularProgressIndicator(),
              SizedBox(height: 12),
              Text('রিপ্লাই পাঠানো হচ্ছে...', style: TextStyle(color: Colors.grey)),
            ],
          ),
        ),
      );
    }

    if (!_showRecorder) {
      return Container(
        padding: const EdgeInsets.all(16),
        width: double.infinity,
        decoration: BoxDecoration(
          color: Colors.green.withOpacity(0.05),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.green.withOpacity(0.2)),
        ),
        child: Column(
          children: [
            const Icon(Icons.mic_none, color: Colors.green, size: 32),
            const SizedBox(height: 12),
            const Text(
              'এই নোটিশের জন্য একটি ভয়েস রিপ্লাই দিন',
              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.green),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => setState(() => _showRecorder = true),
                icon: const Icon(Icons.record_voice_over),
                label: const Text('রেকর্ড শুরু করুন'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ),
          ],
        ),
      );
    }

    return VoiceRecorder(
      onCompleted: _handleVoiceCompleted,
      onCancel: () => setState(() => _showRecorder = false),
    );
  }
}
