# mobile_app

A new Flutter project.

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.

# Institute Mobile App (Flutter)

## Setup

1. Ensure Flutter SDK installed and `flutter doctor` passes.
2. Create platform configs for Firebase:
	- Android: add `android/app/google-services.json`
	- iOS: add `ios/Runner/GoogleService-Info.plist`
3. Set API base URL at build time (optional):
	- `flutter run --dart-define=API_BASE_URL=http://localhost:8000/api/v1`

## Packages
- dio, flutter_riverpod, go_router, firebase_core, firebase_messaging,
  permission_handler, geolocator, camera, image_picker, hive, hive_flutter,
  shared_preferences.

## Try It
```powershell
cd mobile_app
flutter pub get
flutter run --dart-define=API_BASE_URL=http://localhost:8000/api/v1
```
