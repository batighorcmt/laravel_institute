import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../routes/app_router.dart';
import '../../state/auth_state.dart';
import '../../../domain/auth/user_profile.dart';

class HomePage extends ConsumerWidget {
  final UserProfile profile;
  const HomePage({super.key, required this.profile});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final roles = profile.roles.map((r) => r.role).toSet();
    return Scaffold(
      appBar: AppBar(title: const Text('Institute App')),
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Welcome, ${profile.name}'),
            const SizedBox(height: 12),
            if (roles.contains('principal'))
              ElevatedButton(
                onPressed: () => appRouter.go('/principal'),
                child: const Text('Principal Dashboard'),
              ),
            if (roles.contains('teacher'))
              ElevatedButton(
                onPressed: () => appRouter.go('/teacher'),
                child: const Text('Teacher Dashboard'),
              ),
            if (roles.contains('parent'))
              ElevatedButton(
                onPressed: () => appRouter.go('/parent'),
                child: const Text('Parent Dashboard'),
              ),
            ElevatedButton(
              onPressed: () => appRouter.go('/login'),
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
