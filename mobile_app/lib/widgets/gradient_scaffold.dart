import 'package:flutter/material.dart';

class GradientScaffold extends StatelessWidget {
  const GradientScaffold({
    super.key,
    required this.body,
    this.appBar,
    this.safe = true,
  });

  final Widget body;
  final PreferredSizeWidget? appBar;
  final bool safe;

  @override
  Widget build(BuildContext context) {
    final scaffold = Scaffold(
      appBar: appBar,
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF0FD68A), Color(0xFF00BF6D)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: body,
      ),
    );

    return safe ? SafeArea(child: scaffold) : scaffold;
  }
}
