import 'dart:convert';
import 'dart:developer' as developer;
import '../../core/config/env.dart';

class UserProfile {
  final int id;
  final String name;
  final List<UserRole> roles;
  final String? photoUrl;

  UserProfile({
    required this.id,
    required this.name,
    required this.roles,
    this.photoUrl,
  });

  /// Robust factory accepting potentially malformed structures.
  factory UserProfile.fromJson(Map<String, dynamic> json) {
    final rawRoles = json['roles'];
    developer.log(
      'UserProfile.fromJson rawRolesType=${rawRoles.runtimeType} value=$rawRoles',
      name: 'UserProfile',
    );
    List<dynamic> rolesList;
    if (rawRoles is List) {
      rolesList = rawRoles;
    } else if (rawRoles is Map) {
      // Some APIs may return roles keyed; collect map values.
      rolesList = rawRoles.values.toList();
    } else if (rawRoles is String) {
      try {
        final decoded = jsonDecode(rawRoles);
        if (decoded is List) {
          rolesList = decoded;
        } else {
          rolesList = [];
        }
      } catch (_) {
        rolesList = [];
      }
    } else {
      rolesList = [];
    }
    developer.log(
      'Normalized rolesList length=${rolesList.length} elementTypes=${rolesList.map((e) => e.runtimeType).join(',')}',
      name: 'UserProfile',
    );
    final roles = rolesList
        .where((e) => e is Map || e is String)
        .map((e) => UserRole.fromAny(e))
        .toList();
    String? photo;
    for (final key in const [
      'photo',
      'avatar',
      'image',
      'photo_url',
      'avatar_url',
      'profile_image',
      'profile_photo_url',
      'profilePhotoUrl',
    ]) {
      final v = json[key];
      if (v != null && v.toString().trim().isNotEmpty) {
        photo = v.toString();
        break;
      }
    }

    return UserProfile(
      id: (json['id'] as num).toInt(),
      name: (json['name'] ?? '').toString(),
      roles: roles,
      photoUrl: _absolutePhoto(photo),
    );
  }

  static UserProfile fromDynamic(dynamic raw) {
    if (raw is Map<String, dynamic>) return UserProfile.fromJson(raw);
    if (raw is Map) return UserProfile.fromJson(Map<String, dynamic>.from(raw));
    if (raw is String) {
      try {
        final decoded = jsonDecode(raw);
        if (decoded is Map<String, dynamic>) {
          return UserProfile.fromJson(decoded);
        }
        if (decoded is Map) {
          return UserProfile.fromJson(Map<String, dynamic>.from(decoded));
        }
      } catch (_) {}
    }
    // Fallback empty profile (id 0) to avoid crash
    return UserProfile(id: 0, name: '', roles: [], photoUrl: null);
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'roles': roles.map((r) => r.toJson()).toList(),
    'photo_url': photoUrl,
  };
}

String? _absolutePhoto(String? url) {
  if (url == null || url.trim().isEmpty) return null;
  final s = url.trim();
  if (s.startsWith('http://') || s.startsWith('https://')) return s;
  // Build origin from API base URL
  final base = Env.apiBaseUrl;
  final uri = Uri.tryParse(base);
  if (uri == null) return s;
  final origin = uri.hasScheme && uri.host.isNotEmpty
      ? '${uri.scheme}://${uri.host}${uri.hasPort ? ':${uri.port}' : ''}'
      : base;
  if (s.startsWith('/')) return '$origin$s';
  return '$origin/$s';
}

class UserRole {
  final String role; // principal | teacher | parent | super_admin
  final int? schoolId;
  final String? schoolName;

  UserRole({required this.role, this.schoolId, this.schoolName});

  factory UserRole.fromJson(Map<String, dynamic> json) => UserRole(
    role: (json['role'] ?? '').toString(),
    schoolId: (json['school_id'] is String)
        ? int.tryParse(json['school_id'])
        : (json['school_id'] as num?)?.toInt(),
    schoolName: (json['school_name'] ?? '') == ''
        ? null
        : json['school_name'].toString(),
  );

  static UserRole fromAny(dynamic raw) {
    if (raw is Map<String, dynamic>) return UserRole.fromJson(raw);
    if (raw is Map) return UserRole.fromJson(Map<String, dynamic>.from(raw));
    if (raw is String) {
      return UserRole(role: raw, schoolId: null, schoolName: null);
    }
    return UserRole(role: '', schoolId: null, schoolName: null);
  }

  Map<String, dynamic> toJson() => {
    'role': role,
    'school_id': schoolId,
    'school_name': schoolName,
  };
}
