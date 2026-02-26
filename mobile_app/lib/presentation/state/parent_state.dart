import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/parent/parent_repository.dart';

final parentRepositoryProvider = Provider((ref) => ParentRepository());

class SelectedStudentIdNotifier extends Notifier<int?> {
  @override
  int? build() => null;
  void update(int? id) => state = id;
}

final selectedStudentIdProvider = NotifierProvider<SelectedStudentIdNotifier, int?>(SelectedStudentIdNotifier.new);

class AttendanceMonthYearNotifier extends Notifier<DateTime> {
  @override
  DateTime build() => DateTime.now();
  set state(DateTime value) => super.state = value;
}

final attendanceMonthYearProvider = NotifierProvider<AttendanceMonthYearNotifier, DateTime>(AttendanceMonthYearNotifier.new);

final parentChildrenProvider = FutureProvider<List<dynamic>>((ref) async {
  final children = await ref.watch(parentRepositoryProvider).getChildren();
  if (children.isNotEmpty && ref.read(selectedStudentIdProvider) == null) {
    ref.read(selectedStudentIdProvider.notifier).update(children.first['id']);
  }
  return children;
});

class HomeworkDateFilterNotifier extends Notifier<DateTime?> {
  @override
  DateTime? build() => null;
  set state(DateTime? value) => super.state = value;
}
final homeworkDateFilterProvider = NotifierProvider<HomeworkDateFilterNotifier, DateTime?>(HomeworkDateFilterNotifier.new);

class HomeworkSubjectFilterNotifier extends Notifier<int?> {
  @override
  int? build() => null;
  set state(int? value) => super.state = value;
}
final homeworkSubjectFilterProvider = NotifierProvider<HomeworkSubjectFilterNotifier, int?>(HomeworkSubjectFilterNotifier.new);

class HomeworkTeacherFilterNotifier extends Notifier<int?> {
  @override
  int? build() => null;
  set state(int? value) => super.state = value;
}
final homeworkTeacherFilterProvider = NotifierProvider<HomeworkTeacherFilterNotifier, int?>(HomeworkTeacherFilterNotifier.new);

final parentHomeworkProvider = FutureProvider<List<dynamic>>((ref) {
  final studentId = ref.watch(selectedStudentIdProvider);
  final date = ref.watch(homeworkDateFilterProvider);
  final dateStr = date?.toIso8601String().split('T').first;
  return ref.watch(parentRepositoryProvider).getHomework(studentId: studentId, date: dateStr);
});

final parentRoutineProvider = FutureProvider<List<dynamic>>((ref) {
  final studentId = ref.watch(selectedStudentIdProvider);
  return ref.watch(parentRepositoryProvider).getRoutine(studentId: studentId);
});

final parentAttendanceProvider = FutureProvider<List<dynamic>>((ref) {
  final studentId = ref.watch(selectedStudentIdProvider);
  final dt = ref.watch(attendanceMonthYearProvider);
  return ref.watch(parentRepositoryProvider).getAttendance(
    studentId: studentId,
    month: dt.month,
    year: dt.year,
  );
});

final parentOverallAttendanceProvider = FutureProvider<List<dynamic>>((ref) {
  final studentId = ref.watch(selectedStudentIdProvider);
  return ref.watch(parentRepositoryProvider).getAttendance(studentId: studentId);
});

final parentEvaluationsProvider = FutureProvider<List<dynamic>>((ref) {
  final studentId = ref.watch(selectedStudentIdProvider);
  return ref.watch(parentRepositoryProvider).getLessonEvaluations(studentId: studentId);
});

final parentLeavesProvider = FutureProvider<List<dynamic>>((ref) {
  return ref.watch(parentRepositoryProvider).getLeaves();
});

final parentNoticesProvider = FutureProvider<List<dynamic>>((ref) {
  return ref.watch(parentRepositoryProvider).getNotices();
});

final parentTeachersProvider = FutureProvider<List<dynamic>>((ref) {
  final studentId = ref.watch(selectedStudentIdProvider);
  return ref.watch(parentRepositoryProvider).getTeachers(studentId: studentId);
});

final parentSubjectsProvider = FutureProvider<List<dynamic>>((ref) {
  final studentId = ref.watch(selectedStudentIdProvider);
  return ref.watch(parentRepositoryProvider).getSubjects(studentId: studentId);
});

final parentFeedbackProvider = FutureProvider<List<dynamic>>((ref) {
  return ref.watch(parentRepositoryProvider).getFeedback();
});
