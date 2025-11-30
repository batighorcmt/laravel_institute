import 'package:flutter/material.dart';
import 'package:rive/rive.dart';

class AnimatedTile extends StatefulWidget {
  const AnimatedTile({
    super.key,
    required this.title,
    required this.onTap,
    this.icon,
    this.riveIcon,
    this.stateMachine,
    this.background,
  });

  final String title;
  final VoidCallback onTap;
  final IconData? icon; // Fallback icon
  final String? riveIcon; // Name of artboard/state machine inside icons.riv
  final String? stateMachine; // Optional override for state machine name
  final Color? background;

  @override
  State<AnimatedTile> createState() => _AnimatedTileState();
}

class _AnimatedTileState extends State<AnimatedTile> {
  Artboard? _artboard;
  SMIInput<bool>? _hoverInput;
  SMIInput<bool>? _pressInput;

  @override
  void initState() {
    super.initState();
    _loadRive();
  }

  Future<void> _loadRive() async {
    if (widget.riveIcon == null) return; // use fallback
    try {
      final file = await RiveFile.asset('assets/RiveAssets/icons.riv');
      final art = file.artboardByName(widget.riveIcon!); // may be null
      final board = art ?? file.mainArtboard;
      final controller = StateMachineController.fromArtboard(
        board,
        widget.stateMachine ?? 'Icons',
      );
      if (controller != null) {
        board.addController(controller);
        _hoverInput = controller.findInput<bool>('hover');
        _pressInput = controller.findInput<bool>('pressed');
      }
      setState(() => _artboard = board);
    } catch (_) {
      setState(() => _artboard = null);
    }
  }

  void _onTapDown(_) => _pressInput?.value = true;
  void _onTapUp(_) => _pressInput?.value = false;
  void _onHover(bool v) => _hoverInput?.value = v;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final bg = widget.background ?? Colors.white;
    return MouseRegion(
      onEnter: (_) => _onHover(true),
      onExit: (_) => _onHover(false),
      child: GestureDetector(
        onTapDown: _onTapDown,
        onTapCancel: () => _pressInput?.value = false,
        onTapUp: _onTapUp,
        onTap: widget.onTap,
        child: Card(
          elevation: 3,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: BorderSide(color: Colors.black.withValues(alpha: 0.12)),
          ),
          color: bg,
          surfaceTintColor: Colors.transparent,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                SizedBox(
                  height: 50,
                  child: _artboard != null
                      ? ColorFiltered(
                          colorFilter: const ColorFilter.matrix([
                            1.2,
                            0,
                            0,
                            0,
                            0,
                            0,
                            1.2,
                            0,
                            0,
                            0,
                            0,
                            0,
                            1.2,
                            0,
                            0,
                            0,
                            0,
                            0,
                            1,
                            0,
                          ]),
                          child: Rive(
                            artboard: _artboard!,
                            fit: BoxFit.contain,
                          ),
                        )
                      : Icon(
                          widget.icon ?? Icons.circle_outlined,
                          size: 42,
                          color: scheme.primary,
                        ),
                ),
                const SizedBox(height: 12),
                Text(
                  widget.title,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: scheme.onSurface,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
