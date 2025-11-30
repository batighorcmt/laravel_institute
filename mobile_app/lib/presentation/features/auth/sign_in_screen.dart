import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../state/auth_state.dart';
import '../../../widgets/gradient_scaffold.dart';
import '../../../widgets/app_snack.dart';

class SignInScreen extends ConsumerStatefulWidget {
  const SignInScreen({super.key});

  @override
  ConsumerState<SignInScreen> createState() => _SignInScreenState();
}

class _SignInScreenState extends ConsumerState<SignInScreen> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  final TextEditingController _usernameController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();

  bool _loading = false;
  // Removed inline error text; feedback via snackbars only.
  bool _showPassword = false;

  static const Color _primary = Color(0xFF00BF6D);

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
    });

    try {
      final ok = await ref
          .read(authProvider.notifier)
          .login(_usernameController.text.trim(), _passwordController.text);

      if (ok && mounted) {
        await showAppSnack(context, message: 'Login successful', success: true);
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
        await showAppSnack(context, message: msg, error: true);
      }
    } catch (e) {
      final msg = e.toString();
      if (mounted) {
        await showAppSnack(context, message: msg, error: true);
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    return GradientScaffold(
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 24.0),
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.95),
              borderRadius: BorderRadius.circular(24),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x20000000),
                  blurRadius: 16,
                  offset: Offset(0, 8),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Image.asset(
                  'assets/images/Batighor_EIMS.png',
                  height: 200,
                  errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                ),
                const SizedBox(height: 4),
                Text(
                  'Sign In',
                  style: textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF1A1D1F),
                  ),
                ),
                const SizedBox(height: 20),
                Form(
                  key: _formKey,
                  child: Column(
                    children: [
                      TextFormField(
                        controller: _usernameController,
                        decoration: const InputDecoration(
                          hintText: 'Email or Username',
                          filled: true,
                          fillColor: Color(0xFFF5FCF9),
                          contentPadding: EdgeInsets.symmetric(
                            horizontal: 24.0,
                            vertical: 16.0,
                          ),
                          border: OutlineInputBorder(
                            borderSide: BorderSide.none,
                            borderRadius: BorderRadius.all(Radius.circular(50)),
                          ),
                        ),
                        keyboardType: TextInputType.text,
                        validator: (v) {
                          if (v == null || v.trim().isEmpty) {
                            return 'Please enter email or username';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _passwordController,
                        obscureText: !_showPassword,
                        decoration: InputDecoration(
                          hintText: 'Password',
                          filled: true,
                          fillColor: const Color(0xFFF5FCF9),
                          contentPadding: const EdgeInsets.symmetric(
                            horizontal: 24.0,
                            vertical: 16.0,
                          ),
                          border: const OutlineInputBorder(
                            borderSide: BorderSide.none,
                            borderRadius: BorderRadius.all(Radius.circular(50)),
                          ),
                          suffixIcon: IconButton(
                            icon: Icon(
                              _showPassword
                                  ? Icons.visibility_off
                                  : Icons.visibility,
                              color: _primary.withValues(alpha: 0.7),
                            ),
                            onPressed: () =>
                                setState(() => _showPassword = !_showPassword),
                          ),
                        ),
                        validator: (v) {
                          if (v == null || v.isEmpty) {
                            return 'Please enter password';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 20),
                      SizedBox(
                        width: double.infinity,
                        height: 48,
                        child: ElevatedButton(
                          onPressed: _loading ? null : _submit,
                          child: const Text('Sign In'),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Align(
                        alignment: Alignment.centerRight,
                        child: TextButton(
                          onPressed: () {},
                          child: Text(
                            'Forgot Password?',
                            style: textTheme.bodyMedium?.copyWith(
                              color: const Color(0xFF667085),
                            ),
                          ),
                        ),
                      ),
                    ],
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
