import 'package:dio/dio.dart';

/// Converts any caught error into a short, plain-Bengali message safe to
/// show directly in the UI. Never leaks raw exception text, stack traces,
/// or file paths to the user.
String friendlyErrorMessage(Object? error) {
  if (error is DioException) {
    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return 'সার্ভারের সাড়া পেতে দেরি হচ্ছে। আবার চেষ্টা করুন।';
      case DioExceptionType.connectionError:
        return 'ইন্টারনেট সংযোগ পাওয়া যাচ্ছে না। সংযোগ পরীক্ষা করে আবার চেষ্টা করুন।';
      case DioExceptionType.badResponse:
        final status = error.response?.statusCode;
        final serverMsg = _extractServerMessage(error.response?.data);
        if (serverMsg != null) return serverMsg;
        if (status == 404) return 'তথ্য খুঁজে পাওয়া যায়নি।';
        if (status == 401) return 'সেশনের মেয়াদ শেষ হয়ে গেছে। আবার লগইন করুন।';
        if (status == 403) return 'এই তথ্য দেখার অনুমতি নেই।';
        if (status != null && status >= 500) {
          return 'সার্ভারে সাময়িক সমস্যা হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।';
        }
        return 'অনুরোধ সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।';
      default:
        return 'কিছু একটা সমস্যা হয়েছে। আবার চেষ্টা করুন।';
    }
  }
  return 'কিছু একটা সমস্যা হয়েছে। আবার চেষ্টা করুন।';
}

String? _extractServerMessage(dynamic data) {
  if (data is Map && data['message'] is String) {
    return data['message'] as String;
  }
  return null;
}
