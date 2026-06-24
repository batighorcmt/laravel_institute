import 'dart:io';
import 'package:dio/dio.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:path_provider/path_provider.dart';
import 'package:open_filex/open_filex.dart';
import '../config/env.dart';
import 'dart:developer' as developer;

class UpdateService {
  static final UpdateService _instance = UpdateService._internal();
  factory UpdateService() => _instance;
  UpdateService._internal();

  final Dio _dio = Dio();

  Future<Map<String, dynamic>?> checkForUpdate() async {
    try {
      final packageInfo = await PackageInfo.fromPlatform();
      final currentVersionCode = int.parse(packageInfo.buildNumber);

      final response = await _dio.get(
        '${Env.apiBaseUrl}app-update/check',
        queryParameters: {'version_code': currentVersionCode},
      );

      if (response.statusCode == 200 &&
          response.data['update_available'] == true) {
        return response.data;
      }
    } catch (e) {
      developer.log('Check for update error: $e');
    }
    return null;
  }

  Future<void> downloadAndInstall({
    required String url,
    required Function(double progress) onProgress,
    required Function(String error) onError,
  }) async {
    try {
      // 1. Request Storage Permissions
      if (Platform.isAndroid) {
        // On Android 13+ (SDK 33+), WRITE_EXTERNAL_STORAGE is deprecated.
        // We use getExternalStorageDirectory() which doesn't need it.
        // However, REQUEST_INSTALL_PACKAGES is needed for opening the APK.
      }

      final directory = await getExternalStorageDirectory();
      if (directory == null) {
        onError('Storage directory not found');
        return;
      }

      final String filePath = '${directory.path}/update.apk';

      // Delete existing file if exists
      final file = File(filePath);
      if (await file.exists()) {
        await file.delete();
      }

      // 2. Download APK
      await _dio.download(
        url,
        filePath,
        onReceiveProgress: (received, total) {
          if (total != -1) {
            onProgress(received / total);
          }
        },
      );

      // 3. Install APK
      final result = await OpenFilex.open(filePath);
      if (result.type != ResultType.done) {
        onError('Could not open APK: ${result.message}');
      }
    } catch (e) {
      developer.log('Download and install error: $e');
      onError(e.toString());
    }
  }
}
