class Env {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://institute.batighorbd.com/api/v1/',
  );
}
