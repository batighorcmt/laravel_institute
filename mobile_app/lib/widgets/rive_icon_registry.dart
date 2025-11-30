class RiveIconRegistry {
  static const Map<String, String> artboards = {
    'self_attendance': 'SelfAttendance',
    'students_attendance': 'StudentsAttendance',
    'lesson_evaluation': 'LessonEvaluation',
    'homework': 'Homework',
    'manage_leave': 'ManageLeave',
    'teachers': 'Teachers',
    'students': 'Students',
    'attendance': 'Attendance',
    'exam_results': 'ExamResults',
    'children': 'Children',
    'parent_homework': 'ParentHomework',
  };

  static String? artboardFor(String key) => artboards[key];
}
