class UserProfile {
  final int id;
  final String name;
  final List<UserRole> roles;

  UserProfile({required this.id, required this.name, required this.roles});

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    final rolesJson = (json['roles'] as List?) ?? [];
    final roles = rolesJson
        .map((e) => UserRole.fromJson(e as Map<String, dynamic>))
        .toList();
    return UserProfile(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String,
      roles: roles,
    );
  }
}

class UserRole {
  final String role; // principal | teacher | parent | super_admin
  final int? schoolId;
  final String? schoolName;

  UserRole({required this.role, this.schoolId, this.schoolName});

  factory UserRole.fromJson(Map<String, dynamic> json) => UserRole(
    role: json['role'] as String,
    schoolId: (json['school_id'] as num?)?.toInt(),
    schoolName: json['school_name'] as String?,
  );
}
