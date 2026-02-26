import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/auth/sign_in_screen.dart';
import '../features/splash/splash_screen.dart';
import '../features/home/home_page.dart';
import '../state/auth_state.dart';
import '../features/principal/principal_dashboard_page.dart';
import '../features/teacher/teacher_dashboard_page.dart';
import '../features/teacher/self_attendance_page.dart';
import '../features/teacher/students_attendance_menu_page.dart';
import '../features/teacher/class_sections_list_page.dart';
import '../features/teacher/extra_classes_list_page.dart';
import '../features/teacher/teams_list_page.dart';
import '../features/teacher/extra_class_mark_attendance_page.dart';
import '../features/teacher/mark_attendance_page.dart';
import '../features/parent/parent_shell_page.dart';
import '../features/parent/parent_dashboard_page.dart';
import '../features/parent/pages/profile_page.dart';
import '../features/parent/pages/subjects_page.dart';
import '../features/parent/pages/class_routine_page.dart';
import '../features/parent/pages/homework_list_page.dart';
import '../features/parent/pages/attendance_report_page.dart';
import '../features/parent/pages/lesson_evaluation_page.dart';
import '../features/parent/pages/leave_application_page.dart';
import '../features/parent/pages/notice_board_page.dart';
import '../features/parent/pages/teacher_list_page.dart';
import '../features/parent/pages/feedback_page.dart';
import '../features/parent/pages/my_child_details_page.dart';

// Helper to refresh GoRouter when auth state changes.
class GoRouterRefreshNotifier extends ChangeNotifier {
  GoRouterRefreshNotifier(this.ref) {
    // Listen to changes in authProvider and trigger router refresh
    _subscription = ref.listen<AsyncValue<dynamic>>(authProvider, (prev, next) {
      notifyListeners();
    });
  }

  final Ref ref;
  late final ProviderSubscription<AsyncValue<dynamic>> _subscription;

  @override
  void dispose() {
    _subscription.close();
    super.dispose();
  }
}

final appRouterProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    refreshListenable: GoRouterRefreshNotifier(ref),
    initialLocation: '/splash',
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/',
        builder: (context, state) {
          final profile = ref.read(authProvider).asData?.value;
          return profile != null
              ? HomePage(profile: profile)
              : const SignInScreen();
        },
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const SignInScreen(),
      ),
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
        path: '/teacher/students-attendance',
        builder: (context, state) => const StudentsAttendanceMenuPage(),
      ),
      GoRoute(
        path: '/teacher/students-attendance/class',
        name: 'teacher-class-sections',
        builder: (context, state) => const ClassSectionsListPage(),
        routes: [
          GoRoute(
            path: 'sections/:sectionId/mark',
            name: 'teacher-class-section-mark',
            builder: (context, state) {
              final sectionId =
                  int.tryParse(state.pathParameters['sectionId'] ?? '0') ?? 0;
              final title =
                  state.uri.queryParameters['title'] ?? 'Class Attendance';
              return ClassSectionMarkAttendancePage(
                sectionId: sectionId,
                title: title,
              );
            },
          ),
        ],
      ),
      GoRoute(
        path: '/teacher/students-attendance/extra',
        builder: (context, state) => const ExtraClassesListPage(),
        routes: [
          GoRoute(
            path: 'classes/:extraClassId/mark',
            name: 'teacher-extra-class-mark',
            builder: (context, state) {
              final id =
                  int.tryParse(state.pathParameters['extraClassId'] ?? '0') ??
                  0;
              final title =
                  state.uri.queryParameters['title'] ??
                  'Extra Class Attendance';
              return ExtraClassMarkAttendancePage(
                extraClassId: id,
                title: title,
              );
            },
          ),
        ],
      ),
      GoRoute(
        path: '/teacher/students-attendance/team',
        builder: (context, state) => const TeamsListPage(),
      ),

      // ── Parent Shell with sidebar ──
      ShellRoute(
        builder: (context, state, child) => ParentShellPage(child: child),
        routes: [
          GoRoute(
            path: '/parent',
            name: 'parent-home',
            redirect: (_, __) => '/parent/dashboard',
          ),
          GoRoute(
            path: '/parent/dashboard',
            name: 'parent-dashboard',
            builder: (context, state) => const ParentDashboardPage(),
          ),
          GoRoute(
            path: '/parent/profile',
            name: 'parent-profile',
            builder: (context, state) => const ProfilePage(),
          ),
          GoRoute(
            path: '/parent/my-child',
            name: 'parent-my-child',
            builder: (context, state) => const MyChildDetailsPage(),
          ),
          GoRoute(
            path: '/parent/subjects',
            name: 'parent-subjects',
            builder: (context, state) => const SubjectsPage(),
          ),
          GoRoute(
            path: '/parent/routine',
            name: 'parent-routine',
            builder: (context, state) => const ClassRoutinePage(),
          ),
          GoRoute(
            path: '/parent/homework',
            name: 'parent-homework',
            builder: (context, state) => const HomeworkListPage(),
          ),
          GoRoute(
            path: '/parent/attendance',
            name: 'parent-attendance',
            builder: (context, state) => const AttendanceReportPage(),
          ),
          GoRoute(
            path: '/parent/evaluations',
            name: 'parent-evaluations',
            builder: (context, state) => const LessonEvaluationPage(),
          ),
          GoRoute(
            path: '/parent/leaves',
            name: 'parent-leaves',
            builder: (context, state) => const LeaveApplicationPage(),
          ),
          GoRoute(
            path: '/parent/notices',
            name: 'parent-notices',
            builder: (context, state) => const NoticeBoardPage(),
          ),
          GoRoute(
            path: '/parent/teachers',
            name: 'parent-teachers',
            builder: (context, state) => const TeacherListPage(),
          ),
          GoRoute(
            path: '/parent/feedback',
            name: 'parent-feedback',
            builder: (context, state) => const FeedbackPage(),
          ),
        ],
      ),
    ],
    redirect: (context, state) {
      final loggedIn = ref.read(authProvider).asData?.value != null;
      final loggingIn = state.matchedLocation == '/login';
      final onSplash = state.matchedLocation == '/splash';

      // If not logged in: allow splash/login and teacher flows to continue (camera intents may transiently reset state)
      if (!loggedIn) {
        final onTeacherFlow = state.matchedLocation.startsWith('/teacher');
        if (loggingIn || onSplash || onTeacherFlow) return null;
        return '/login';
      }

      // If logged in and on the login or splash page, redirect to the appropriate dashboard
      if (loggingIn || onSplash) {
        final profile = ref.read(authProvider).asData?.value;
        final roles =
            profile?.roles.map((r) => r.role.toLowerCase()).toList() ?? [];
        // Prefer principal dashboard when user has both principal and teacher roles
        if (roles.contains('principal')) return '/principal';
        if (roles.contains('teacher')) return '/teacher';
        if (roles.contains('parent')) return '/parent/dashboard';
        return '/'; // Fallback to home
      }

      return null; // No redirect needed
    },
  );
});
