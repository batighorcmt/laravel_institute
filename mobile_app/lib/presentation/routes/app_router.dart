import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/auth/login_page.dart';
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
                int.tryParse(state.pathParameters['extraClassId'] ?? '0') ?? 0;
            final title =
                state.uri.queryParameters['title'] ?? 'Extra Class Attendance';
            return ExtraClassMarkAttendancePage(extraClassId: id, title: title);
          },
        ),
      ],
    ),
    GoRoute(
      path: '/teacher/students-attendance/team',
      builder: (context, state) => const TeamsListPage(),
    ),
    GoRoute(
      path: '/parent',
      builder: (context, state) => const ParentDashboardPage(),
    ),
  ],
);
