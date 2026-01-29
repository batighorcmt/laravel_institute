import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/auth/auth_repository.dart';
import '../../domain/auth/user_profile.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

class AuthNotifier extends AsyncNotifier<UserProfile?> {
  @override
  Future<UserProfile?> build() async {
    // Hydrate from cache first for resilience across external intents
    final cached = await _readCachedProfile();
    if (cached != null) {
      // Emit cached immediately
      state = AsyncData(cached);
    }
    try {
      final data = await AuthRepository().me();
      final profile = UserProfile.fromDynamic(data);
      await _writeCachedProfile(profile);
      return profile;
    } catch (_) {
      return cached; // fallback to cached if available
    }
  }

  Future<bool> login(String username, String password) async {
    state = const AsyncLoading();
    try {
      final loginData = await AuthRepository().login(
        username: username,
        password: password,
        deviceName: 'flutter-app',
      );
      // After successful login, always fetch the complete /me profile so
      // server-provided teacher fields (designation/photo) are included.
      final data = await AuthRepository().me();
      final profile = UserProfile.fromDynamic(data);
      state = AsyncData(profile);
      await _writeCachedProfile(profile);
      return true;
    } catch (e) {
      state = AsyncError(e, StackTrace.current);
      return false;
    }
  }

  Future<void> logout() async {
    await AuthRepository().logout();
    state = const AsyncData(null);
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_cacheKey);
  }
}

final authProvider = AsyncNotifierProvider<AuthNotifier, UserProfile?>(
  AuthNotifier.new,
);

const _cacheKey = 'auth_profile_cache_v1';

Future<UserProfile?> _readCachedProfile() async {
  try {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_cacheKey);
    if (raw == null) return null;
    final map = jsonDecode(raw);
    return UserProfile.fromDynamic(map);
  } catch (_) {
    return null;
  }
}

Future<void> _writeCachedProfile(UserProfile profile) async {
  try {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_cacheKey, jsonEncode(profile.toJson()));
  } catch (_) {}
}
