import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../widgets/gradient_scaffold.dart';
import '../../state/auth_state.dart';
import '../../../domain/auth/user_profile.dart';
import '../../../theme/theme_mode_provider.dart';

class HomePage extends ConsumerWidget {
  final UserProfile profile;
  const HomePage({super.key, required this.profile});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final roles = profile.roles.map((r) => r.role).toSet();
    return GradientScaffold(
      appBar: AppBar(
        title: const Text('Institute App'),
        actions: [
          IconButton(
            tooltip: 'Toggle theme',
            icon: const Icon(Icons.dark_mode_outlined),
            onPressed: () {
              ref.read(themeModeProvider.notifier).toggle();
            },
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Welcome, ${profile.name}'),
            const SizedBox(height: 12),
            if (roles.contains('principal'))
              ElevatedButton(
                onPressed: () => context.go('/principal'),
                child: const Text('Principal Dashboard'),
              ),
            // Principals should also be able to open the Teacher Dashboard
            if (roles.contains('teacher') ||
                roles.contains('principal') ||
                roles.contains('head'))
              ElevatedButton(
                onPressed: () => context.go('/teacher'),
                child: const Text('Teacher Dashboard'),
              ),
            if (roles.contains('parent'))
              ElevatedButton(
                onPressed: () => context.go('/parent'),
                child: const Text('Parent Dashboard'),
              ),
            ElevatedButton(
              onPressed: () => context.go('/login'),
              child: const Text('Go to Login'),
            ),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: () async {
                await ref.read(authProvider.notifier).logout();
                if (context.mounted) {
                  ScaffoldMessenger.of(
                    context,
                  ).showSnackBar(const SnackBar(content: Text('Logged out')));
                }
              },
              child: const Text('Logout'),
            ),
          ],
        ),
      ),
    );
  }
}
