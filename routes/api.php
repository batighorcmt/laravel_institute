<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // Notices (public to authenticated users; create restricted to principal)
        Route::get('notices', [\App\Http\Controllers\Api\NoticeController::class, 'index']);
        Route::post('notices', [\App\Http\Controllers\Api\NoticeController::class, 'store'])->middleware('role:principal');

        // Principal reports
        Route::get('principal/reports/attendance-summary', [\App\Http\Controllers\Api\PrincipalReportController::class, 'attendanceSummary'])->middleware('role:principal');
        Route::get('principal/reports/exam-results-summary', [\App\Http\Controllers\Api\PrincipalReportController::class, 'examResultsSummary'])->middleware('role:principal');

        // Teacher attendance & academic actions
        Route::post('teacher/attendance', [\App\Http\Controllers\Api\TeacherAttendanceController::class, 'store'])->middleware('role:teacher');
        Route::get('teacher/attendance', [\App\Http\Controllers\Api\TeacherAttendanceController::class, 'index'])->middleware('role:teacher');
        Route::get('teacher/homework', [\App\Http\Controllers\Api\HomeworkController::class, 'index'])->middleware('role:teacher');
        Route::post('teacher/homework', [\App\Http\Controllers\Api\HomeworkController::class, 'store'])->middleware('role:teacher');
        Route::get('teacher/lesson-evaluations', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'index'])->middleware('role:teacher');
        Route::post('teacher/lesson-evaluations', [\App\Http\Controllers\Api\LessonEvaluationController::class, 'store'])->middleware('role:teacher');

        // Parent endpoints
        Route::get('parent/children', [\App\Http\Controllers\Api\ParentController::class, 'children'])->middleware('role:parent');
        Route::get('parent/homework', [\App\Http\Controllers\Api\ParentController::class, 'homework'])->middleware('role:parent');
        Route::get('parent/attendance', [\App\Http\Controllers\Api\ParentController::class, 'attendance'])->middleware('role:parent');
        Route::get('parent/exam-results', [\App\Http\Controllers\Api\ParentController::class, 'examResults'])->middleware('role:parent');
        Route::get('parent/leaves', [\App\Http\Controllers\Api\ParentController::class, 'leavesIndex'])->middleware('role:parent');
        Route::post('parent/leaves', [\App\Http\Controllers\Api\ParentController::class, 'leavesStore'])->middleware('role:parent');
    });
});
