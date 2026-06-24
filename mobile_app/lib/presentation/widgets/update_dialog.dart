import 'package:flutter/material.dart';
import '../../core/services/update_service.dart';

class UpdateDialog extends StatefulWidget {
  final Map<String, dynamic> updateData;

  const UpdateDialog({super.key, required this.updateData});

  @override
  State<UpdateDialog> createState() => _UpdateDialogState();
}

class _UpdateDialogState extends State<UpdateDialog> {
  double _progress = 0;
  bool _isDownloading = false;
  String? _error;

  void _startDownload() async {
    setState(() {
      _isDownloading = true;
      _error = null;
    });

    try {
      await UpdateService().downloadAndInstall(
        url: widget.updateData['apk_url'],
        onProgress: (progress) {
          setState(() {
            _progress = progress;
          });
        },
        onError: (error) {
          setState(() {
            _isDownloading = false;
            _error = error;
          });
        },
      );
    } catch (e) {
      setState(() {
        _isDownloading = false;
        _error = e.toString();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    // User requested that any update should be mandatory ('অবশ্যই আপডেট করতে হবে')
    bool isMandatory = true;

    return PopScope(
      canPop: false,

      child: AlertDialog(
        title: Text(
          isMandatory ? 'নতুন আপডেট আবশ্যক' : 'নতুন আপডেট পাওয়া গেছে',
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            color: Colors.indigo,
          ),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('ভার্সন: ${widget.updateData['version_name']}'),
            if (widget.updateData['release_notes'] != null) ...[
              const SizedBox(height: 10),
              const Text(
                'কি কি নতুন আছে:',
                style: TextStyle(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 5),
              Text(widget.updateData['release_notes']),
            ],
            const SizedBox(height: 20),
            if (_isDownloading) ...[
              LinearProgressIndicator(
                value: _progress,
                backgroundColor: Colors.indigo.withValues(alpha: 0.1),
                valueColor: const AlwaysStoppedAnimation<Color>(Colors.indigo),
              ),
              const SizedBox(height: 10),
              Center(
                child: Text(
                  'ডাউনলোড হচ্ছে: ${(_progress * 100).toStringAsFixed(1)}%',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ] else if (_error != null) ...[
              Text(
                'ভুল হয়েছে: $_error',
                style: const TextStyle(color: Colors.red),
              ),
            ] else
              const Text(
                'এপ্লিকেশনটি নিরবচ্ছিন্নভাবে ব্যবহার করতে এখনই আপডেট করুন।',
              ),
          ],
        ),
        actions: [
          if (!isMandatory && !_isDownloading)
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text(
                'পরে করুন',
                style: TextStyle(color: Colors.grey),
              ),
            ),
          ElevatedButton(
            onPressed: _isDownloading ? null : _startDownload,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.indigo,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: Text(
              _isDownloading ? 'ডাউনলোড হচ্ছে...' : 'এখনই আপডেট করুন',
            ),
          ),
        ],
      ),
    );
  }
}
