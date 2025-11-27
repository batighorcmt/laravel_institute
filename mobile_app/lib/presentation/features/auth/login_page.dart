import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../data/auth/auth_repository.dart';
import '../../state/auth_state.dart';
import '../../../core/config/env.dart';

final authRepoProvider = Provider<AuthRepository>((ref) => AuthRepository());

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});

  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _username = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;
  String? _error;
  bool _showPassword = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Login')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              // Note removed: all roles can log in now
              const SizedBox(height: 8),
              TextFormField(
                controller: _username,
                decoration: const InputDecoration(labelText: 'Username/Email'),
                validator: (v) => v == null || v.isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _password,
                decoration: InputDecoration(
                  labelText: 'Password',
                  suffixIcon: IconButton(
                    icon: Icon(
                      _showPassword ? Icons.visibility_off : Icons.visibility,
                    ),
                    onPressed: () =>
                        setState(() => _showPassword = !_showPassword),
                  ),
                ),
                obscureText: !_showPassword,
                validator: (v) => v == null || v.isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 24),
              // Debug: show current API base URL to quickly verify config
              Text(
                'API: ${Env.apiBaseUrl}',
                style: const TextStyle(fontSize: 12, color: Colors.grey),
              ),
              const SizedBox(height: 8),
              if (_error != null)
                Text(_error!, style: const TextStyle(color: Colors.red)),
              ElevatedButton(
                onPressed: _loading ? null : _submit,
                child: _loading
                    ? const CircularProgressIndicator()
                    : const Text('Login'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final ok = await ref
          .read(authProvider.notifier)
          .login(_username.text.trim(), _password.text);
      if (ok && mounted) {
        // Optional: visual confirmation
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Login successful')));
        // Route by role when available, else go to home
        final profile = ref.read(authProvider).asData?.value;
        final roles =
            profile?.roles.map((r) => r.role.toLowerCase()).toList() ?? [];
        if (roles.contains('teacher')) {
          context.go('/teacher');
        } else if (roles.contains('principal')) {
          context.go('/principal');
        } else if (roles.contains('parent')) {
          context.go('/parent');
        } else {
          context.go('/');
        }
      } else if (!ok && mounted) {
        final authValue = ref.read(authProvider);
        final msg = authValue.hasError
            ? authValue.error.toString()
            : 'Login failed. Please check your credentials or network.';
        setState(() => _error = msg);
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(msg)));
      }
    } catch (e) {
      final msg = e.toString();
      setState(() => _error = msg);
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(msg)));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}
