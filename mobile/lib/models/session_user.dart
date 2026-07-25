class SessionUser {
  final int id;
  final String name;
  final String email;
  final List<String> roles;
  final List<String> permissions;

  SessionUser({
    required this.id,
    required this.name,
    required this.email,
    required this.roles,
    required this.permissions,
  });

  factory SessionUser.fromJson(Map<String, dynamic> json) {
    return SessionUser(
      id: json['id'],
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      roles: List<String>.from(json['roles'] ?? []),
      permissions: List<String>.from(json['permissions'] ?? []),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'roles': roles,
        'permissions': permissions,
      };

  bool can(String permission) => permissions.contains(permission);

  bool get isSuperAdmin => roles.contains('super-admin');
}
