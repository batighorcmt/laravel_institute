<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('auth/login', [AuthController::class, 'login']);

    // Debug helpers (no auth) - quick verification endpoints
    Route::get('debug/classes', [\App\Http\Controllers\Api\DebugController::class, 'classes']);
    Route::get('debug/sections', [\App\Http\Controllers\Api\DebugController::class, 'sections']);
    Route::get('debug/subjects', [\App\Http\Controllers\Api\DebugController::class, 'subjects']);
    Route::get('debug/teachers', [\App\Http\Controllers\Api\DebugController::class, 'teachers']);

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
    Route::get('principal/reports/exam-results-summary', [\App\Http\Controllers\Api\PrincipalReportController::class, 'examResultsSummary'])->middleware('role:principal');

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
    // Teacher leaves
    Route::get('teacher/leaves', [\App\Http\Controllers\Api\TeacherLeaveController::class, 'index'])->middleware('role:teacher');
    Route::post('teacher/leaves', [\App\Http\Controllers\Api\TeacherLeaveController::class, 'store'])->middleware('role:teacher');
    // Teacher directory (teacher + principal access)
    Route::get('teachers', [\App\Http\Controllers\Api\TeacherDirectoryController::class, 'index'])->middleware('role:teacher,principal');
    // Teacher → Students directory and profile
    Route::get('teacher/students', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'index'])->middleware('role:teacher');
    Route::get('teacher/students/{student}', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'show'])->middleware('role:teacher');
    Route::get('teacher/students/meta', [\App\Http\Controllers\Api\StudentDirectoryController::class, 'meta'])->middleware('role:teacher');
    Route::get('teacher/subjects', [\App\Http\Controllers\Api\TeacherSubjectController::class, 'forClassSection'])->middleware('role:teacher');
    Route::get('teacher/lesson-evaluations', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'index'])->middleware('role:teacher');
    Route::post('teacher/lesson-evaluations', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'store'])->middleware('role:teacher');
    Route::get('teacher/lesson-evaluations/today-routine', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'todayRoutine'])->middleware('role:teacher');
    Route::get('teacher/lesson-evaluations/form', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'form'])->middleware('role:teacher');

    // Parent endpoints
    Route::get('parent/children', [\App\Http\Controllers\Api\ParentController::class, 'children'])->middleware('role:parent');
    Route::get('parent/homework', [\App\Http\Controllers\Api\ParentController::class, 'homework'])->middleware('role:parent');
    Route::get('parent/attendance', [\App\Http\Controllers\Api\ParentController::class, 'attendance'])->middleware('role:parent');
    Route::get('parent/exam-results', [\App\Http\Controllers\Api\ParentController::class, 'examResults'])->middleware('role:parent');
    Route::get('parent/leaves', [\App\Http\Controllers\Api\ParentController::class, 'leavesIndex'])->middleware('role:parent');
    Route::post('parent/leaves', [\App\Http\Controllers\Api\ParentController::class, 'leavesStore'])->middleware('role:parent');
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
        Route::get('students/filters/subjects', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'getSubjects']);
        Route::get('students/filters/groups', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'getGroups']);
    });

    // Lesson evaluation JSON endpoints for mobile (principal only)
    Route::get('principal/reports/lesson-evaluations/{id}', [\App\Http\Controllers\Principal\LessonEvaluationReportController::class, 'apiShow'])->middleware('role:principal');
    Route::get('principal/reports/lesson-evaluations/details', [\App\Http\Controllers\Principal\LessonEvaluationReportController::class, 'details'])->middleware('role:principal');

    // Generic school metadata endpoints (classes, sections, teachers)
    Route::prefix('meta')->middleware('role:teacher,principal')->group(function () {
        Route::get('classes', [\App\Http\Controllers\Api\SchoolMetaController::class, 'classes']);
        Route::get('sections', [\App\Http\Controllers\Api\SchoolMetaController::class, 'sections']);
        Route::get('teachers', [\App\Http\Controllers\Api\SchoolMetaController::class, 'teachers']);
    });
    // Also expose the same endpoints to teachers so they can fetch
    // full DB-backed class/section/group lists when allowed.
    Route::prefix('teacher')->middleware('role:teacher')->group(function () {
        Route::get('students/search', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'search']);
        Route::get('students/filters/classes', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'getClasses']);
        Route::get('students/filters/sections', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'getSections']);
        Route::get('students/filters/groups', [\App\Http\Controllers\Api\PrincipalStudentController::class, 'getGroups']);
    });
    });
});
