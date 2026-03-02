import 'dart:async';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:record/record.dart';
import 'package:audioplayers/audioplayers.dart';
import 'package:path/path.dart' as p;

class VoiceRecorder extends StatefulWidget {
  final Function(String filePath, int durationSeconds) onCompleted;
  final VoidCallback onCancel;

  const VoiceRecorder({
    super.key,
    required this.onCompleted,
    required this.onCancel,
  });

  @override
  State<VoiceRecorder> createState() => _VoiceRecorderState();
}

class _VoiceRecorderState extends State<VoiceRecorder> {
  late final AudioRecorder _audioRecorder;
  final AudioPlayer _audioPlayer = AudioPlayer();
  
  bool _isRecording = false;
  String? _filePath;
  int _recordDuration = 0;
  Timer? _timer;
  
  // Playback state
  bool _isPlaying = false;
  Duration _playPosition = Duration.zero;
  Duration _playDuration = Duration.zero;
  StreamSubscription? _playerStateSubscription;
  StreamSubscription? _playerPositionSubscription;
  StreamSubscription? _playerDurationSubscription;

  @override
  void initState() {
    super.initState();
    _audioRecorder = AudioRecorder();
    _initPlayerListeners();
  }

  void _initPlayerListeners() {
    _playerStateSubscription = _audioPlayer.onPlayerStateChanged.listen((state) {
      if (mounted) {
        setState(() {
          _isPlaying = state == PlayerState.playing;
        });
      }
    });
    _playerPositionSubscription = _audioPlayer.onPositionChanged.listen((p) {
      if (mounted) setState(() => _playPosition = p);
    });
    _playerDurationSubscription = _audioPlayer.onDurationChanged.listen((d) {
      if (mounted) setState(() => _playDuration = d);
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _audioRecorder.dispose();
    _audioPlayer.dispose();
    _playerStateSubscription?.cancel();
    _playerPositionSubscription?.cancel();
    _playerDurationSubscription?.cancel();
    super.dispose();
  }

  Future<void> _start() async {
    try {
      if (await _audioRecorder.hasPermission()) {
        final directory = await getApplicationDocumentsDirectory();
        final path = p.join(directory.path, 'reply_${DateTime.now().millisecondsSinceEpoch}.m4a');

        const config = RecordConfig(encoder: AudioEncoder.aacLc);

        await _audioRecorder.start(config, path: path);

        setState(() {
          _isRecording = true;
          _recordDuration = 0;
          _filePath = null;
        });
        _startTimer();
      }
    } catch (e) {
      debugPrint(e.toString());
    }
  }

  Future<void> _stop() async {
    _timer?.cancel();
    final path = await _audioRecorder.stop();
    setState(() {
      _isRecording = false;
      _filePath = path;
    });
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (Timer t) {
      setState(() => _recordDuration++);
      if (_recordDuration >= 30) {
        _stop();
      }
    });
  }

  Future<void> _playPreview() async {
    if (_filePath == null) return;
    if (_isPlaying) {
      await _audioPlayer.pause();
    } else {
      await _audioPlayer.play(DeviceFileSource(_filePath!));
    }
  }

  void _reset() {
    setState(() {
      _filePath = null;
      _recordDuration = 0;
      _isRecording = false;
    });
  }

  String _formatNumber(int number) {
    return number.toString().padLeft(2, '0');
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (_filePath == null) ...[
            Text(
              _isRecording ? 'রেকর্ডিং হচ্ছে...' : 'ভয়েস রিপ্লাই দিন',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                color: _isRecording ? Colors.red : Colors.grey[700],
              ),
            ),
            const SizedBox(height: 12),
            Text(
              '00:${_formatNumber(_recordDuration)} / 00:30',
              style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 24),
            GestureDetector(
              onTap: _isRecording ? _stop : _start,
              child: Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: _isRecording ? Colors.red : Colors.blue,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: (_isRecording ? Colors.red : Colors.blue).withOpacity(0.3),
                      blurRadius: 12,
                      spreadRadius: 4,
                    )
                  ],
                ),
                child: Icon(
                  _isRecording ? Icons.stop : Icons.mic,
                  color: Colors.white,
                  size: 32,
                ),
              ),
            ),
            const SizedBox(height: 16),
            TextButton(
              onPressed: widget.onCancel,
              child: const Text('বাতিল করুন', style: TextStyle(color: Colors.grey)),
            ),
          ] else ...[
            const Text(
              'রেকর্ডিং সম্পন্ন হয়েছে',
              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.green),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                IconButton(
                  onPressed: _playPreview,
                  icon: Icon(_isPlaying ? Icons.pause : Icons.play_arrow),
                  color: Colors.blue,
                ),
                Expanded(
                  child: Slider(
                    value: _playPosition.inMilliseconds.toDouble(),
                    max: _playDuration.inMilliseconds.toDouble() > 0 
                         ? _playDuration.inMilliseconds.toDouble() 
                         : 100,
                    onChanged: (value) async {
                      await _audioPlayer.seek(Duration(milliseconds: value.toInt()));
                    },
                  ),
                ),
                Text(
                  '00:${_formatNumber(_playDuration.inSeconds)}',
                  style: const TextStyle(fontSize: 12),
                ),
              ],
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _reset,
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: Colors.red),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    child: const Text('আবার রেকর্ড করুন', style: TextStyle(color: Colors.red)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => widget.onCompleted(_filePath!, _recordDuration),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    child: const Text('রিপ্লাই পাঠান'),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
