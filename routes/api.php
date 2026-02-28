<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::middleware(['auth:sanctum','throttle:120,1'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);
    Route::get('me', [AuthController::class, 'me']);

    // Notices (public to authenticated users; create restricted to principal)
    Route::get('notices', [\App\Http\Controllers\Api\NoticeController::class, 'index']);
    Route::post('notices', [\App\Http\Controllers\Api\NoticeController::class, 'store'])->middleware('role:principal');

    // Principal reports
    Route::get('principal/reports/attendance-summary', [\App\Http\Controllers\Api\PrincipalReportController::class, 'attendanceSummary'])->middleware('role:principal');
    Route::get('principal/reports/attendance-details', [\App\Http\Controllers\Api\PrincipalReportController::class, 'attendanceDetails'])->middleware('role:principal');
    Route::get('principal/reports/lesson-evaluations', [\App\Http\Controllers\Api\PrincipalReportController::class, 'lessonEvaluations'])->middleware('role:principal');
    Route::get('principal/reports/lesson-evaluations/{id}', [\App\Http\Controllers\Api\PrincipalReportController::class, 'lessonEvaluationDetail'])->middleware('role:principal');
    Route::get('principal/reports/exam-results-summary', [\App\Http\Controllers\Api\PrincipalReportController::class, 'examResultsSummary'])->middleware('role:principal');
    Route::get('principal/reports/homework-summary', [\App\Http\Controllers\Api\PrincipalReportController::class, 'homeworkSummary'])->middleware('role:principal');
    Route::get('principal/reports/homework-details', [\App\Http\Controllers\Api\PrincipalReportController::class, 'homeworkDetails'])->middleware('role:principal');
    Route::get('principal/reports/leaves-summary', [\App\Http\Controllers\Api\PrincipalReportController::class, 'leavesSummary'])->middleware('role:principal');
    Route::get('principal/reports/leaves-details', [\App\Http\Controllers\Api\PrincipalReportController::class, 'leavesDetails'])->middleware('role:principal');
    Route::get('principal/reports/students-attendance', [\App\Http\Controllers\Api\PrincipalReportController::class, 'studentsAttendance'])->middleware('role:principal');
    Route::get('principal/reports/students-attendance/details', [\App\Http\Controllers\Api\PrincipalReportController::class, 'studentsAttendanceDetails'])->middleware('role:principal');
    Route::get('principal/reports/students-attendance/summary', [\App\Http\Controllers\Api\PrincipalReportController::class, 'studentsAttendanceSummary'])->middleware('role:principal');

    // Teacher attendance & academic actions
    Route::post('teacher/attendance', [\App\Http\Controllers\Api\TeacherAttendanceController::class, 'store'])->middleware('role:teacher');
    Route::post('teacher/attendance/checkout', [\App\Http\Controllers\Api\TeacherAttendanceController::class, 'checkout'])->middleware('role:teacher');
    Route::get('teacher/attendance', [\App\Http\Controllers\Api\TeacherAttendanceController::class, 'index'])->middleware('role:teacher');
    Route::get('teacher/attendance/settings', [\App\Http\Controllers\Api\TeacherAttendanceSettingController::class, 'show'])->middleware('role:teacher');
    // Teacher → Students Attendance (meta + lists)
    Route::get('teacher/students-attendance/modules', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class, 'modules'])->middleware('role:teacher');
    Route::get('teacher/students-attendance/class/meta', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class, 'classMeta'])->middleware('role:teacher');
    Route::get('teacher/students-attendance/extra/meta', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class, 'extraMeta'])->middleware('role:teacher');
    Route::get('teacher/students-attendance/team/meta', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class, 'teamMeta'])->middleware('role:teacher');
    // Teacher → Students Attendance (class section students + submit)
    Route::get('teacher/students-attendance/class/sections/{section}/students', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class, 'classSectionStudents'])->middleware('role:teacher');
    Route::post('teacher/students-attendance/class/sections/{section}/attendance', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class, 'classSectionSubmit'])->middleware('role:teacher');
    // Teacher → Students Attendance (extra class students + submit)
    Route::get('teacher/students-attendance/extra/classes/{extraClass}/students', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class, 'extraClassStudents'])->middleware('role:teacher');
    Route::post('teacher/students-attendance/extra/classes/{extraClass}/attendance', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class, 'extraClassSubmit'])->middleware('role:teacher');
    Route::get('teacher/homework', [\App\Http\Controllers\Api\HomeworkController::class, 'index'])->middleware('role:teacher');
    Route::post('teacher/homework', [\App\Http\Controllers\Api\HomeworkController::class, 'store'])->middleware('role:teacher');
    Route::match(['put', 'patch'], 'teacher/homework/{homework}', [\App\Http\Controllers\Api\HomeworkController::class, 'update'])->middleware('role:teacher');
    Route::delete('teacher/homework/{homework}', [\App\Http\Controllers\Api\HomeworkController::class, 'destroy'])->middleware('role:teacher');
    // Teacher leaves
    Route::get('teacher/leaves', [\App\Http\Controllers\Api\TeacherLeaveController::class, 'index'])->middleware('role:teacher');
    Route::post('teacher/leaves', [\App\Http\Controllers\Api\TeacherLeaveController::class, 'store'])->middleware('role:teacher');
    // Teacher directory (teacher + principal access)
    Route::get('teachers', [\App\Http\Controllers\Api\TeacherDirectoryController::class, 'index'])->middleware('role:teacher,principal');
    // Teacher → Students directory and profile
    Route::get('teacher/students', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'index'])->middleware('role:teacher');
    Route::get('teacher/students/meta', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'meta'])->middleware('role:teacher');
    Route::get('teacher/students/filters/classes', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'getClasses'])->middleware('role:teacher');
    Route::get('teacher/students/filters/sections', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'getSections'])->middleware('role:teacher');
    Route::get('teacher/students/filters/groups', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'getGroups'])->middleware('role:teacher');
    Route::get('teacher/students/{student}', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'show'])->middleware('role:teacher');
    Route::get('teacher/subjects', [\App\Http\Controllers\Api\TeacherSubjectController::class, 'forClassSection'])->middleware('role:teacher');
    Route::get('teacher/lesson-evaluations', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'index'])->middleware('role:teacher');
    Route::post('teacher/lesson-evaluations', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'store'])->middleware('role:teacher');
    Route::get('teacher/lesson-evaluations/today-routine', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'todayRoutine'])->middleware('role:teacher');
    Route::get('teacher/lesson-evaluations/form', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'form'])->middleware('role:teacher');

    // Teacher → Exams
    Route::prefix('teacher/exams')->middleware('role:teacher')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\TeacherExamController::class, 'index']);
        Route::get('/todays-duty', [\App\Http\Controllers\Api\TeacherExamController::class, 'todaysDuty']);
        Route::get('/duty-meta', [\App\Http\Controllers\Api\TeacherExamController::class, 'dutyMeta']);
        Route::get('/find-seat', [\App\Http\Controllers\Api\TeacherExamController::class, 'findSeat']);
        Route::get('/mark-entry/meta', [\App\Http\Controllers\Api\TeacherExamController::class, 'markEntryMeta']);
        Route::get('/mark-entry/exams', [\App\Http\Controllers\Api\TeacherExamController::class, 'getExams']);
        Route::get('/mark-entry/subjects', [\App\Http\Controllers\Api\TeacherExamController::class, 'getSubjects']);
        Route::get('/mark-entry/students', [\App\Http\Controllers\Api\TeacherExamController::class, 'getStudents']);
        Route::post('/mark-entry/save-mark', [\App\Http\Controllers\Api\TeacherExamController::class, 'saveMark']);
        Route::get('/attendance-report', [\App\Http\Controllers\Api\TeacherExamController::class, 'attendanceReport']);
        Route::get('/room-attendance', [\App\Http\Controllers\Api\TeacherExamController::class, 'roomAttendanceStudents']);
        Route::post('/submit-room-attendance', [\App\Http\Controllers\Api\TeacherExamController::class, 'submitRoomAttendance']);
        Route::post('/bulk-submit-room-attendance', [\App\Http\Controllers\Api\TeacherExamController::class, 'bulkSubmitRoomAttendance']);
        
        // Duty Allocation
        Route::get('/teachers', [\App\Http\Controllers\Api\TeacherExamController::class, 'teachersList']);
        Route::post('/assign-duty', [\App\Http\Controllers\Api\TeacherExamController::class, 'assignDuty']);
    });

    // Parent endpoints
    Route::get('parent/children', [\App\Http\Controllers\Api\ParentController::class, 'children'])->middleware('role:parent');
    Route::get('parent/homework', [\App\Http\Controllers\Api\ParentController::class, 'homework'])->middleware('role:parent');
    Route::get('parent/attendance', [\App\Http\Controllers\Api\ParentController::class, 'attendance'])->middleware('role:parent');

    Route::get('parent/leaves', [\App\Http\Controllers\Api\ParentController::class, 'leavesIndex'])->middleware('role:parent');
    Route::post('parent/leaves', [\App\Http\Controllers\Api\ParentController::class, 'leavesStore'])->middleware('role:parent');
    Route::get('parent/profile', [\App\Http\Controllers\Api\ParentController::class, 'profile'])->middleware('role:parent');
    Route::get('parent/subjects', [\App\Http\Controllers\Api\ParentController::class, 'subjects'])->middleware('role:parent');
    Route::get('parent/routine', [\App\Http\Controllers\Api\ParentController::class, 'classRoutine'])->middleware('role:parent');

    Route::get('parent/lesson-evaluations', [\App\Http\Controllers\Api\ParentController::class, 'lessonEvaluations'])->middleware('role:parent');
    Route::get('parent/lesson-evaluation-stats', [\App\Http\Controllers\Api\ParentController::class, 'lessonEvaluationStats'])->middleware('role:parent');
    Route::get('parent/teachers', [\App\Http\Controllers\Api\ParentController::class, 'teachers'])->middleware('role:parent');
    Route::get('parent/feedback', [\App\Http\Controllers\Api\ParentController::class, 'feedbackIndex'])->middleware('role:parent');
    Route::post('parent/feedback', [\App\Http\Controllers\Api\ParentController::class, 'feedbackStore'])->middleware('role:parent');
    Route::post('parent/update-photo', [\App\Http\Controllers\Api\ParentController::class, 'updatePhoto'])->middleware('role:parent');
    Route::post('parent/change-password', [\App\Http\Controllers\Api\ParentController::class, 'changePassword'])->middleware('role:parent');
    // Device token endpoints
    Route::post('devices', [\App\Http\Controllers\Api\DeviceTokenController::class, 'store']);
    Route::delete('devices/{deviceToken}', [\App\Http\Controllers\Api\DeviceTokenController::class, 'destroy']);

    // Test push (principal only)
    Route::post('notifications/test', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        if (! $user->isPrincipal($request->attributes->get('current_school_id')) && ! $user->isSuperAdmin()) {
        return response()->json(['message' => 'অননুমোদিত'], 403);
        }
        $tokens = \App\Models\DeviceToken::where('user_id',$user->id)->pluck('token')->toArray();
        \App\Jobs\SendPushNotificationJob::dispatch($tokens,'টেস্ট নোটিফিকেশন','এটি একটি পরীক্ষামূলক বার্তা',['type'=>'test']);
        return response()->json(['message' => 'Push জব কিউ হয়েছে','count' => count($tokens)]);
    });

    // Billing endpoints (v1)
    Route::prefix('billing')->group(function () {
        Route::post('/payments', [\App\Http\Controllers\Billing\PaymentController::class, 'store']);
        Route::get('/receipts/{id}', [\App\Http\Controllers\Billing\ReceiptController::class, 'show']);
            Route::get('/students/{student}/due', [\App\Http\Controllers\Billing\DueController::class, 'show']);
            Route::get('/students/{student}/statement', [\App\Http\Controllers\Billing\StatementController::class, 'monthly']);
    });

    // Principal student management endpoints
    Route::prefix('principal')->middleware('role:principal')->group(function () {
        Route::get('students/search', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'search']);
        Route::get('students/filters/classes', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'getClasses']);
        Route::get('students/filters/sections', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'getSections']);
        Route::get('students/filters/groups', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'getGroups']);
        Route::get('students/filters/subjects', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'getSubjects']);
    });

    // Meta endpoints
    Route::get('meta/teachers', [\App\Http\Controllers\Api\SchoolMetaController::class, 'teachers']);
    });
});
