import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/auth/login_page.dart';
import '../features/home/home_page.dart';
import '../state/auth_state.dart';
import '../features/principal/principal_dashboard_page.dart';
import '../features/teacher/teacher_dashboard_page.dart';
import '../features/teacher/self_attendance_page.dart';
import '../features/parent/parent_dashboard_page.dart';

final GoRouter appRouter = GoRouter(
  routes: [
    GoRoute(
      path: '/',
      builder: (context, state) {
        final auth = ProviderScope.containerOf(context).read(authProvider);
        final profile = auth.asData?.value;
        if (profile == null) {
          return const LoginPage();
        }
        return HomePage(profile: profile);
      },
    ),
    GoRoute(path: '/login', builder: (context, state) => const LoginPage()),
    GoRoute(
      path: '/principal',
      builder: (context, state) => const PrincipalDashboardPage(),
    ),
    GoRoute(
      path: '/teacher',
      builder: (context, state) => const TeacherDashboardPage(),
    ),
    GoRoute(
      path: '/teacher/self-attendance',
      builder: (context, state) => const SelfAttendancePage(),
    ),
    GoRoute(
      path: '/parent',
      builder: (context, state) => const ParentDashboardPage(),
    ),
  ],
);
