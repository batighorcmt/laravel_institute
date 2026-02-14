<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    // Final Surgical Live Database Repair (Delete after use)
    Route::get('/run-migrations-system-secure-{key}', function ($key) {
        if ($key !== 'halim2025') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $results = [];
        try {
            // 1. Ensure migrations table exists
            if (!\Illuminate\Support\Facades\Schema::hasTable('migrations')) {
                \Illuminate\Support\Facades\Artisan::call('migrate:install');
                $results[] = 'Migrations table initialized';
            }

            // 2. Identify migrations that are blocking because tables already exist
            $existingTablesToMigrations = [
                'users' => '0001_01_01_000000_create_users_table',
                'password_reset_tokens' => '0001_01_01_000000_create_users_table',
                'sessions' => '0001_01_01_000000_create_users_table',
                'schools' => '2025_11_07_204717_create_schools_table',
                'roles' => '2025_11_07_204728_create_roles_table',
                'user_school_roles' => '2025_11_07_204735_create_user_school_roles_table',
                'classes' => '2025_11_07_204741_create_classes_table',
                'subjects' => '2025_11_07_204748_create_subjects_table',
                'students' => '2025_11_07_204754_create_students_table',
            ];

            foreach ($existingTablesToMigrations as $table => $migration) {
                if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                    \Illuminate\Support\Facades\DB::table('migrations')->updateOrInsert(
                        ['migration' => $migration],
                        ['batch' => 1]
                    );
                    $results[] = "Marked migration '$migration' as run (table '$table' exists)";
                }
            }

            // 3. Run all pending migrations (This will create 'teachers' and everything else missing)
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $results[] = 'Migrations executed: ' . \Illuminate\Support\Facades\Artisan::output();
            } catch (\Exception $e) {
                $results[] = 'Migration warning: ' . $e->getMessage();
            }

            // 4. Data Fix: Ensure username column and population
            \Illuminate\Support\Facades\Schema::table('users', function ($table) use (&$results) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'username')) {
                    $table->string('username')->nullable()->unique()->after('name');
                    $results[] = 'Manually added missing username column';
                }
            });

            // Populate usernames for any teacher records found
            if (\Illuminate\Support\Facades\Schema::hasTable('teachers')) {
                $teachers = \Illuminate\Support\Facades\DB::table('teachers')
                    ->join('users', 'teachers.user_id', '=', 'users.id')
                    ->join('schools', 'teachers.school_id', '=', 'schools.id')
                    ->whereNull('users.username')
                    ->select('teachers.id', 'schools.code as school_code', 'users.id as user_id')
                    ->get();
                
                $count = 0;
                foreach ($teachers as $teacher) {
                    $schoolCode = $teacher->school_code;
                    $username = $schoolCode . 'T' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                    while (\Illuminate\Support\Facades\DB::table('users')->where('username', $username)->exists()) {
                        $username = $schoolCode . 'T' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                    }
                    \Illuminate\Support\Facades\DB::table('users')->where('id', $teacher->user_id)->update(['username' => $username]);
                    $count++;
                }
                $results[] = "Populated usernames for $count users/teachers";
            }

            return response()->json(['message' => 'Full Database Repair Completed', 'results' => $results]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Critical Error', 'error' => $e->getMessage()]);
        }
    });

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
