import 'dart:io';
import 'package:dio/dio.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:path_provider/path_provider.dart';
import 'package:open_filex/open_filex.dart';
import 'package:permission_handler/permission_handler.dart';
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
      // 1. Request the "install unknown apps" permission before downloading —
      // no point pulling a multi-MB APK if the user is going to deny this.
      // On Android this is a per-app special permission (Settings-gated on
      // Android 8+); permission_handler surfaces it as a normal
      // request()/status check and opens the system settings screen itself
      // when needed.
      if (Platform.isAndroid) {
        var status = await Permission.requestInstallPackages.status;
        if (!status.isGranted) {
          status = await Permission.requestInstallPackages.request();
        }
        if (!status.isGranted) {
          onError(
            'অ্যাপ ইনস্টল করার অনুমতি প্রয়োজন। সেটিংস থেকে "Install unknown apps" অনুমতি দিয়ে আবার চেষ্টা করুন।',
          );
          return;
        }
      }

      final directory = await getExternalStorageDirectory();
      if (directory == null) {
        onError('ডিভাইসে স্টোরেজ পাওয়া যায়নি।');
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

      // 3. Install APK — this hands off to the Android system package
      // installer UI. A single confirmation tap there is unavoidable on
      // stock Android (no app can silently install another APK without
      // device-owner/system privileges); everything up to that point is
      // now fully automatic.
      final result = await OpenFilex.open(filePath);
      if (result.type == ResultType.permissionDenied) {
        onError(
          'অ্যাপ ইনস্টল করার অনুমতি প্রয়োজন। সেটিংস থেকে "Install unknown apps" অনুমতি দিয়ে আবার চেষ্টা করুন।',
        );
      } else if (result.type != ResultType.done) {
        onError('আপডেট ইনস্টল করা যায়নি: ${result.message}');
      }
    } catch (e) {
      developer.log('Download and install error: $e');
      onError('ডাউনলোড ব্যর্থ হয়েছে: $e');
    }
  }
}
