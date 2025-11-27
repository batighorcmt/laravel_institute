স<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::middleware(['auth:sanctum','throttle:120,1'])->group(function () {
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
        Route::post('teacher/attendance/checkout', [\App\Http\Controllers\Api\TeacherAttendanceController::class, 'checkout'])->middleware('role:teacher');
        Route::get('teacher/attendance', [\App\Http\Controllers\Api\TeacherAttendanceController::class, 'index'])->middleware('role:teacher');
        Route::get('teacher/attendance/settings', [\App\Http\Controllers\Api\TeacherAttendanceSettingController::class, 'show'])->middleware('role:teacher');
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
    });
});
