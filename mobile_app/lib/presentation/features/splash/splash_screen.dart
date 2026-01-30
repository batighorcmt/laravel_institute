import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../state/auth_state.dart';
import 'package:go_router/go_router.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer(const Duration(milliseconds: 1000), _proceed);
  }

  void _proceed() {
    if (!mounted) return;
    final profile = ref.read(authProvider).asData?.value;
    if (profile == null) {
      context.go('/login');
      return;
    }
    final roles = profile.roles.map((r) => r.role.toLowerCase()).toList();
    // Prefer principal dashboard when user is a principal even if they also
    // have a teacher role. Principals should still be able to access
    // teacher flows from their dashboard.
    if (roles.contains('principal')) {
      context.go('/principal');
    } else if (roles.contains('teacher')) {
      context.go('/teacher');
    } else if (roles.contains('parent')) {
      context.go('/parent');
    } else {
      context.go('/');
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: Image.asset(
          'assets/images/Splash.gif',
          fit: BoxFit.contain,
          height: 260,
        ),
      ),
    );
  }
}
