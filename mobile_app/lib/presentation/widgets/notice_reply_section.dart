import 'package:dio/dio.dart';
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
    debugPrint('[NoticeReply] ▶ onCompleted: filePath=$filePath | duration=$duration | noticeId=${widget.noticeId}');

    setState(() => _isSending = true);

    try {
      debugPrint('[NoticeReply] ⏳ Calling submitVoiceReply...');
      await ref.read(noticeRepositoryProvider).submitVoiceReply(
        widget.noticeId,
        filePath,
        duration.toDouble(),
      );
      debugPrint('[NoticeReply] ✅ submitVoiceReply SUCCESS');

      if (!mounted) {
        debugPrint('[NoticeReply] ⚠ Widget unmounted after success — cannot setState');
        return;
      }
      setState(() {
        _isSending = false;
        _isSent = true;
        _showRecorder = false;
      });
      ref.invalidate(noticesListProvider);

    } on DioException catch (e) {
      debugPrint('[NoticeReply] ❌ DioException:'
          ' type=${e.type.name}'
          ' | status=${e.response?.statusCode}'
          ' | data=${e.response?.data}'
          ' | msg=${e.message}');

      if (!mounted) {
        debugPrint('[NoticeReply] ⚠ Widget unmounted after DioException');
        return;
      }
      setState(() => _isSending = false);

      final dynamic respData = e.response?.data;
      final String? serverMsg = (respData is Map) ? respData['message'] as String? : null;
      final String displayMsg = serverMsg ?? 'নেটওয়ার্ক সমস্যা (${e.type.name})';

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(displayMsg),
            backgroundColor: Colors.red[700],
            behavior: SnackBarBehavior.floating,
            duration: const Duration(seconds: 5),
          ),
        );
      }
    } catch (e, stack) {
      debugPrint('[NoticeReply] ❌ Unknown error: $e\n$stack');

      if (!mounted) {
        debugPrint('[NoticeReply] ⚠ Widget unmounted after unknown error');
        return;
      }
      setState(() => _isSending = false);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('ত্রুটি: $e'),
            backgroundColor: Colors.red[700],
            behavior: SnackBarBehavior.floating,
            duration: const Duration(seconds: 5),
          ),
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
              'আপনার রিপ্লাই পাঠানো হয়েছে',
              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue),
            ),
          ],
        ),
      );
    }

    // VoiceRecorder stays mounted even during sending — isSending controls the button state
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
              'এই নোটিশের জন্য একটি ভয়েস রিপ্লাই দিন',
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
      isSending: _isSending,
    );
  }
}
