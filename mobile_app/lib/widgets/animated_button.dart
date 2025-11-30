import 'package:flutter/material.dart';
import 'package:rive/rive.dart';

class AnimatedButton extends StatefulWidget {
  const AnimatedButton({
    super.key,
    required this.onPressed,
    this.label = 'Continue',
    this.width,
    this.height = 48,
  });

  final Future<void> Function() onPressed;
  final String label;
  final double? width;
  final double height;

  @override
  State<AnimatedButton> createState() => _AnimatedButtonState();
}

class _AnimatedButtonState extends State<AnimatedButton> {
  Artboard? _artboard;
  SMIInput<bool>? _isPressed;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _loadRive();
  }

  Future<void> _loadRive() async {
    try {
      final data = await RiveFile.asset('assets/RiveAssets/button.riv');
      final artboard = data.mainArtboard;
      final controller = StateMachineController.fromArtboard(
        artboard,
        'Button',
      );
      if (controller != null) {
        artboard.addController(controller);
        _isPressed = controller.findInput<bool>('pressed');
      }
      setState(() => _artboard = artboard);
    } catch (_) {
      // If asset missing, fallback gracefully.
      setState(() => _artboard = null);
    }
  }

  Future<void> _handlePress() async {
    if (_loading) return;
    setState(() => _loading = true);
    _isPressed?.value = true;
    try {
      await widget.onPressed();
    } catch (_) {
    } finally {
      await Future.delayed(const Duration(milliseconds: 400));
      _isPressed?.value = false;
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final textStyle = const TextStyle(
      color: Colors.white,
      fontSize: 16,
      fontWeight: FontWeight.w700,
      shadows: [
        Shadow(color: Color(0x40000000), offset: Offset(0, 1), blurRadius: 2),
      ],
    );
    final currentLabel = _loading ? 'Signing in…' : widget.label;
    final child = _artboard != null
        ? Stack(
            alignment: Alignment.center,
            children: [
              Rive(artboard: _artboard!),
              Text(currentLabel, style: textStyle),
            ],
          )
        : Center(
            child: _loading
                ? const SizedBox(
                    height: 24,
                    width: 24,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Text(currentLabel, style: textStyle),
          );

    return SizedBox(
      width: widget.width ?? double.infinity,
      height: widget.height,
      child: GestureDetector(
        onTap: _handlePress,
        child: DecoratedBox(
          decoration: const BoxDecoration(
            color: Color(0xFF00BF6D),
            borderRadius: BorderRadius.all(Radius.circular(28)),
          ),
          child: child,
        ),
      ),
    );
  }
}
