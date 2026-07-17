<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('auth/login', [AuthController::class , 'login']);

    // Public SSLCommerz Callbacks (Move outside auth middleware)
    Route::prefix('billing')->group(function () {
            Route::match (['get', 'post'], '/payment/ssl/success', [\App\Http\Controllers\Billing\SSLCommerzCallbackController::class , 'success'])->name('api.billing.ssl.success');
            Route::match (['get', 'post'], '/payment/ssl/fail', [\App\Http\Controllers\Billing\SSLCommerzCallbackController::class , 'fail'])->name('api.billing.ssl.fail');
            Route::match (['get', 'post'], '/payment/ssl/cancel', [\App\Http\Controllers\Billing\SSLCommerzCallbackController::class , 'cancel'])->name('api.billing.ssl.cancel');
            Route::post('/payment/ssl/ipn', [\App\Http\Controllers\Billing\SSLCommerzCallbackController::class , 'ipn'])->name('api.billing.ssl.ipn');
        }
        );

    // App Updates Check
    Route::get('app-update/check', [\App\Http\Controllers\Api\AppUpdateController::class , 'check']);

    Route::middleware(['auth:sanctum', 'throttle:120,1', 'active_school'])->group(function () {
            Route::post('auth/logout', [AuthController::class , 'logout']);
            Route::post('auth/change-password', [AuthController::class , 'changePassword']);
            Route::get('me', [AuthController::class , 'me']);

            // Notices
            Route::get('notices', [\App\Http\Controllers\Api\NoticeController::class , 'index'])->middleware('role:parent,teacher,principal,school');
            Route::get('notices/{notice}', [\App\Http\Controllers\Api\NoticeController::class , 'show']);
            Route::post('notices', [\App\Http\Controllers\Api\NoticeController::class , 'store'])->middleware('role:principal');
            Route::match (['put', 'patch'], 'notices/{notice}', [\App\Http\Controllers\Api\NoticeController::class , 'update'])->middleware('role:principal');
            Route::delete('notices/{notice}', [\App\Http\Controllers\Api\NoticeController::class , 'destroy'])->middleware('role:principal');
            Route::get('notices/{notice}/stats', [\App\Http\Controllers\Api\NoticeController::class , 'stats'])->middleware('role:principal');

            // Interactions
            Route::post('notices/{notice}/read', [\App\Http\Controllers\Api\NoticeInteractionController::class , 'markAsRead']);
            Route::post('notices/{notice}/reply', [\App\Http\Controllers\Api\NoticeInteractionController::class , 'storeReply'])->middleware('role:parent,teacher,principal');

            // Principal reports
            Route::get('principal/reports/attendance-summary', [\App\Http\Controllers\Api\PrincipalReportController::class , 'attendanceSummary'])->middleware('role:principal');
            Route::get('principal/reports/attendance-details', [\App\Http\Controllers\Api\PrincipalReportController::class , 'attendanceDetails'])->middleware('role:principal');
            Route::get('principal/reports/extra-class-attendance-details', [\App\Http\Controllers\Api\PrincipalReportController::class , 'extraClassAttendanceDetails'])->middleware('role:principal');
            Route::get('principal/reports/lesson-evaluations', [\App\Http\Controllers\Api\PrincipalReportController::class , 'lessonEvaluations'])->middleware('role:principal');
            Route::get('principal/reports/lesson-evaluations/periods', [\App\Http\Controllers\Api\PrincipalReportController::class , 'lessonEvaluationPeriods'])->middleware('role:principal');
            Route::get('principal/reports/lesson-evaluations/{id}', [\App\Http\Controllers\Api\PrincipalReportController::class , 'lessonEvaluationDetail'])->middleware('role:principal');
            Route::get('principal/reports/exam-results-summary', [\App\Http\Controllers\Api\PrincipalReportController::class , 'examResultsSummary'])->middleware('role:principal');
            Route::get('principal/reports/homework-summary', [\App\Http\Controllers\Api\PrincipalReportController::class , 'homeworkSummary'])->middleware('role:principal');
            Route::get('principal/reports/homework-details', [\App\Http\Controllers\Api\PrincipalReportController::class , 'homeworkDetails'])->middleware('role:principal');
            Route::get('principal/reports/leaves-summary', [\App\Http\Controllers\Api\PrincipalReportController::class , 'leavesSummary'])->middleware('role:principal');
            Route::get('principal/reports/leaves-details', [\App\Http\Controllers\Api\PrincipalReportController::class , 'leavesDetails'])->middleware('role:principal');
            Route::get('principal/reports/students-attendance', [\App\Http\Controllers\Api\PrincipalReportController::class , 'studentsAttendance'])->middleware('role:principal');
            Route::get('principal/reports/students-attendance/details', [\App\Http\Controllers\Api\PrincipalReportController::class , 'studentsAttendanceDetails'])->middleware('role:principal');
            Route::get('principal/reports/students-attendance/summary', [\App\Http\Controllers\Api\PrincipalReportController::class , 'studentsAttendanceSummary'])->middleware('role:principal');

            // Teacher attendance & academic actions
            Route::post('teacher/attendance', [\App\Http\Controllers\Api\TeacherAttendanceController::class , 'store'])->middleware('role:teacher');
            Route::post('teacher/attendance/checkout', [\App\Http\Controllers\Api\TeacherAttendanceController::class , 'checkout'])->middleware('role:teacher');
            Route::get('teacher/attendance', [\App\Http\Controllers\Api\TeacherAttendanceController::class , 'index'])->middleware('role:teacher');
            Route::get('teacher/attendance/settings', [\App\Http\Controllers\Api\TeacherAttendanceSettingController::class , 'show'])->middleware('role:teacher');
            // Teacher → Students Attendance (meta + lists)
            Route::get('teacher/students-attendance/modules', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class , 'modules'])->middleware('role:teacher,principal');
            Route::get('teacher/students-attendance/class/meta', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class , 'classMeta'])->middleware('role:teacher,principal');
            Route::get('teacher/students-attendance/extra/meta', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class , 'extraMeta'])->middleware('role:teacher,principal');
            Route::get('teacher/students-attendance/team/meta', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class , 'teamMeta'])->middleware('role:teacher,principal');
            // Teacher → Students Attendance (class section students + submit)
            Route::get('teacher/students-attendance/class/sections/{section}/students', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class , 'classSectionStudents'])->middleware('role:teacher,principal');
            Route::post('teacher/students-attendance/class/sections/{section}/attendance', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class , 'classSectionSubmit'])->middleware('role:teacher,principal');
            // Teacher → Students Attendance (extra class students + submit)
            Route::get('teacher/students-attendance/extra/classes/{extraClass}/students', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class , 'extraClassStudents'])->middleware('role:teacher,principal');
            Route::post('teacher/students-attendance/extra/classes/{extraClass}/attendance', [\App\Http\Controllers\Api\TeacherStudentAttendanceController::class , 'extraClassSubmit'])->middleware('role:teacher,principal');
            Route::get('teacher/homework', [\App\Http\Controllers\Api\HomeworkController::class , 'index'])->middleware('role:teacher');
            Route::post('teacher/homework', [\App\Http\Controllers\Api\HomeworkController::class , 'store'])->middleware('role:teacher');
            Route::match (['put', 'patch'], 'teacher/homework/{homework}', [\App\Http\Controllers\Api\HomeworkController::class , 'update'])->middleware('role:teacher');
            Route::delete('teacher/homework/{homework}', [\App\Http\Controllers\Api\HomeworkController::class , 'destroy'])->middleware('role:teacher');
            // Teacher leaves
            Route::get('teacher/leaves', [\App\Http\Controllers\Api\TeacherLeaveController::class , 'index'])->middleware('role:teacher');
            Route::post('teacher/leaves', [\App\Http\Controllers\Api\TeacherLeaveController::class , 'store'])->middleware('role:teacher');
            // Teacher directory (teacher + principal access)
            Route::get('teachers', [\App\Http\Controllers\Api\TeacherDirectoryController::class , 'index'])->middleware('role:teacher,principal');
            // Teacher → Students directory and profile
            Route::get('teacher/students', [\App\Http\Controllers\Api\StudentDirectoryController::class , 'index'])->middleware('role:teacher,principal');
            Route::get('teacher/students/meta', [\App\Http\Controllers\Api\StudentDirectoryController::class , 'meta'])->middleware('role:teacher,principal');
            Route::get('teacher/students/filters/classes', [\App\Http\Controllers\Api\StudentDirectoryController::class , 'getClasses'])->middleware('role:teacher,principal');
            Route::get('teacher/students/filters/sections', [\App\Http\Controllers\Api\StudentDirectoryController::class , 'getSections'])->middleware('role:teacher,principal');
            Route::get('teacher/students/filters/groups', [\App\Http\Controllers\Api\StudentDirectoryController::class , 'getGroups'])->middleware('role:teacher,principal');
            Route::get('teacher/students/{student}', [\App\Http\Controllers\Api\StudentDirectoryController::class , 'show'])->middleware('role:teacher,principal');
            Route::get('teacher/subjects', [\App\Http\Controllers\Api\TeacherSubjectController::class , 'forClassSection'])->middleware('role:teacher');
            Route::get('teacher/lesson-evaluations', [\App\Http\Controllers\Api\LessonEvaluationController::class , 'index'])->middleware('role:teacher');
            Route::post('teacher/lesson-evaluations', [\App\Http\Controllers\Api\LessonEvaluationController::class , 'store'])->middleware('role:teacher');
            Route::get('teacher/lesson-evaluations/today-routine', [\App\Http\Controllers\Api\LessonEvaluationController::class , 'todayRoutine'])->middleware('role:teacher');
            Route::get('teacher/lesson-evaluations/form', [\App\Http\Controllers\Api\LessonEvaluationController::class , 'form'])->middleware('role:teacher');

            // Teacher → Exams
            Route::prefix('teacher/exams')->middleware('role:teacher,principal')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\TeacherExamController::class , 'index']);
                    Route::get('/todays-duty', [\App\Http\Controllers\Api\TeacherExamController::class , 'todaysDuty']);
                    Route::get('/duty-meta', [\App\Http\Controllers\Api\TeacherExamController::class , 'dutyMeta']);
                    Route::get('/find-seat', [\App\Http\Controllers\Api\TeacherExamController::class , 'findSeat']);
                    Route::get('/mark-entry/meta', [\App\Http\Controllers\Api\TeacherExamController::class , 'markEntryMeta']);
                    Route::get('/mark-entry/exams', [\App\Http\Controllers\Api\TeacherExamController::class , 'getExams']);
                    Route::get('/mark-entry/subjects', [\App\Http\Controllers\Api\TeacherExamController::class , 'getSubjects']);
                    Route::get('/mark-entry/students', [\App\Http\Controllers\Api\TeacherExamController::class , 'getStudents']);
                    Route::post('/mark-entry/save-mark', [\App\Http\Controllers\Api\TeacherExamController::class , 'saveMark']);
                    Route::get('/attendance-report', [\App\Http\Controllers\Api\TeacherExamController::class , 'attendanceReport']);
                    Route::get('/room-attendance', [\App\Http\Controllers\Api\TeacherExamController::class , 'roomAttendanceStudents']);
                    Route::post('/submit-room-attendance', [\App\Http\Controllers\Api\TeacherExamController::class , 'submitRoomAttendance']);
                    Route::post('/bulk-submit-room-attendance', [\App\Http\Controllers\Api\TeacherExamController::class , 'bulkSubmitRoomAttendance']);

                    // Duty Allocation
                    Route::get('/teachers', [\App\Http\Controllers\Api\TeacherExamController::class , 'teachersList']);
                    Route::post('/assign-duty', [\App\Http\Controllers\Api\TeacherExamController::class , 'assignDuty']);
                }
                );

                // Parent endpoints
                Route::get('parent/children', [\App\Http\Controllers\Api\ParentController::class , 'children'])->middleware('role:parent');
                Route::get('parent/homework', [\App\Http\Controllers\Api\ParentController::class , 'homework'])->middleware('role:parent');
                Route::get('parent/attendance', [\App\Http\Controllers\Api\ParentController::class , 'attendance'])->middleware('role:parent');

                Route::get('parent/leaves', [\App\Http\Controllers\Api\ParentController::class , 'leavesIndex'])->middleware('role:parent');
                Route::post('parent/leaves', [\App\Http\Controllers\Api\ParentController::class , 'leavesStore'])->middleware('role:parent');
                Route::get('parent/profile', [\App\Http\Controllers\Api\ParentController::class , 'profile'])->middleware('role:parent');
                Route::get('parent/subjects', [\App\Http\Controllers\Api\ParentController::class , 'subjects'])->middleware('role:parent');
                Route::get('parent/routine', [\App\Http\Controllers\Api\ParentController::class , 'classRoutine'])->middleware('role:parent');

                Route::get('parent/lesson-evaluations', [\App\Http\Controllers\Api\ParentController::class , 'lessonEvaluations'])->middleware('role:parent');
                Route::get('parent/lesson-evaluation-stats', [\App\Http\Controllers\Api\ParentController::class , 'lessonEvaluationStats'])->middleware('role:parent');
                Route::get('parent/exams', [\App\Http\Controllers\Api\ParentController::class , 'exams'])->middleware('role:parent');
                Route::get('parent/exams/{exam}/results', [\App\Http\Controllers\Api\ParentController::class , 'examResults'])->middleware('role:parent');
                Route::get('parent/exams/{exam}/marksheet', [\App\Http\Controllers\Api\ParentController::class , 'examMarksheetPdf'])->middleware('role:parent')->name('api.parent.exams.marksheet');

                Route::get('parent/teachers', [\App\Http\Controllers\Api\ParentController::class , 'teachers'])->middleware('role:parent');
                Route::get('parent/feedback', [\App\Http\Controllers\Api\ParentController::class , 'feedbackIndex'])->middleware('role:parent');
                Route::post('parent/feedback', [\App\Http\Controllers\Api\ParentController::class , 'feedbackStore'])->middleware('role:parent');
                Route::get('/parent/fees', [\App\Http\Controllers\Api\ParentController::class , 'getFees']);
                Route::get('/parent/notices', [\App\Http\Controllers\Api\ParentController::class , 'getNotices']);
    
                // Quick Dashboard Stats
                Route::post('parent/update-photo', [\App\Http\Controllers\Api\ParentController::class , 'updatePhoto'])->middleware('role:parent');
                Route::post('parent/change-password', [\App\Http\Controllers\Api\ParentController::class , 'changePassword'])->middleware('role:parent');
                // Device token endpoints
                Route::post('devices', [\App\Http\Controllers\Api\DeviceTokenController::class , 'store']);
                Route::delete('devices/{deviceToken}', [\App\Http\Controllers\Api\DeviceTokenController::class , 'destroy']);

                // User notifications (for authenticated users)
                Route::get('notifications', [\App\Http\Controllers\Api\NotificationLogController::class , 'userIndex']);
                Route::post('notifications/mark-read', [\App\Http\Controllers\Api\NotificationLogController::class , 'markAsRead']);
                Route::post('notifications/{id}/mark-read', [\App\Http\Controllers\Api\NotificationLogController::class , 'markAsRead']);

                // Notifications Diagnostics
                Route::middleware('role:principal,school')->group(function () {
                    Route::get('notifications/logs', [\App\Http\Controllers\Api\NotificationLogController::class , 'index']);
                    Route::get('notifications/stats', [\App\Http\Controllers\Api\NotificationLogController::class , 'stats']);
                }
                );

                // Test push (principal only)
                Route::post('notifications/test', function (\Illuminate\Http\Request $request) {
                    $user = $request->user();
                    if (!$user->isPrincipal($request->attributes->get('current_school_id')) && !$user->isSuperAdmin()) {
                        return response()->json(['message' => 'অননুমোদিত'], 403);
                    }
                    $tokens = \App\Models\DeviceToken::where('user_id', $user->id)->pluck('token')->toArray();
                    \App\Jobs\SendPushNotificationJob::dispatch($tokens, 'টেস্ট নোটিফিকেশন', 'এটি একটি পরীক্ষামূলক বার্তা', ['type' => 'test']);
                    return response()->json(['message' => 'Push জব কিউ হয়েছে', 'count' => count($tokens)]);
                }
                );

                // Billing & Fees endpoints (v1)
                Route::prefix('billing')->group(function () {
                    Route::get('/fees/student/{studentId}/due', [\App\Http\Controllers\Api\FeeCollectionController::class , 'getDueFees']);
                    Route::get('/fees/categories', [\App\Http\Controllers\Api\FeeReportController::class , 'getCategories']);
                    Route::post('/fees/collect', [\App\Http\Controllers\Api\FeeCollectionController::class , 'collectFees']);
                    Route::post('/fees/{id}/waive-fine', [\App\Http\Controllers\Api\FeeCollectionController::class , 'waiveFine'])->middleware('role:principal,school');
                    Route::post('/fees/initiate-ssl', [\App\Http\Controllers\Api\FeeCollectionController::class , 'initiateSSLPayment']);
                    Route::get('/fees/receipt/{id}/download', [\App\Http\Controllers\Billing\ReceiptController::class , 'downloadPdf'])->name('api.billing.fees.receipt.download');

                    // Fee Configuration
                    Route::get('/config', [\App\Http\Controllers\Api\FeeConfigurationController::class , 'index']);
                    Route::post('/config/categories', [\App\Http\Controllers\Api\FeeConfigurationController::class , 'storeCategory']);
                    Route::match (['put', 'patch'], '/config/categories/{id}', [\App\Http\Controllers\Api\FeeConfigurationController::class , 'updateCategory']);
                    Route::delete('/config/categories/{id}', [\App\Http\Controllers\Api\FeeConfigurationController::class , 'deleteCategory']);
                    Route::post('/config/structures', [\App\Http\Controllers\Api\FeeConfigurationController::class , 'saveStructure']);
                    Route::delete('/config/structures/{id}', [\App\Http\Controllers\Api\FeeConfigurationController::class , 'deleteStructure']);
                    Route::post('/config/generate-dues', [\App\Http\Controllers\Api\FeeConfigurationController::class , 'generateDues']);
                    Route::post('/config/toggle-fine', [\App\Http\Controllers\Api\FeeConfigurationController::class , 'toggleGlobalFine']);

                    // Reports
                    Route::get('/reports/collection-by-date', [\App\Http\Controllers\Api\FeeReportController::class , 'collectionByDate']);
                    Route::get('/reports/collection-by-teacher', [\App\Http\Controllers\Api\FeeReportController::class , 'collectionByTeacher']);
                    Route::get('/reports/collection-paid-students', [\App\Http\Controllers\Api\FeeReportController::class , 'collectionPaidStudents']);
                    Route::get('/reports/due-summary', [\App\Http\Controllers\Api\FeeReportController::class , 'dueReport']);
                    Route::get('/reports/detailed-dues', [\App\Http\Controllers\Api\FeeReportController::class , 'detailedDues']);
                    Route::get('/reports/detailed-dues/pdf', [\App\Http\Controllers\Api\FeeReportController::class , 'detailedDuesPdf']);
                    Route::get('/reports/student-dues', [\App\Http\Controllers\Api\FeeReportController::class , 'studentDues']);
                    Route::get('/reports/teacher-collections', [\App\Http\Controllers\Api\FeeReportController::class, 'teacherCollections'])->middleware('role:teacher');
                    Route::get('/reports/teacher-cash-transfer', [\App\Http\Controllers\Api\FeeReportController::class, 'teacherCashTransfer'])->middleware('role:teacher');
                    Route::post('/reports/teacher-cash-transfer/deposit', [\App\Http\Controllers\Api\FeeReportController::class, 'teacherDepositCash'])->middleware('role:teacher');
                    Route::get('/reports/teacher-deposit-history', [\App\Http\Controllers\Api\FeeReportController::class, 'teacherDepositHistory'])->middleware('role:teacher');

                    // Fee Waivers management (principal/admin)
                    Route::get('/waivers', [\App\Http\Controllers\Api\FeeWaiverController::class , 'index'])->middleware('role:principal,school');
                    Route::get('/waivers/{id}', [\App\Http\Controllers\Api\FeeWaiverController::class , 'show'])->middleware('role:principal,school');
                    Route::post('/waivers', [\App\Http\Controllers\Api\FeeWaiverController::class , 'store'])->middleware('role:principal,school');
                    Route::match (['put', 'patch'], '/waivers/{id}', [\App\Http\Controllers\Api\FeeWaiverController::class , 'update'])->middleware('role:principal,school');
                    Route::delete('/waivers/{id}', [\App\Http\Controllers\Api\FeeWaiverController::class , 'destroy'])->middleware('role:principal,school');

                    // Legacy/Generic payments
                    Route::post('/payments', [\App\Http\Controllers\Billing\PaymentController::class , 'store']);
                    Route::get('/receipts/{id}', [\App\Http\Controllers\Billing\ReceiptController::class , 'show']);
                    Route::get('/students/{student}/due', [\App\Http\Controllers\Billing\DueController::class , 'show']);
                    Route::get('/students/{student}/statement', [\App\Http\Controllers\Billing\StatementController::class , 'monthly']);

                    // Cashier Management
                    Route::get('/cashier-setup', [\App\Http\Controllers\Api\CashierManagementController::class, 'getCashierSetup'])->middleware('role:principal');
                    Route::post('/cashier-setup/assign', [\App\Http\Controllers\Api\CashierManagementController::class, 'assignCashier'])->middleware('role:principal');
                    Route::get('/cashier-setup/statement/{id}', [\App\Http\Controllers\Api\CashierManagementController::class, 'getCashierStatement'])->middleware('role:principal');
                    
                    Route::get('/cashier-pending-deposits', [\App\Http\Controllers\Api\CashierManagementController::class, 'getPendingDeposits']);
                    Route::post('/cashier-accept-deposit/{id}', [\App\Http\Controllers\Api\CashierManagementController::class, 'acceptDeposit']);
                    
                    // Cashier Dashboard
                    Route::get('/cashier-dashboard/data', [\App\Http\Controllers\Api\CashierManagementController::class, 'getCashierDashboardData']);
                    Route::post('/cashier-dashboard/add-expense', [\App\Http\Controllers\Api\CashierManagementController::class, 'addExpense']);
                }
                );

                // SHARED student search and filter endpoints
                Route::prefix('principal')->middleware('role:principal,teacher,school')->group(function () {
                    Route::get('students/search', [\App\Http\Controllers\Api\PrincipalStudentController::class , 'search']);
                    Route::get('students/filters/classes', [\App\Http\Controllers\Api\PrincipalStudentController::class , 'getClasses']);
                    Route::get('students/filters/sections', [\App\Http\Controllers\Api\PrincipalStudentController::class , 'getSections']);
                    Route::get('students/filters/groups', [\App\Http\Controllers\Api\PrincipalStudentController::class , 'getGroups']);
                    Route::get('students/filters/subjects', [\App\Http\Controllers\Api\PrincipalStudentController::class , 'getSubjects']);
                    Route::get('students/{id}', [\App\Http\Controllers\Api\PrincipalStudentController::class , 'show']);
                });

                // Meta endpoints
                Route::prefix('meta')->group(function () {
                    Route::get('classes', [\App\Http\Controllers\Api\SchoolMetaController::class , 'classes']);
                    Route::get('sections', [\App\Http\Controllers\Api\SchoolMetaController::class, 'sections']);
                    Route::get('groups', [\App\Http\Controllers\Api\SchoolMetaController::class, 'groups']);
                    Route::get('subjects', [\App\Http\Controllers\Api\SchoolMetaController::class, 'subjects']);
                    Route::get('teachers', [\App\Http\Controllers\Api\SchoolMetaController::class, 'teachers']);
                    Route::get('school', [\App\Http\Controllers\Api\SchoolMetaController::class, 'school']);
                }
                );
            }
            );        });

// ============================================================
// Biometric Agent API Routes (No standard auth, uses Agent Token)
// ============================================================
Route::prefix('biometric')->group(function () {

    // Agent Authentication
    Route::post('/agent/login', [\App\Http\Controllers\Api\Biometric\AgentAuthController::class, 'login'])
        ->name('biometric.agent.login');

    // Agent Heartbeat (Periodic ping from agent itself)
    Route::post('/agent/heartbeat', [\App\Http\Controllers\Api\Biometric\AgentAuthController::class, 'heartbeat'])
        ->name('biometric.agent.heartbeat');

    // Routes requiring agent authentication via agent token
    Route::middleware(['agent_auth', 'throttle:300,1'])->group(function () {

        // Device heartbeat (status ping from local agent)
        Route::post('/device/heartbeat', [\App\Http\Controllers\Api\Biometric\BiometricSyncController::class, 'heartbeat'])
            ->name('biometric.device.heartbeat');

        // Attendance sync (push punch logs to server)
        Route::post('/attendance/sync', [\App\Http\Controllers\Api\Biometric\BiometricSyncController::class, 'syncAttendance'])
            ->name('biometric.attendance.sync');

        // Template sync (push templates to server, download templates to device)
        Route::post('/templates/upload', [\App\Http\Controllers\Api\Biometric\BiometricSyncController::class, 'uploadTemplates'])
            ->name('biometric.templates.upload');
        Route::post('/templates/download', [\App\Http\Controllers\Api\Biometric\BiometricSyncController::class, 'downloadTemplates'])
            ->name('biometric.templates.download');

        // Device commands (agent polls for commands)
        Route::post('/device-command', [\App\Http\Controllers\Api\Biometric\DeviceCommandController::class, 'getPendingCommands'])
            ->name('biometric.device.command');

        // Full disaster-recovery backup (fingers + card + face per user) - separate from
        // /templates/upload,download above, which are used by the routine sync flow.
        Route::post('/backup/upload', [\App\Http\Controllers\Api\Biometric\BackupSnapshotController::class, 'upload'])
            ->name('biometric.backup.upload');
        Route::post('/backup/download', [\App\Http\Controllers\Api\Biometric\BackupSnapshotController::class, 'download'])
            ->name('biometric.backup.download');

        // Fetch all users with biometric_id for the agent
        Route::get('/users', [\App\Http\Controllers\Api\Biometric\BiometricSyncController::class, 'getUsers'])
            ->name('biometric.users.list');
    });

    // Agent Update Checker (No auth required to check version)
    Route::get('/agent/check-update', [\App\Http\Controllers\Api\Biometric\AgentUpdateController::class, 'checkUpdate'])
        ->name('biometric.agent.update');
});

