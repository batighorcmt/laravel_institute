import 'dart:developer' as developer;
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/env.dart';

/// Resolves which API server the app should actually talk to this session.
///
/// [Env.apiBaseUrl] is compiled into the app and never changes without a
/// rebuild — it's used only once, here, to fetch a tiny public config
/// (piggy-backed on the existing `app-update/check` endpoint, which every
/// cold start already hits). A super admin can point `mobile_api_base_url`
/// at a different server from the web panel; that value wins from then on
/// and is cached, so every installed app picks up the change on its next
/// launch without an app-store update. Offline or first-run falls back to
/// the last cached value, then the compiled-in default.
class BootstrapService {
  static const _cacheKey = 'resolved_api_base_url';

  Future<String> resolveApiBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    final cached = prefs.getString(_cacheKey);

    try {
      final bootDio = Dio(
        BaseOptions(
          baseUrl: Env.apiBaseUrl,
          connectTimeout: const Duration(seconds: 5),
          receiveTimeout: const Duration(seconds: 5),
          headers: {'Accept': 'application/json'},
        ),
      );
      final resp = await bootDio.get(
        'app-update/check',
        queryParameters: {'version_code': 0},
      );
      final data = resp.data;
      final url = (data is Map ? data['api_base_url'] : null)?.toString();
      if (url != null && url.isNotEmpty) {
        if (url != cached) {
          await prefs.setString(_cacheKey, url);
          developer.log(
            'Resolved API base URL from server: $url',
            name: 'BootstrapService',
          );
        }
        return url;
      }
    } catch (e) {
      developer.log('Bootstrap fetch failed: $e', name: 'BootstrapService');
    }

    return cached ?? Env.apiBaseUrl;
  }
}
