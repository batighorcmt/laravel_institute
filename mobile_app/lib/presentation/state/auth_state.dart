import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/auth/auth_repository.dart';
import '../../domain/auth/user_profile.dart';

class AuthNotifier extends AsyncNotifier<UserProfile?> {
  @override
  Future<UserProfile?> build() async {
    try {
      final data = await AuthRepository().me();
      return UserProfile.fromJson(data);
    } catch (_) {
      return null;
    }
  }

  Future<bool> login(String username, String password) async {
    state = const AsyncLoading();
    try {
      await AuthRepository().login(
        username: username,
        password: password,
        deviceName: 'flutter-app',
      );
      final data = await AuthRepository().me();
      final profile = UserProfile.fromJson(data);
      state = AsyncData(profile);
      return true;
    } catch (e) {
      state = AsyncError(e, StackTrace.current);
      return false;
    }
  }

  Future<void> logout() async {
    await AuthRepository().logout();
    state = const AsyncData(null);
  }
}

final authProvider = AsyncNotifierProvider<AuthNotifier, UserProfile?>(
  AuthNotifier.new,
);
