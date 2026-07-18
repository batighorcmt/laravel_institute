import 'package:flutter/material.dart';
import 'dart:developer' as developer;
import '../../core/navigation.dart';
import '../../core/services/update_service.dart';
import 'update_dialog.dart';

/// Wraps the app root and checks for a mandatory update on every cold start.
///
/// Previously this widget was a no-op stub (just returned [child]) — the
/// backend check endpoint, [UpdateService], and [UpdateDialog] all existed
/// and worked, but nothing ever called them, so the whole feature was dead.
class UpdateChecker extends StatefulWidget {
  final Widget child;

  const UpdateChecker({super.key, required this.child});

  @override
  State<UpdateChecker> createState() => _UpdateCheckerState();
}

class _UpdateCheckerState extends State<UpdateChecker> {
  bool _checked = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _checkForUpdate());
  }

  Future<void> _checkForUpdate() async {
    if (_checked) return;
    _checked = true;
    try {
      final data = await UpdateService().checkForUpdate();
      if (data == null || !mounted) return;

      // Give the router's first route a moment to settle so the dialog
      // doesn't race the initial navigation frame (e.g. splash -> login).
      await Future.delayed(const Duration(milliseconds: 400));

      final ctx = rootNavigatorKey.currentContext;
      if (ctx == null || !ctx.mounted) return;

      showDialog(
        context: ctx,
        barrierDismissible: false,
        useRootNavigator: true,
        builder: (_) => UpdateDialog(updateData: data),
      );
    } catch (e) {
      developer.log('UpdateChecker error: $e', name: 'UpdateChecker');
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
