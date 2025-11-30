import 'package:flutter/material.dart';
import 'package:rive/rive.dart';

Future<void> showAppSnack(
  BuildContext context, {
  required String message,
  bool success = false,
  bool error = false,
  Duration duration = const Duration(seconds: 3),
}) async {
  Widget? riveWidget;
  if (success || error) {
    final asset = success
        ? 'assets/RiveAssets/check.riv'
        : 'assets/RiveAssets/confetti.riv';
    try {
      final file = await RiveFile.asset(asset);
      final art = file.mainArtboard;
      riveWidget = SizedBox(
        height: 36,
        width: 36,
        child: Rive(artboard: art, fit: BoxFit.contain),
      );
    } catch (_) {
      riveWidget = null;
    }
  }

  final content = Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      if (riveWidget != null) riveWidget,
      if (riveWidget != null) const SizedBox(width: 12),
      Flexible(
        child: Text(message, style: const TextStyle(color: Colors.white)),
      ),
    ],
  );

  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      backgroundColor: success
          ? const Color(0xFF00BF6D)
          : error
          ? Colors.redAccent
          : Colors.black87,
      duration: duration,
      content: content,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ),
  );
}
