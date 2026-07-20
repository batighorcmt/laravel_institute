class Env {
  // Temporarily defaulted to the local XAMPP dev server per current task —
  // switch back to the production URL before shipping a release build.
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://localhost/laravel_institute/public/api/v1/',
  );
}
