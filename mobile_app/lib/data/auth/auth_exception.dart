/// Distinguishes *why* a login attempt failed so the UI can show the right
/// guidance instead of a raw exception string (e.g. "DioException [bad
/// response]: ...").
enum AuthErrorType { invalidCredentials, noInternet, inactiveAccount, server }

class AuthException implements Exception {
  final AuthErrorType type;
  final String message;

  const AuthException(this.type, this.message);

  @override
  String toString() => message;
}
