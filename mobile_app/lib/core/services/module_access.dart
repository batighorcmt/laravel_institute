import 'dart:developer' as developer;
import '../network/dio_client.dart';

/// Which feature modules the current school has enabled — mirrors the
/// super admin's per-school module toggles (School::hasModule() on the
/// web) so the mobile dashboard/reports don't show a tile for a feature
/// that's been switched off.
///
/// Cached for the process lifetime once fetched successfully; a failed
/// fetch returns null (meaning "unknown") rather than an empty set, so
/// callers should treat null as "don't hide anything" — a transient
/// network error shouldn't hide features the school actually has access
/// to.
class ModuleAccess {
  static Set<String>? _cached;

  static Future<Set<String>?> fetch({bool forceRefresh = false}) async {
    if (_cached != null && !forceRefresh) return _cached;
    try {
      final resp = await DioClient().dio.get('meta/school');
      final data = resp.data;
      final raw = (data is Map && data['enabled_modules'] is List)
          ? List.from(data['enabled_modules'] as List)
          : null;
      if (raw == null) return _cached;
      _cached = raw.map((e) => e.toString()).toSet();
      return _cached;
    } catch (e) {
      developer.log('ModuleAccess fetch failed: $e', name: 'ModuleAccess');
      return _cached;
    }
  }

  /// True if [slug] is enabled, or module status is still unknown
  /// (fail-open — never hide a feature just because we couldn't check).
  static bool isOn(Set<String>? modules, String slug) {
    return modules == null || modules.contains(slug);
  }
}
