import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

final GlobalKey<NavigatorState> rootNavigatorKey = GlobalKey<NavigatorState>();

// Global Riverpod container so non-widget classes (e.g. DioClient's
// interceptors) can read/invalidate providers such as authProvider when
// handling events like a 401 session expiry.
final ProviderContainer appProviderContainer = ProviderContainer();
