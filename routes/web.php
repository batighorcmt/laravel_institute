<?php

use App\Http\Controllers\AdmissionFlowController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\Principal\AdmissionController as PrincipalAdmissionController;
use App\Http\Controllers\Principal\ClassController as PrincipalClassController;
use App\Http\Controllers\Principal\ClassSubjectController as PrincipalClassSubjectController;
use App\Http\Controllers\Principal\GroupController as PrincipalGroupController;
use App\Http\Controllers\Principal\PaymentSettingsController as PrincipalPaymentSettingsController;
use App\Http\Controllers\Principal\RoutineController as PrincipalRoutineController;
use App\Http\Controllers\Principal\SectionController as PrincipalSectionController;
use App\Http\Controllers\Principal\ShiftController as PrincipalShiftController;
use App\Http\Controllers\Principal\StudentController as PrincipalStudentController;
use App\Http\Controllers\Principal\TeacherController as PrincipalTeacherController;
use App\Http\Controllers\Principal\TeamController as PrincipalTeamController;
use App\Http\Controllers\PrincipalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TeacherController;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [App\Http\Controllers\FrontendWebController::class, 'index'])->name('frontend.index');
Route::get('/notices/{notice}/download', [App\Http\Controllers\FrontendWebController::class, 'downloadNotice'])->name('frontend.notices.download');
Route::get('/blog', [App\Http\Controllers\FrontendWebController::class, 'blogIndex'])->name('frontend.blog.index');
Route::get('/blog/{slug}', [App\Http\Controllers\FrontendWebController::class, 'blogShow'])->name('frontend.blog.show');

// Public admission flow
Route::prefix('admission/{schoolCode}')->group(function () {
    Route::get('/validate/mobile', [AdmissionFlowController::class, 'checkMobile'])->name('admission.validate.mobile');
    Route::get('/', [AdmissionFlowController::class, 'index'])->name('admission.index');
    Route::get('/instruction', [AdmissionFlowController::class, 'instruction'])->name('admission.instruction');
    Route::post('/instruction', [AdmissionFlowController::class, 'handleConsent'])->name('admission.instruction.consent');
    // Block applying if an applicant session already exists (must logout first)
    Route::get('/apply', [AdmissionFlowController::class, 'apply'])->middleware('admission.applicant.exclusive')->name('admission.apply');
    Route::post('/apply', [AdmissionFlowController::class, 'submit'])->middleware('admission.applicant.exclusive')->name('admission.apply.submit');
    // Require applicant session to view preview
    Route::get('/preview/{appId}', [AdmissionFlowController::class, 'preview'])->middleware('admission.applicant.guard')->name('admission.preview');
    Route::post('/payment/initiate', [AdmissionFlowController::class, 'paymentInitiate'])->name('admission.payment');
    Route::get('/copy/{appId}', [AdmissionFlowController::class, 'copy'])->middleware('admission.applicant.guard')->name('admission.copy');
    // Applicant admit card (printable) using the same guard
    Route::get('/admit-card/{appId}', [AdmissionFlowController::class, 'admitCard'])
        ->middleware('admission.applicant.guard')
        ->name('admission.admit_card');
    // Applicant login (POST) within admission group; use unique segment to avoid param collisions
    Route::post('/applicant-login', [\App\Http\Controllers\AdmissionController::class, 'login'])->name('admission.login');
    // Login page (Blade view) within admission flow
    Route::get('/login', function (string $schoolCode) {
        $school = \App\Models\School::where('code', $schoolCode)->first();

        return view('admission.login', ['school' => $school]);
    }
    )->name('admission.login.page');
    // Applicant logout: clear only the applicant session key
    Route::post('/applicant-logout', function (string $schoolCode) {
        session()->forget('admission_applicant');

        return redirect()->route('admission.index', $schoolCode);
    }
    )->name('admission.logout');
    Route::match(['GET', 'POST'], '/payment/success/{appId}', [AdmissionFlowController::class, 'paymentSuccess'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.payment.success');
    Route::match(['GET', 'POST'], '/payment/fail/{appId}', [AdmissionFlowController::class, 'paymentFail'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.payment.fail');
    Route::match(['GET', 'POST'], '/payment/cancel/{appId}', [AdmissionFlowController::class, 'paymentCancel'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.payment.cancel');
    // Admission fee payment routes
    Route::post('/admission-fee/initiate', [AdmissionFlowController::class, 'admissionFeeInitiate'])->name('admission.fee.initiate');
    Route::match(['GET', 'POST'], '/admission-fee/success/{appId}', [AdmissionFlowController::class, 'admissionFeeSuccess'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.fee.success');
    Route::match(['GET', 'POST'], '/admission-fee/fail/{appId}', [AdmissionFlowController::class, 'admissionFeeFail'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.fee.fail');
    Route::match(['GET', 'POST'], '/admission-fee/cancel/{appId}', [AdmissionFlowController::class, 'admissionFeeCancel'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.fee.cancel');
    // Printable receipt for admission fee
    Route::get('/admission-fee/receipt/{appId}/{payment}', [AdmissionFlowController::class, 'admissionFeeReceipt'])->name('admission.fee.receipt');
});
Route::post('/admission-fee/ipn', [AdmissionFlowController::class, 'admissionFeeIpn'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('admission.fee.ipn');
Route::post('/admission/payment/ipn', [AdmissionFlowController::class, 'paymentIpn'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('admission.payment.ipn');

// Legacy public admission routes removed in favor of new flow

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Password Reset (custom minimal implementation)
Route::get('/password/forgot', [PasswordResetController::class, 'requestForm'])->name('password.request');
Route::post('/password/email', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.update');

// Protected routes - require authentication
Route::middleware(['auth', 'active_school'])->group(function () {

    // Dashboard routes based on roles
    Route::get('/dashboard', function () {
        // Use Auth facade for clearer static analysis (avoids undefined method warning)
        $user = Auth::user();
        /** @var User $user */
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        } elseif ($user->isPrincipal()) {
            return redirect()->route('principal.dashboard');
        } elseif ($user->isTeacher()) {
            return redirect()->route('teacher.dashboard');
        } elseif ($user->isParent()) {
            return redirect()->route('parent.dashboard');
        }

        return view('dashboard');
    }
    )->name('dashboard');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');

    // CKEditor image upload endpoint (used by admin rich editor)
    Route::post('/cms/ckeditor/upload', function (\Illuminate\Http\Request $request) {
        if (! $request->hasFile('upload')) {
            return response()->json(['uploaded' => 0, 'error' => ['message' => 'No file uploaded']]);
        }
        $file = $request->file('upload');
        $path = $file->store('uploads/ckeditor', 'public');
        $url = asset('storage/'.$path);

        return response()->json(['uploaded' => 1, 'fileName' => $file->getClientOriginalName(), 'url' => $url]);
    })->name('cms.ckeditor.upload');

    // Super Admin Routes (fully protected)
    Route::prefix('superadmin')->name('superadmin.')->middleware([EnsureSuperAdmin::class])->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

        // School CRUD - only super admin
        Route::resource('schools', SchoolController::class)->except(['show']);
        // Added explicit show route for viewing full school + principal details
        Route::get('schools/{school}', [SchoolController::class, 'show'])->name('schools.show');
        Route::get('schools/{school}/manage', [SchoolController::class, 'manage'])->name('schools.manage');
        // Reset principal password and show once to superadmin
        Route::post('schools/{school}/reset-password', [SchoolController::class, 'resetPassword'])->name('schools.reset-password');

        // School Modules management
        Route::get('schools/{school}/modules', [SchoolController::class, 'getModules'])->name('schools.modules');
        Route::post('schools/{school}/modules', [SchoolController::class, 'updateModules'])->name('schools.update-modules');

        // App Updates management
        Route::resource('app-updates', \App\Http\Controllers\SuperAdmin\AppUpdateController::class);
    }
    );

    // Location Dependent Dropdowns (AJAX) - Available to all authenticated users
    Route::get('location/districts', [\App\Http\Controllers\LocationController::class, 'districts'])->name('location.districts');
    Route::get('location/thanas', [\App\Http\Controllers\LocationController::class, 'thanas'])->name('location.thanas');
    Route::get('location/unions', [\App\Http\Controllers\LocationController::class, 'unions'])->name('location.unions');

    // Principal Routes (role-protected)
    Route::prefix('principal')->name('principal.')->middleware(['role:principal'])->group(function () {
        Route::get('/dashboard', [PrincipalController::class, 'dashboard'])->name('dashboard');
        // Institute management for Principal
        Route::get('/institute', [PrincipalController::class, 'institute'])->name('institute');
        Route::get('/institute/{school}/manage', [PrincipalController::class, 'manageSchool'])->name('institute.manage');

        // Nested resources under a specific school
        Route::prefix('institute/{school}')->name('institute.')->middleware(['role:principal,school'])->group(function () {
            // Class Routine
            Route::prefix('routine')->name('routine.')->middleware('module:routine')->group(function () {
                Route::get('/', [PrincipalRoutineController::class, 'panel'])->name('panel');
                Route::get('/teacher-panel', [PrincipalRoutineController::class, 'teacherPanel'])->name('teacher-panel');
                Route::get('/master', [PrincipalRoutineController::class, 'master'])->name('master');
                Route::get('/master-all', [PrincipalRoutineController::class, 'masterAll'])->name('master-all');
                Route::get('/master-print', [PrincipalRoutineController::class, 'masterPrint'])->name('master-print');
                Route::get('/print', [PrincipalRoutineController::class, 'printView'])->name('print');
                Route::get('/teacher-print', [PrincipalRoutineController::class, 'teacherPrintView'])->name('teacher-print');
                Route::get('/subjects', [PrincipalRoutineController::class, 'subjects'])->name('subjects');
                Route::get('/grid', [PrincipalRoutineController::class, 'grid'])->name('grid');
                Route::get('/period-count', [PrincipalRoutineController::class, 'periodCount'])->name('period-count');
                Route::post('/period-count', [PrincipalRoutineController::class, 'setPeriodCount'])->name('period-count.set');
                Route::post('/entry', [PrincipalRoutineController::class, 'saveEntry'])->name('entry.save');
                Route::delete('/entry', [PrincipalRoutineController::class, 'deleteEntry'])->name('entry.delete');
            }
            );
            // Student Report Cards (Vue page + data endpoint)
            Route::prefix('students')->name('students.')->group(function () {
                Route::get('/report-cards', [\App\Http\Controllers\Principal\StudentReportCardController::class, 'index'])->name('report-cards.index');
                Route::get('/{student}/report-card', [\App\Http\Controllers\Principal\StudentReportCardController::class, 'show'])->name('report-cards.show');
                Route::get('/{student}/report-card/print', [\App\Http\Controllers\Principal\StudentReportCardController::class, 'printRecord'])->name('report-cards.print');
                Route::get('/{student}/report-card/data', [\App\Http\Controllers\Principal\StudentReportCardController::class, 'data'])->name('report-cards.data');
            });
            // Notices management
            Route::get('/notices', [PrincipalController::class, 'notices'])->middleware('module:notices')->name('notices');
            // Teachers management
            Route::prefix('teachers')->name('teachers.')->group(function () {
                Route::get('/', [PrincipalTeacherController::class, 'index'])->name('index');
                Route::get('/print', [PrincipalTeacherController::class, 'print'])->name('print');
                Route::get('/create', [PrincipalTeacherController::class, 'create'])->name('create');
                Route::post('/', [PrincipalTeacherController::class, 'store'])->name('store');
                Route::get('/{teacher}/edit', [PrincipalTeacherController::class, 'edit'])->name('edit');
                Route::put('/{teacher}', [PrincipalTeacherController::class, 'update'])->name('update');
                Route::delete('/{teacher}', [PrincipalTeacherController::class, 'destroy'])->name('destroy');
                Route::post('/{teacher}/reset-password', [PrincipalTeacherController::class, 'resetPassword'])->name('reset-password');
            }
            );
            // Attendance routes
            Route::prefix('attendance')->name('attendance.')->middleware('module:attendance')->group(function () {
                Route::get('/class', [App\Http\Controllers\Principal\AttendanceController::class, 'index'])->name('class.index');
                Route::get('/class/take', [App\Http\Controllers\Principal\AttendanceController::class, 'take'])->name('class.take');
                Route::post('/class/store', [App\Http\Controllers\Principal\AttendanceController::class, 'store'])->name('class.store');
                // Dashboard overview (analytics summary similar to legacy attendance_overview.php)
                Route::get('/dashboard', [App\Http\Controllers\Principal\AttendanceController::class, 'dashboard'])->name('dashboard');
                // Monthly report
                Route::get('/monthly-report', [App\Http\Controllers\Principal\AttendanceController::class, 'monthlyReport'])->name('monthly_report');
                // Team attendance
                Route::get('/team', [App\Http\Controllers\Principal\TeamAttendanceController::class, 'index'])->name('team.index');
                Route::get('/team/take', [App\Http\Controllers\Principal\TeamAttendanceController::class, 'take'])->name('team.take');
                Route::post('/team/store', [App\Http\Controllers\Principal\TeamAttendanceController::class, 'store'])->name('team.store');
            }
            );

            // Teacher Attendance Settings
            Route::prefix('teacher-attendance')->name('teacher-attendance.')->group(function () {
                Route::prefix('settings')->name('settings.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Principal\Institute\TeacherAttendanceSettingsController::class, 'index'])->name('index');
                    Route::post('/', [App\Http\Controllers\Principal\Institute\TeacherAttendanceSettingsController::class, 'store'])->name('store');
                }
                );
                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('/daily', [App\Http\Controllers\Principal\Institute\TeacherAttendanceReportController::class, 'dailyReport'])->name('daily');
                    Route::get('/daily/print', [App\Http\Controllers\Principal\Institute\TeacherAttendanceReportController::class, 'dailyReportPrint'])->name('daily.print');
                    Route::get('/monthly', [App\Http\Controllers\Principal\Institute\TeacherAttendanceReportController::class, 'monthlyReport'])->name('monthly');
                    Route::get('/monthly/print', [App\Http\Controllers\Principal\Institute\TeacherAttendanceReportController::class, 'monthlyReportPrint'])->name('monthly.print');
                }
                );
            }
            );

            // Teacher Leaves (review by principal only; no super admin bypass)
            Route::prefix('teacher-leaves')->name('teacher-leaves.')->middleware(['strict_role:principal,school'])->group(function () {
                Route::get('/', [App\Http\Controllers\Principal\Institute\TeacherLeaveController::class, 'index'])->name('index');
                Route::post('/{leave}/approve', [App\Http\Controllers\Principal\Institute\TeacherLeaveController::class, 'approve'])->name('approve');
                Route::post('/{leave}/reject', [App\Http\Controllers\Principal\Institute\TeacherLeaveController::class, 'reject'])->name('reject');
            }
            );

            Route::prefix('lesson-evaluations')->name('lesson-evaluations.')->middleware('module:lesson_evaluation')->group(function () {
                Route::get('/', [\App\Http\Controllers\Principal\LessonEvaluationReportController::class, 'index'])->name('index');
                Route::get('/entry-report', [\App\Http\Controllers\Principal\LessonEvaluationReportController::class, 'entryReport'])->name('entry-report');
                Route::get('/entry-report-print', [\App\Http\Controllers\Principal\LessonEvaluationReportController::class, 'entryReportPrint'])->name('entry-report-print');
                Route::get('/teacher-report', [\App\Http\Controllers\Principal\LessonEvaluationReportController::class, 'teacherReport'])->name('teacher-report');
                Route::get('/teacher-report-print', [\App\Http\Controllers\Principal\LessonEvaluationReportController::class, 'teacherReportPrint'])->name('teacher-report-print');
                Route::get('/print', [\App\Http\Controllers\Principal\LessonEvaluationReportController::class, 'print'])->name('print');
                Route::get('/{lessonEvaluation}', [\App\Http\Controllers\Principal\LessonEvaluationReportController::class, 'show'])->name('show');
            }
            );

            // Admission settings and applications
            Route::prefix('admissions')->name('admissions.')->middleware('module:admission')->group(function () {
                Route::get('/settings', [PrincipalAdmissionController::class, 'settings'])->name('settings');
                Route::post('/settings', [PrincipalAdmissionController::class, 'updateSettings'])->name('settings.update');
                // Per-class admission settings
                Route::get('/class-settings', [\App\Http\Controllers\Principal\AdmissionClassSettingController::class, 'index'])->name('class-settings.index');
                Route::post('/class-settings', [\App\Http\Controllers\Principal\AdmissionClassSettingController::class, 'store'])->name('class-settings.store');
                Route::put('/class-settings/{setting}', [\App\Http\Controllers\Principal\AdmissionClassSettingController::class, 'update'])->name('class-settings.update');
                Route::delete('/class-settings/{setting}', [\App\Http\Controllers\Principal\AdmissionClassSettingController::class, 'destroy'])->name('class-settings.destroy');
                Route::get('/applications', [PrincipalAdmissionController::class, 'applications'])->name('applications');
                Route::get('/applications/print', [PrincipalAdmissionController::class, 'applicationsPrint'])->name('applications.print');
                Route::get('/applications/print.csv', [PrincipalAdmissionController::class, 'applicationsPrintCsv'])->name('applications.print.csv');
                Route::get('/applications/summary', [PrincipalAdmissionController::class, 'summary'])->name('applications.summary');
                Route::get('/applications/{application}', [PrincipalAdmissionController::class, 'show'])->name('applications.show');
                Route::get('/applications/{application}/copy', [PrincipalAdmissionController::class, 'copy'])->name('applications.copy');
                Route::post('/applications/{application}/accept', [PrincipalAdmissionController::class, 'accept'])->name('applications.accept');
                Route::post('/applications/{application}/cancel', [PrincipalAdmissionController::class, 'cancel'])->name('applications.cancel');
                Route::get('/applications/{application}/admit-card', [PrincipalAdmissionController::class, 'admitCard'])->name('applications.admit_card');
                Route::get('/applications/{application}/edit', [PrincipalAdmissionController::class, 'edit'])->name('applications.edit');
                Route::post('/applications/{application}/update', [PrincipalAdmissionController::class, 'update'])->name('applications.update');
                Route::post('/applications/{application}/reset-password', [PrincipalAdmissionController::class, 'resetPassword'])->name('applications.reset_password');
                Route::get('/applications/{application}/payments', [PrincipalAdmissionController::class, 'applicationPayments'])->name('applications.payments.details');
                Route::get('/payments', [PrincipalAdmissionController::class, 'payments'])->name('payments');
                Route::get('/payments/{payment}/invoice', [PrincipalAdmissionController::class, 'paymentInvoice'])->name('payments.invoice');

                // Admission Enrollment - Convert passed students to enrolled students
                Route::get('/enrollment', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class, 'index'])->name('enrollment.index');
                Route::get('/enrollment/print', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class, 'print'])->name('enrollment.print');
                Route::get('/enrollment/{admission_application}/data', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class, 'create'])->name('enrollment.create');
                Route::post('/enrollment', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class, 'store'])->name('enrollment.store');
                Route::get('/enrollment/{student}/subjects', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class, 'subjects'])->name('enrollment.subjects');
                Route::post('/enrollment/{student}/subjects', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class, 'storeSubjects'])->name('enrollment.subjects.store');

                // Admission Permission & Fee (Modal endpoints)
                Route::get('/permission/{application}/data', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class, 'permissionData'])->name('permission.data');
                Route::post('/permission/store', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class, 'permissionStore'])->name('permission.store');

                // Admission Exam management
                Route::prefix('exams')->name('exams.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'index'])->name('index');
                    Route::get('/create', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'create'])->name('create');
                    Route::post('/', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'store'])->name('store');
                    Route::get('/{exam}/edit', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'edit'])->name('edit');
                    Route::put('/{exam}', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'update'])->name('update');
                    Route::delete('/{exam}', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'destroy'])->name('destroy');
                    // Marks entry
                    Route::get('/{exam}/marks', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'marks'])->name('marks');
                    Route::post('/{exam}/marks', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'marksStore'])->name('marks.store');
                    Route::get('/{exam}/results', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'results'])->name('results');
                    Route::get('/{exam}/results/print', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'resultsPrint'])->name('results.print');
                    Route::post('/{exam}/results/send-sms', [\App\Http\Controllers\Principal\AdmissionExamController::class, 'sendResultsSms'])->name('results.send-sms');
                }
                );

                // Admission Seat Plans
                Route::prefix('seat-plans')->name('seat-plans.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'index'])->name('index');
                    Route::get('/create', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'create'])->name('create');
                    Route::post('/', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'store'])->name('store');
                    Route::get('/{seatPlan}/edit', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'edit'])->name('edit');
                    Route::put('/{seatPlan}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'update'])->name('update');
                    Route::delete('/{seatPlan}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'destroy'])->name('destroy');
                    // Room management & allocation stubs (future expansion)
                    Route::get('/{seatPlan}/rooms', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'rooms'])->name('rooms');
                    Route::post('/{seatPlan}/rooms', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'storeRoom'])->name('rooms.store');
                    Route::delete('/rooms/{room}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'deleteRoom'])->name('rooms.delete');
                    Route::get('/rooms/{room}/edit', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'editRoom'])->name('rooms.edit');
                    Route::put('/rooms/{room}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'updateRoom'])->name('rooms.update');
                    // Per-room allocation routes
                    Route::get('/{seatPlan}/rooms/{room}/allocate', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'allocateRoom'])->name('rooms.allocate');
                    Route::post('/{seatPlan}/rooms/{room}/allocate', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'storeRoomAllocation'])->name('rooms.allocate.store');
                    Route::delete('/{seatPlan}/rooms/{room}/allocations/{allocation}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'deleteRoomAllocation'])->name('rooms.allocations.delete');
                    Route::get('/{seatPlan}/rooms/{room}/print', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class, 'printRoom'])->name('rooms.print');
                }
                );
            }
            );

            // Exam Management Routes
            Route::prefix('exams')->name('exams.')->middleware('module:exams')->group(function () {
                Route::get('/', [App\Http\Controllers\Principal\ExamController::class, 'index'])->name('index');

                // Room Attendance (Fixed paths first)
                Route::get('/room-attendance', [App\Http\Controllers\Teacher\ExamRoomAttendanceController::class, 'index'])->name('room-attendance');
                Route::post('/room-attendance/mark', [App\Http\Controllers\Teacher\ExamRoomAttendanceController::class, 'mark'])->name('room-attendance.mark');
                Route::post('/room-attendance/mark-all', [App\Http\Controllers\Teacher\ExamRoomAttendanceController::class, 'markAll'])->name('room-attendance.mark-all');
                Route::get('/attendance-report', [App\Http\Controllers\Teacher\ExamController::class, 'attendanceReport'])->name('attendance-report');
                Route::get('/attendance-report/overall', [App\Http\Controllers\Teacher\ExamController::class, 'overallAttendanceReport'])->name('attendance-report.overall');
                Route::get('/find-seat', [App\Http\Controllers\Teacher\ExamController::class, 'findSeat'])->name('find-seat');

                // Invigilation routes
                Route::prefix('invigilations')->name('invigilations.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Principal\InvigilationController::class, 'index'])->name('index');
                    Route::post('/controller', [App\Http\Controllers\Principal\InvigilationController::class, 'setController'])->name('controller.set');
                    Route::post('/', [App\Http\Controllers\Principal\InvigilationController::class, 'store'])->name('store');
                }
                );

                Route::get('/create', [App\Http\Controllers\Principal\ExamController::class, 'create'])->name('create');
                Route::get('/fetch-subjects', [App\Http\Controllers\Principal\ExamController::class, 'fetchSubjects'])->name('fetch-subjects');
                Route::post('/', [App\Http\Controllers\Principal\ExamController::class, 'store'])->name('store');
                Route::get('/{exam}', [App\Http\Controllers\Principal\ExamController::class, 'show'])->name('show');
                Route::get('/{exam}/print', [App\Http\Controllers\Principal\ExamController::class, 'printView'])->name('print');
                Route::get('/{exam}/edit', [App\Http\Controllers\Principal\ExamController::class, 'edit'])->name('edit');
                Route::put('/{exam}', [App\Http\Controllers\Principal\ExamController::class, 'update'])->name('update');
                Route::get('/{exam}/bulk-update', [App\Http\Controllers\Principal\ExamController::class, 'bulkUpdateView'])->name('bulk-update');
                Route::post('/{exam}/bulk-update', [App\Http\Controllers\Principal\ExamController::class, 'bulkUpdate'])->name('bulk-update.store');
                Route::delete('/{exam}', [App\Http\Controllers\Principal\ExamController::class, 'destroy'])->name('destroy');

                // Exam Subjects
                Route::post('/{exam}/subjects', [App\Http\Controllers\Principal\ExamController::class, 'addSubject'])->name('subjects.add');
                Route::put('/{exam}/subjects/{examSubject}', [App\Http\Controllers\Principal\ExamController::class, 'updateSubject'])->name('subjects.update');
                Route::delete('/{exam}/subjects/{examSubject}', [App\Http\Controllers\Principal\ExamController::class, 'removeSubject'])->name('subjects.remove');

                // Print Templates
                Route::get('/{exam}/admit-v1', [App\Http\Controllers\Principal\ExamPrintController::class, 'admitV1'])->name('admit_v1');
                Route::get('/{exam}/admit-v2', [App\Http\Controllers\Principal\ExamPrintController::class, 'admitV2'])->name('admit_v2');
                Route::get('/{exam}/admit-v3', [App\Http\Controllers\Principal\ExamPrintController::class, 'admitV3'])->name('admit_v3');
                Route::get('/{exam}/admit-v4', [App\Http\Controllers\Principal\ExamPrintController::class, 'admitV4'])->name('admit_v4');
                Route::get('/{exam}/attendance-sheet', [App\Http\Controllers\Principal\ExamPrintController::class, 'attendanceSheet'])->name('attendance_sheet');
            }
            );

            // Printable statistics report
            Route::get('/results/statistics', [App\Http\Controllers\Principal\ResultController::class, 'statistics'])->name('institute.results.statistics');

            // Mark Entry Routes
            Route::prefix('marks')->name('marks.')->middleware('module:exams')->group(function () {
                Route::get('/', [App\Http\Controllers\Principal\MarkEntryController::class, 'index'])->name('index');
                Route::get('/{exam}', [App\Http\Controllers\Principal\MarkEntryController::class, 'show'])->name('show');
                Route::get('/{exam}/subjects/{examSubject}/entry', [App\Http\Controllers\Principal\MarkEntryController::class, 'entryForm'])->name('entry');
                Route::post('/{exam}/subjects/{examSubject}/save', [App\Http\Controllers\Principal\MarkEntryController::class, 'saveMark'])->name('save');
                Route::get('/{exam}/subjects/{examSubject}/print-blank', [App\Http\Controllers\Principal\MarkEntryController::class, 'printBlank'])->name('print-blank');
                Route::get('/{exam}/subjects/{examSubject}/print-filled', [App\Http\Controllers\Principal\MarkEntryController::class, 'printFilled'])->name('print-filled');
            }
            );

            // Seat Plan Routes
            Route::prefix('seat-plans')->name('seat-plans.')->group(function () {
                Route::get('/', [App\Http\Controllers\Principal\SeatPlanController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Principal\SeatPlanController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Principal\SeatPlanController::class, 'store'])->name('store');
                Route::get('/{seatPlan}', [App\Http\Controllers\Principal\SeatPlanController::class, 'show'])->name('show');
                Route::get('/{seatPlan}/edit', [App\Http\Controllers\Principal\SeatPlanController::class, 'edit'])->name('edit');
                Route::put('/{seatPlan}', [App\Http\Controllers\Principal\SeatPlanController::class, 'update'])->name('update');
                Route::delete('/{seatPlan}', [App\Http\Controllers\Principal\SeatPlanController::class, 'destroy'])->name('destroy');

                // Room Management
                Route::get('/{seatPlan}/rooms', [App\Http\Controllers\Principal\SeatPlanController::class, 'manageRooms'])->name('rooms');
                Route::post('/{seatPlan}/rooms', [App\Http\Controllers\Principal\SeatPlanController::class, 'storeRoom'])->name('rooms.store');
                Route::get('/{seatPlan}/rooms/{room}/edit', [App\Http\Controllers\Principal\SeatPlanController::class, 'editRoom'])->name('rooms.edit');
                Route::put('/{seatPlan}/rooms/{room}', [App\Http\Controllers\Principal\SeatPlanController::class, 'updateRoom'])->name('rooms.update');
                Route::delete('/{seatPlan}/rooms/{room}', [App\Http\Controllers\Principal\SeatPlanController::class, 'destroyRoom'])->name('rooms.destroy');

                // Seat Allocation
                Route::get('/{seatPlan}/allocate', [App\Http\Controllers\Principal\SeatPlanController::class, 'allocateSeats'])->name('allocate');
                Route::post('/{seatPlan}/allocate', [App\Http\Controllers\Principal\SeatPlanController::class, 'storeAllocation'])->name('allocate.store');
                Route::delete('/{seatPlan}/allocations/{allocation}', [App\Http\Controllers\Principal\SeatPlanController::class, 'removeAllocation'])->name('allocations.remove');
                Route::get('/{seatPlan}/search-students', [App\Http\Controllers\Principal\SeatPlanController::class, 'searchStudents'])->name('search-students');
                Route::get('/{seatPlan}/find-student', [App\Http\Controllers\Principal\SeatPlanController::class, 'findStudent'])->name('find-student');

                // Print
                Route::get('/{seatPlan}/rooms/{room}/print', [App\Http\Controllers\Principal\SeatPlanController::class, 'printRoom'])->name('rooms.print');
                Route::get('/{seatPlan}/print-all', [App\Http\Controllers\Principal\SeatPlanController::class, 'printAll'])->name('print-all');
            }
            );

            // Result Management Routes
            Route::prefix('results')->name('results.')->middleware('module:results')->group(function () {
                // Exam List
                Route::get('/exams', [App\Http\Controllers\Principal\ResultController::class, 'examList'])->name('exams');
                Route::get('/exams/{exam}/result-sheet/print', [App\Http\Controllers\Principal\ResultController::class, 'printResultSheet'])->name('exams.result-sheet.print');

                // Marksheet
                Route::get('/marksheet', [App\Http\Controllers\Principal\ResultController::class, 'marksheet'])->name('marksheet');
                Route::get('/marksheet/{exam}/{student}/print', [App\Http\Controllers\Principal\ResultController::class, 'printMarksheet'])->name('marksheet.print');

                // Merit List
                Route::get('/merit-list', [App\Http\Controllers\Principal\ResultController::class, 'meritList'])->name('merit-list');
                Route::get('/merit-list/{exam}/{classId}/print', [App\Http\Controllers\Principal\ResultController::class, 'printMeritList'])->name('merit-list.print');

                // Tabulation Sheet
                Route::get('/tabulation', [App\Http\Controllers\Principal\ResultController::class, 'tabulation'])->name('tabulation');
                Route::get('/tabulation/{exam}/{classId}/print', [App\Http\Controllers\Principal\ResultController::class, 'printTabulation'])->name('tabulation.print');
                // AJAX helpers for tabulation cascading selects
                Route::get('/exams-by-year', [App\Http\Controllers\Principal\ResultController::class, 'examsByYear'])->name('exams-by-year');
                Route::get('/sections-by-class', [App\Http\Controllers\Principal\ResultController::class, 'sectionsByClass'])->name('sections-by-class');
                Route::get('/students-by-class', [App\Http\Controllers\Principal\ResultController::class, 'studentsByClass'])->name('students-by-class');

                // Statistics
                Route::get('/statistics', [App\Http\Controllers\Principal\ResultController::class, 'statistics'])->name('statistics');

                // Publish/Unpublish
                Route::post('/{exam}/publish', [App\Http\Controllers\Principal\ResultController::class, 'publishResults'])->name('publish');
                Route::post('/{exam}/unpublish', [App\Http\Controllers\Principal\ResultController::class, 'unpublishResults'])->name('unpublish');
            }
            );

            // Holiday management (per school)
            Route::prefix('settings')->group(function () {
                Route::get('holidays', [\App\Http\Controllers\Principal\HolidayController::class, 'index'])->name('holidays.index');
                Route::post('holidays', [\App\Http\Controllers\Principal\HolidayController::class, 'store'])->name('holidays.store');
                Route::patch('holidays/{holiday}', [\App\Http\Controllers\Principal\HolidayController::class, 'update'])->name('holidays.update');
                Route::delete('holidays/{holiday}', [\App\Http\Controllers\Principal\HolidayController::class, 'destroy'])->name('holidays.destroy');
                Route::post('weekly-holidays', [\App\Http\Controllers\Principal\HolidayController::class, 'updateWeekly'])->name('weekly-holidays.update');
                // SMS Settings
                Route::prefix('sms')->name('sms.')->middleware('module:sms')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Principal\SmsSettingsController::class, 'index'])->name('index');
                    Route::match(['post', 'patch'], '/', function () {
                        abort(405, 'Method not allowed for this route. Use the appropriate form.');
                    }
                    );
                    Route::match(['post', 'patch'], '/api', [\App\Http\Controllers\Principal\SmsSettingsController::class, 'saveApi'])->name('api.save');
                    Route::match(['post', 'patch'], '/class-attendance', [\App\Http\Controllers\Principal\SmsSettingsController::class, 'saveClassAttendance'])->name('class-attendance.save');
                    Route::match(['post', 'patch'], '/extra-class-attendance', [\App\Http\Controllers\Principal\SmsSettingsController::class, 'saveExtraClassAttendance'])->name('extra-class-attendance.save');
                    Route::match(['post', 'patch'], '/lesson-evaluation', [\App\Http\Controllers\Principal\SmsSettingsController::class, 'saveLessonEvaluation'])->name('lesson-evaluation.save');
                    Route::post('/templates', [\App\Http\Controllers\Principal\SmsSettingsController::class, 'storeTemplate'])->name('templates.store');
                    Route::match(['post', 'patch'], '/templates/{template}', [\App\Http\Controllers\Principal\SmsSettingsController::class, 'updateTemplate'])->name('templates.update');
                    Route::delete('/templates/{template}', [\App\Http\Controllers\Principal\SmsSettingsController::class, 'destroyTemplate'])->name('templates.destroy');
                    // SMS Panel + Logs
                    Route::get('/panel', [\App\Http\Controllers\Principal\SmsController::class, 'panel'])->name('panel');
                    Route::post('/send', [\App\Http\Controllers\Principal\SmsController::class, 'send'])->name('send');
                    Route::get('/logs', [\App\Http\Controllers\Principal\SmsController::class, 'logs'])->name('logs');
                    Route::get('/logs/{log}', [\App\Http\Controllers\Principal\SmsController::class, 'view'])->name('logs.view');
                }
                );
                // Online Payments (SSLCommerz)
                Route::get('payments', [PrincipalPaymentSettingsController::class, 'index'])->name('payments.index');
                Route::post('payments', [PrincipalPaymentSettingsController::class, 'save'])->name('payments.save');

                // FCM Diagnostics & Logs
                Route::prefix('fcm')->name('fcm.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Principal\FcmDiagnosticsController::class, 'index'])->name('index');
                    Route::post('/test-send', [\App\Http\Controllers\Principal\FcmDiagnosticsController::class, 'testSend'])->name('test-send');
                    Route::post('/purge-stale', [\App\Http\Controllers\Principal\FcmDiagnosticsController::class, 'purgeStale'])->name('purge-stale');
                    Route::delete('/token/{token}', [\App\Http\Controllers\Principal\FcmDiagnosticsController::class, 'deleteToken'])->name('token.destroy');
                    Route::delete('/logs/clear', [\App\Http\Controllers\Principal\FcmDiagnosticsController::class, 'clearLogs'])->name('logs.clear');
                }
                );
            }
            );

            // Background Settings
            Route::get('background-settings', [\App\Http\Controllers\Principal\BackgroundSettingsController::class, 'index'])->name('background_settings.index');
            Route::post('background-settings', [\App\Http\Controllers\Principal\BackgroundSettingsController::class, 'update'])->name('background_settings.update');

            // Public Exams Settings
            Route::resource('public-exams', \App\Http\Controllers\Principal\PublicExamController::class)->names('public_exams')->except(['show']);

            Route::resource('shifts', PrincipalShiftController::class)->except(['show']);
            Route::resource('sections', PrincipalSectionController::class)->except(['show']);
            Route::resource('groups', PrincipalGroupController::class)->except(['show']);
            Route::resource('classes', PrincipalClassController::class)->except(['show']);
            Route::resource('subjects', \App\Http\Controllers\Principal\SubjectController::class)->except(['show']);
            Route::resource('academic-years', \App\Http\Controllers\Principal\AcademicYearController::class)->except(['show']);
            Route::patch('academic-years/{academic_year}/current', [\App\Http\Controllers\Principal\AcademicYearController::class, 'setCurrent'])->name('academic-years.set-current');

            // Result Settings
            Route::get('result-settings', [\App\Http\Controllers\Principal\ResultSettingController::class, 'index'])->name('result-settings.index');
            Route::post('result-settings', [\App\Http\Controllers\Principal\ResultSettingController::class, 'store'])->name('result-settings.store');

            // Extra Classes routes
            Route::prefix('extra-classes')->name('extra-classes.')->middleware('module:extra_class')->group(function () {
                Route::get('/', [\App\Http\Controllers\Principal\ExtraClassController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Principal\ExtraClassController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Principal\ExtraClassController::class, 'store'])->name('store');
                Route::get('/{extraClass}/edit', [\App\Http\Controllers\Principal\ExtraClassController::class, 'edit'])->name('edit');
                Route::put('/{extraClass}', [\App\Http\Controllers\Principal\ExtraClassController::class, 'update'])->name('update');
                Route::delete('/{extraClass}', [\App\Http\Controllers\Principal\ExtraClassController::class, 'destroy'])->name('destroy');
                Route::get('/{extraClass}/students', [\App\Http\Controllers\Principal\ExtraClassController::class, 'manageStudents'])->name('students');
                Route::post('/{extraClass}/students', [\App\Http\Controllers\Principal\ExtraClassController::class, 'storeStudents'])->name('students.store');

                // Extra Class Attendance routes
                Route::prefix('attendance')->name('attendance.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Principal\ExtraClassAttendanceController::class, 'index'])->name('index');
                    Route::get('/take', [\App\Http\Controllers\Principal\ExtraClassAttendanceController::class, 'take'])->name('take');
                    Route::post('/store', [\App\Http\Controllers\Principal\ExtraClassAttendanceController::class, 'store'])->name('store');
                    Route::get('/daily-report', [\App\Http\Controllers\Principal\ExtraClassAttendanceController::class, 'dailyReport'])->name('daily-report');
                    Route::get('/monthly-report', [\App\Http\Controllers\Principal\ExtraClassAttendanceController::class, 'monthlyReport'])->name('monthly-report');
                    Route::get('/dashboard', [\App\Http\Controllers\Principal\ExtraClassAttendanceController::class, 'dashboard'])->name('dashboard');
                }
                );
            }
            );

            // Bulk student import routes should be defined before the resource route
            // to avoid the 'bulk' segment being interpreted as a student ID.
            Route::get('students/bulk', [PrincipalStudentController::class, 'bulkForm'])->name('students.bulk');
            Route::post('students/bulk', [PrincipalStudentController::class, 'bulkImport'])->name('students.bulk.import');
            Route::get('students/bulk/template', [PrincipalStudentController::class, 'bulkTemplate'])->name('students.bulk.template');
            Route::post('students/bulk/queue', [PrincipalStudentController::class, 'bulkEnqueue'])->name('students.bulk.queue');
            Route::get('students/bulk/status/{id}', [PrincipalStudentController::class, 'bulkStatus'])->name('students.bulk.status');
            Route::get('students/bulk/report/{id}', [PrincipalStudentController::class, 'bulkReport'])->name('students.bulk.report');
            Route::get('students/print-controls', [PrincipalStudentController::class, 'printControls'])->name('students.print-controls');
            Route::get('students/print-preview', [PrincipalStudentController::class, 'printPreview'])->name('students.print-preview');
            Route::get('students/public-exam-info', [PrincipalStudentController::class, 'publicExamInfoPage'])->name('students.public-exam-info');
            Route::get('students/public-exam-info/print', [PrincipalStudentController::class, 'publicExamInfoPrint'])->name('students.public-exam-info.print');
            Route::get('students/public-exam-info/print-table', [PrincipalStudentController::class, 'publicExamInfoPrintTable'])->name('students.public-exam-info.print-table');
            Route::match(['GET', 'POST'], 'students/public-exam-info/id-card-print', [PrincipalStudentController::class, 'publicExamInfoIdCardPrint'])->name('students.public-exam-info.id-card-print');
            Route::get('students/public-exam-info/id-card-settings', [PrincipalStudentController::class, 'publicExamInfoIdCardSettingsLoad'])->name('students.public-exam-info.id-card-settings.load');
            Route::post('students/public-exam-info/id-card-settings', [PrincipalStudentController::class, 'publicExamInfoIdCardSettingsSave'])->name('students.public-exam-info.id-card-settings.save');

            // Regular Student ID Cards
            Route::get('students/id-cards', [PrincipalStudentController::class, 'idCardsIndex'])->name('students.id-cards.index');
            Route::post('students/id-cards/load', [PrincipalStudentController::class, 'idCardsLoad'])->name('students.id-cards.load');
            Route::get('students/id-cards/settings', [PrincipalStudentController::class, 'idCardsSettingsLoad'])->name('students.id-cards.settings.load');
            Route::post('students/id-cards/settings', [PrincipalStudentController::class, 'idCardsSettingsSave'])->name('students.id-cards.settings.save');
            Route::match(['GET', 'POST'], 'students/id-cards/print', [PrincipalStudentController::class, 'idCardsPrint'])->name('students.id-cards.print');

            Route::post('students/public-exam-info/load', [PrincipalStudentController::class, 'publicExamInfoLoad'])->name('students.public-exam-info.load');
            Route::post('students/{student}/public-exam-info/save', [PrincipalStudentController::class, 'publicExamInfoSave'])->name('students.public-exam-info.save');
            Route::post('students/{student}/reset-password', [PrincipalStudentController::class, 'resetPassword'])->name('students.reset-password');
            Route::resource('students', PrincipalStudentController::class);
            Route::patch('students/{student}/status', [PrincipalStudentController::class, 'toggleStatus'])->name('students.toggle-status');
            Route::post('students/{student}/enrollments', [PrincipalStudentController::class, 'addEnrollment'])->name('students.enrollments.add');
            Route::delete('students/{student}/enrollments/{enrollment}', [PrincipalStudentController::class, 'removeEnrollment'])->name('students.enrollments.remove');
            Route::post('students/{student}/teams', [PrincipalStudentController::class, 'attachTeam'])->name('students.teams.attach');
            Route::delete('students/{student}/teams/{team}', [PrincipalStudentController::class, 'detachTeam'])->name('students.teams.detach');
            Route::get('students/{student}/lesson-evaluation-details', [PrincipalStudentController::class, 'lessonEvaluationDetails'])->name('students.lesson-evaluation-details');
            Route::get('students/{student}/print-cv', [PrincipalStudentController::class, 'printCv'])->name('students.print-cv');
            Route::post('students/{student}/public-exams', [\App\Http\Controllers\Principal\StudentPublicExamController::class, 'store'])->name('students.public-exams.store');
            Route::put('students/{student}/public-exams/{publicExam}', [\App\Http\Controllers\Principal\StudentPublicExamController::class, 'update'])->name('students.public-exams.update');
            Route::delete('students/{student}/public-exams/{publicExam}', [\App\Http\Controllers\Principal\StudentPublicExamController::class, 'destroy'])->name('students.public-exams.destroy');
            // Student subject assignment
            Route::get('enrollments/{enrollment}/subjects', [\App\Http\Controllers\Principal\StudentSubjectController::class, 'edit'])->name('enrollments.subjects.edit');
            Route::post('enrollments/{enrollment}/subjects', [\App\Http\Controllers\Principal\StudentSubjectController::class, 'update'])->name('enrollments.subjects.update');
            // Meta endpoints for dynamic dropdowns
            Route::get('meta/sections', [\App\Http\Controllers\Principal\MetaController::class, 'sections'])->name('meta.sections');
            Route::get('meta/classes', [\App\Http\Controllers\Principal\MetaController::class, 'classes'])->name('meta.classes');
            Route::get('meta/academic-years', [\App\Http\Controllers\Principal\MetaController::class, 'academicYears'])->name('meta.academic-years');
            Route::get('meta/students', [\App\Http\Controllers\Principal\MetaController::class, 'students'])->name('meta.students');
            Route::get('meta/groups', [\App\Http\Controllers\Principal\MetaController::class, 'groups'])->name('meta.groups');
            Route::get('meta/next-roll', [\App\Http\Controllers\Principal\MetaController::class, 'nextRoll'])->name('meta.next-roll');
            // Class-Subject mapping
            Route::prefix('classes/{class}')->group(function () {
                Route::get('subjects', [PrincipalClassSubjectController::class, 'index'])->name('classes.subjects.index');
                Route::post('subjects', [PrincipalClassSubjectController::class, 'store'])->name('classes.subjects.store');
                Route::post('subjects/bulk', [PrincipalClassSubjectController::class, 'bulkStore'])->name('classes.subjects.bulk');
                Route::patch('subjects/{mapping}/optional', [PrincipalClassSubjectController::class, 'toggleOptional'])->name('classes.subjects.toggleOptional');
                Route::patch('subjects/order/update', [PrincipalClassSubjectController::class, 'updateOrder'])->name('classes.subjects.order');
                Route::get('subjects/{mapping}/edit', [PrincipalClassSubjectController::class, 'edit'])->name('classes.subjects.edit');
                Route::patch('subjects/{mapping}', [PrincipalClassSubjectController::class, 'update'])->name('classes.subjects.update');
                Route::delete('subjects/{mapping}', [PrincipalClassSubjectController::class, 'destroy'])->name('classes.subjects.destroy');
            }
            );

            // Teams CRUD
            Route::resource('teams', PrincipalTeamController::class)->except(['show']);
            Route::get('teams/{team}/add-students', [PrincipalTeamController::class, 'addStudents'])->name('teams.add-students');
            Route::post('teams/{team}/add-students', [PrincipalTeamController::class, 'storeStudents'])->name('teams.store-students');
            Route::get('teams/{team}/members', [PrincipalTeamController::class, 'members'])->name('teams.members');

            // Documents (Prottayon, Certificate, Testimonial, Settings)
            Route::prefix('documents')->name('documents.')->middleware('module:documents')->group(function () {
                // Prottayon
                Route::get('/prottayon', [\App\Http\Controllers\Principal\Documents\ProttayonController::class, 'index'])->name('prottayon.index');
                Route::post('/prottayon/generate', [\App\Http\Controllers\Principal\Documents\ProttayonController::class, 'generate'])->name('prottayon.generate');
                Route::get('/prottayon/print/{document}', [\App\Http\Controllers\Principal\Documents\ProttayonController::class, 'print'])->name('prottayon.print');
                Route::get('/prottayon/history', [\App\Http\Controllers\Principal\Documents\ProttayonController::class, 'history'])->name('prottayon.history');
                Route::get('/prottayon/{document}/edit', [\App\Http\Controllers\Principal\Documents\ProttayonController::class, 'edit'])->name('prottayon.edit');
                Route::put('/prottayon/{document}', [\App\Http\Controllers\Principal\Documents\ProttayonController::class, 'update'])->name('prottayon.update');

                // Certificate (past-year study certificate)
                Route::get('/certificate', [\App\Http\Controllers\Principal\Documents\CertificateController::class, 'index'])->name('certificate.index');
                Route::post('/certificate/generate', [\App\Http\Controllers\Principal\Documents\CertificateController::class, 'generate'])->name('certificate.generate');
                Route::get('/certificate/print/{document}', [\App\Http\Controllers\Principal\Documents\CertificateController::class, 'print'])->name('certificate.print');
                Route::get('/certificate/history', [\App\Http\Controllers\Principal\Documents\CertificateController::class, 'history'])->name('certificate.history');
                Route::get('/certificate/{document}/edit', [\App\Http\Controllers\Principal\Documents\CertificateController::class, 'edit'])->name('certificate.edit');
                Route::put('/certificate/{document}', [\App\Http\Controllers\Principal\Documents\CertificateController::class, 'update'])->name('certificate.update');

                // Testimonial (SSC/HSC)
                Route::get('/testimonial', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'index'])->name('testimonial.index');
                Route::get('/testimonial/load-students', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'loadStudents'])->name('testimonial.load-students');
                Route::post('/testimonial/quick-generate', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'quickGenerate'])->name('testimonial.quick-generate');
                Route::post('/testimonial/generate', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'generate'])->name('testimonial.generate');
                Route::get('/testimonial/print/{document}', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'print'])->name('testimonial.print');
                Route::get('/testimonial/history', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'history'])->name('testimonial.history');
                Route::get('/testimonial/{document}/edit', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'edit'])->name('testimonial.edit');
                Route::put('/testimonial/{document}', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'update'])->name('testimonial.update');

                // Settings (backgrounds, colors for print pages)
                Route::get('/settings', [\App\Http\Controllers\Principal\Documents\SettingsController::class, 'index'])->name('settings.index');
                Route::post('/settings', [\App\Http\Controllers\Principal\Documents\SettingsController::class, 'store'])->name('settings.store');
                Route::get('/settings/templates', [\App\Http\Controllers\Principal\Documents\SettingsController::class, 'templates'])->name('settings.templates.index');
                Route::post('/settings/templates', [\App\Http\Controllers\Principal\Documents\SettingsController::class, 'storeTemplate'])->name('settings.templates.store');
                Route::delete('/settings/templates/{template}', [\App\Http\Controllers\Principal\Documents\SettingsController::class, 'destroyTemplate'])->name('settings.templates.destroy');
            }
            );

            // Game and Sports
            Route::prefix('game-and-sports')->name('game-and-sports.')->group(function () {
                Route::prefix('consent')->name('consent.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Principal\GameAndSportsController::class, 'index'])->name('index');
                    Route::get('/print', [App\Http\Controllers\Principal\GameAndSportsController::class, 'print'])->name('print');
                });

                Route::prefix('interschool')->name('interschool.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Principal\InterschoolController::class, 'index'])->name('index');
                    Route::get('/settings', [\App\Http\Controllers\Principal\InterschoolController::class, 'settings'])->name('settings');

                    Route::get('/api/seasons', [\App\Http\Controllers\Principal\InterschoolController::class, 'getSeasons']);
                    Route::post('/api/seasons', [\App\Http\Controllers\Principal\InterschoolController::class, 'storeSeason']);
                    Route::put('/api/seasons/{season}', [\App\Http\Controllers\Principal\InterschoolController::class, 'updateSeason']);

                    Route::get('/api/events-settings', [\App\Http\Controllers\Principal\InterschoolController::class, 'getEventsSettings']);
                    Route::post('/api/events-settings', [\App\Http\Controllers\Principal\InterschoolController::class, 'storeEventSetting']);
                    Route::put('/api/events-settings/{id}', [\App\Http\Controllers\Principal\InterschoolController::class, 'updateEventSetting']);
                    Route::delete('/api/events-settings/{id}', [\App\Http\Controllers\Principal\InterschoolController::class, 'deleteEventSetting']);

                    Route::post('/api/sub-events-settings', [\App\Http\Controllers\Principal\InterschoolController::class, 'storeSubEventSetting']);
                    Route::put('/api/sub-events-settings/{id}', [\App\Http\Controllers\Principal\InterschoolController::class, 'updateSubEventSetting']);
                    Route::delete('/api/sub-events-settings/{id}', [\App\Http\Controllers\Principal\InterschoolController::class, 'deleteSubEventSetting']);

                    Route::get('/api/seasons/{season}/events', [\App\Http\Controllers\Principal\InterschoolController::class, 'getSeasonEvents']);
                    Route::post('/api/seasons/{season}/events', [\App\Http\Controllers\Principal\InterschoolController::class, 'storeSeasonEvent']);
                    Route::delete('/api/seasons/{season}/events/{id}', [\App\Http\Controllers\Principal\InterschoolController::class, 'deleteSeasonEvent']);

                    Route::get('/api/season-events/{seasonEvent}/players', [\App\Http\Controllers\Principal\InterschoolController::class, 'getPlayers']);
                    Route::post('/api/season-events/{seasonEvent}/players', [\App\Http\Controllers\Principal\InterschoolController::class, 'storePlayer']);
                    Route::patch('/api/season-events/{seasonEvent}/players/reorder', [\App\Http\Controllers\Principal\InterschoolController::class, 'reorderPlayers']);
                    Route::put('/api/season-events/{seasonEvent}/players/{player}', [\App\Http\Controllers\Principal\InterschoolController::class, 'updatePlayer']);
                    Route::delete('/api/season-events/{seasonEvent}/players/{player}', [\App\Http\Controllers\Principal\InterschoolController::class, 'deletePlayer']);
                    Route::get('/api/search-students', [\App\Http\Controllers\Principal\InterschoolController::class, 'searchStudents']);
                    Route::get('/api/classes', [\App\Http\Controllers\Principal\InterschoolController::class, 'getClasses']);

                    // Print Appendixes
                    Route::get('/appendix/ka', [\App\Http\Controllers\Principal\InterschoolController::class, 'printAppendixKa'])->name('appendix.ka');
                    Route::get('/appendix/kha', [\App\Http\Controllers\Principal\InterschoolController::class, 'printAppendixKha'])->name('appendix.kha');
                    Route::get('/appendix/ga', [\App\Http\Controllers\Principal\InterschoolController::class, 'printAppendixGa'])->name('appendix.ga');
                    Route::get('/appendix/gha', [\App\Http\Controllers\Principal\InterschoolController::class, 'printAppendixGha'])->name('appendix.gha');
                    Route::get('/appendix/umo', [\App\Http\Controllers\Principal\InterschoolController::class, 'printAppendixUmo'])->name('appendix.umo');
                });
            });

            // Frontend Website Settings & CMS
            Route::prefix('frontend')->name('frontend.')->middleware('module:frontend_website')->group(function () {
                Route::get('/settings', [\App\Http\Controllers\Principal\FrontendSettingsController::class, 'index'])->name('settings');
                Route::get('/settings/data', [\App\Http\Controllers\Principal\FrontendSettingsController::class, 'getData'])->name('settings.data');
                Route::post('/settings/data', [\App\Http\Controllers\Principal\FrontendSettingsController::class, 'updateData'])->name('settings.update');
                Route::post('/settings/upload', [\App\Http\Controllers\Principal\FrontendSettingsController::class, 'uploadImage'])->name('settings.upload');

                Route::get('/front-page-elements', [\App\Http\Controllers\Principal\FrontPageElementsController::class, 'index'])->name('front-page-elements');
                Route::get('/front-page-elements/data', [\App\Http\Controllers\Principal\FrontPageElementsController::class, 'getData'])->name('front-page-elements.data');
                Route::post('/front-page-elements/data', [\App\Http\Controllers\Principal\FrontPageElementsController::class, 'updateData'])->name('front-page-elements.update');
                Route::delete('/front-page-elements/gallery', [\App\Http\Controllers\Principal\FrontPageElementsController::class, 'deleteGalleryImage'])->name('front-page-elements.gallery.delete');

                Route::get('/menus', [\App\Http\Controllers\Principal\FrontendMenuController::class, 'index'])->name('menus');
                Route::get('/menus/data', [\App\Http\Controllers\Principal\FrontendMenuController::class, 'getData'])->name('menus.data');
                Route::post('/menus/data', [\App\Http\Controllers\Principal\FrontendMenuController::class, 'updateData'])->name('menus.update');

                Route::get('/pages', [\App\Http\Controllers\Principal\CmsPageController::class, 'index'])->name('pages.index');
                Route::get('/pages/create', [\App\Http\Controllers\Principal\CmsPageController::class, 'create'])->name('pages.create');
                Route::post('/pages', [\App\Http\Controllers\Principal\CmsPageController::class, 'store'])->name('pages.store');
                Route::get('/pages/{page}/edit', [\App\Http\Controllers\Principal\CmsPageController::class, 'edit'])->name('pages.edit');
                Route::put('/pages/{page}', [\App\Http\Controllers\Principal\CmsPageController::class, 'update'])->name('pages.update');
                Route::delete('/pages/{page}', [\App\Http\Controllers\Principal\CmsPageController::class, 'destroy'])->name('pages.destroy');

                Route::get('/posts', [\App\Http\Controllers\Principal\CmsPostController::class, 'index'])->name('posts.index');
                Route::get('/posts/create', [\App\Http\Controllers\Principal\CmsPostController::class, 'create'])->name('posts.create');
                Route::post('/posts', [\App\Http\Controllers\Principal\CmsPostController::class, 'store'])->name('posts.store');
                Route::get('/posts/{post}/edit', [\App\Http\Controllers\Principal\CmsPostController::class, 'edit'])->name('posts.edit');
                Route::put('/posts/{post}', [\App\Http\Controllers\Principal\CmsPostController::class, 'update'])->name('posts.update');
                Route::delete('/posts/{post}', [\App\Http\Controllers\Principal\CmsPostController::class, 'destroy'])->name('posts.destroy');
            });
        }
        );
    }
    );

    // Teacher Routes (role-protected)
    Route::prefix('teacher')->name('teacher.')->middleware(['role:teacher'])->group(function () {
        Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');

        // Teacher Attendance
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [App\Http\Controllers\Teacher\AttendanceController::class, 'index'])->name('index');
            Route::post('/check-in', [App\Http\Controllers\Teacher\AttendanceController::class, 'checkIn'])->name('check-in');
            Route::post('/check-out', [App\Http\Controllers\Teacher\AttendanceController::class, 'checkOut'])->name('check-out');
            Route::get('/my-attendance', [App\Http\Controllers\Teacher\AttendanceController::class, 'myAttendance'])->name('my-attendance');
        }
        );

        // Teacher Leaves
        Route::prefix('leave')->name('leave.')->group(function () {
            Route::get('/', [App\Http\Controllers\Teacher\LeaveController::class, 'index'])->name('index');
            Route::get('/apply', [App\Http\Controllers\Teacher\LeaveController::class, 'create'])->name('create');
            Route::post('/apply', [App\Http\Controllers\Teacher\LeaveController::class, 'store'])->name('store');
        }
        );

        // School-specific teacher routes
        Route::prefix('institute/{school}')->name('institute.')->middleware(['role:teacher,school'])->group(function () {
            // Student Attendance (Class/Section based)
            Route::prefix('attendance/class')->name('attendance.class.')->middleware('module:attendance')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\StudentAttendanceController::class, 'index'])->name('index');
                Route::get('/take', [App\Http\Controllers\Teacher\StudentAttendanceController::class, 'take'])->name('take');
                Route::post('/store', [App\Http\Controllers\Teacher\StudentAttendanceController::class, 'store'])->name('store');
            }
            );

            Route::prefix('attendance/extra-classes')->name('attendance.extra-classes.')->middleware('module:attendance')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\ExtraClassAttendanceController::class, 'index'])->name('index');
                Route::get('/take', [App\Http\Controllers\Teacher\ExtraClassAttendanceController::class, 'take'])->name('take');
                Route::post('/store', [App\Http\Controllers\Teacher\ExtraClassAttendanceController::class, 'store'])->name('store');
            }
            );

            // Notices
            Route::get('/notices', [TeacherController::class, 'notices'])->middleware('module:notices')->name('notices');

            // Team Attendance (stub; to be implemented)
            Route::prefix('attendance/team')->name('attendance.team.')->middleware('module:attendance')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\TeamAttendanceController::class, 'index'])->name('index');
            }
            );

            // Lesson Evaluation
            Route::prefix('lesson-evaluation')->name('lesson-evaluation.')->middleware('module:lesson_evaluation')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\LessonEvaluationController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Teacher\LessonEvaluationController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Teacher\LessonEvaluationController::class, 'store'])->name('store');
                Route::get('/{lessonEvaluation}', [App\Http\Controllers\Teacher\LessonEvaluationController::class, 'show'])->name('show');
            }
            );

            // Homework
            Route::prefix('homework')->name('homework.')->middleware('module:homework')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\HomeworkController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Teacher\HomeworkController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Teacher\HomeworkController::class, 'store'])->name('store');
                Route::get('/{homework}', [App\Http\Controllers\Teacher\HomeworkController::class, 'show'])->name('show');
                Route::get('/{homework}/edit', [App\Http\Controllers\Teacher\HomeworkController::class, 'edit'])->name('edit');
                Route::put('/{homework}', [App\Http\Controllers\Teacher\HomeworkController::class, 'update'])->name('update');
                Route::delete('/{homework}', [App\Http\Controllers\Teacher\HomeworkController::class, 'destroy'])->name('destroy');
            }
            );

            // Directories (Students and Teachers)
            Route::prefix('directory')->name('directory.')->group(function () {
                Route::get('/students', [App\Http\Controllers\Teacher\DirectoryController::class, 'students'])->name('students');
                Route::get('/students/{student}', [App\Http\Controllers\Teacher\DirectoryController::class, 'studentShow'])->name('students.show');
                Route::post('/students/{student}/reset-password', [App\Http\Controllers\Teacher\DirectoryController::class, 'studentResetPassword'])->name('students.reset-password');
                Route::get('/teachers', [App\Http\Controllers\Teacher\DirectoryController::class, 'teachers'])->name('teachers');
            }
            );

            // Teacher Routine
            Route::prefix('routine')->name('routine.')->middleware('module:routine')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\TeacherRoutineController::class, 'printView'])->name('index');
            }
            );

            // Billing collection page for teachers (restricted by assigned class in controller)
            Route::get('/billing/collect', [App\Http\Controllers\Teacher\Billing\CollectController::class, 'create'])->name('billing.collect');
            Route::get('/billing/my-collections', function () {
                return view('billing.teacher_collections');
            }
            )->name('billing.my_collections');
            Route::get('/billing/cash-transfer', function () {
                return view('billing.teacher_cash_transfer');
            }
            )->name('billing.cash_transfer');
            Route::get('/billing/deposit-history', function () {
                return view('billing.teacher_deposit_history');
            }
            )->name('billing.deposit_history');

            // Manage Exams (all teachers)
            Route::prefix('exams')->name('exams.')->middleware('module:exams')->group(function () {
                Route::get('/todays-duty', [App\Http\Controllers\Teacher\ExamController::class, 'todaysDuty'])->name('todays-duty');
                Route::get('/mark-entry', [App\Http\Controllers\Teacher\ExamController::class, 'markEntry'])->name('mark-entry');
                Route::get('/load-marks-form', [App\Http\Controllers\Teacher\ExamController::class, 'loadMarksForm'])->name('load-marks-form');
                Route::post('/store-marks', [App\Http\Controllers\Teacher\ExamController::class, 'storeMarks'])->name('store-marks');
                Route::get('/get-classes', [App\Http\Controllers\Teacher\ExamController::class, 'getClasses'])->name('get-classes');
                Route::get('/get-by-status', [App\Http\Controllers\Teacher\ExamController::class, 'getExamsByStatus'])->name('get-by-status');
                Route::get('/get-subjects', [App\Http\Controllers\Teacher\ExamController::class, 'getSubjects'])->name('get-subjects');
                Route::post('/save-mark', [App\Http\Controllers\Teacher\ExamController::class, 'saveMark'])->name('save-mark');
                Route::get('/room-attendance', [App\Http\Controllers\Teacher\ExamRoomAttendanceController::class, 'index'])->name('room-attendance');
                Route::post('/room-attendance/mark', [App\Http\Controllers\Teacher\ExamRoomAttendanceController::class, 'mark'])->name('room-attendance.mark');
                Route::post('/room-attendance/mark-all', [App\Http\Controllers\Teacher\ExamRoomAttendanceController::class, 'markAll'])->name('room-attendance.mark-all');
                Route::get('/find-seat', [App\Http\Controllers\Teacher\ExamController::class, 'findSeat'])->name('find-seat');
                // Invigilation management (Exam Controllers)
                Route::prefix('invigilations')->name('invigilations.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Principal\InvigilationController::class, 'index'])->name('index');
                    Route::post('/controller', [\App\Http\Controllers\Principal\InvigilationController::class, 'setController'])->name('controller.set');
                    Route::post('/', [\App\Http\Controllers\Principal\InvigilationController::class, 'store'])->name('store');
                }
                );

                // Attendance Reports (Exam Controllers)
                Route::get('/attendance-report', [App\Http\Controllers\Teacher\ExamController::class, 'attendanceReport'])->name('attendance-report');
                Route::get('/attendance-report/overall', [App\Http\Controllers\Teacher\ExamController::class, 'overallAttendanceReport'])->name('attendance-report.overall');
            }
            );
        }
        );
    }
    );

    // Parent Routes (role-protected)
    Route::prefix('parent')->name('parent.')->middleware(['role:parent'])->group(function () {
        Route::get('/dashboard', [ParentController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [ParentController::class, 'profile'])->name('profile');
        Route::get('/subjects', [ParentController::class, 'subjects'])->name('subjects');
        Route::get('/routine', [ParentController::class, 'routine'])->middleware('module:routine')->name('routine');
        Route::get('/homework', [ParentController::class, 'homework'])->middleware('module:homework')->name('homework');
        Route::get('/attendance/class', [ParentController::class, 'classAttendance'])->middleware('module:attendance')->name('attendance.class');
        Route::get('/attendance/extra', [ParentController::class, 'extraAttendanceReport'])->middleware('module:attendance')->name('attendance.extra');
        Route::get('/evaluations', [ParentController::class, 'evaluations'])->middleware('module:lesson_evaluation')->name('evaluations');
        Route::get('/leaves', [ParentController::class, 'leaves'])->name('leaves');
        Route::post('/leaves', [ParentController::class, 'submitLeave'])->name('leaves.store');
        Route::get('/notices', [ParentController::class, 'notices'])->middleware('module:notices')->name('notices');
        Route::get('/teachers', [ParentController::class, 'teachers'])->name('teachers');
        Route::get('/feedback', [ParentController::class, 'feedback'])->name('feedback');
        Route::post('/feedback', [ParentController::class, 'submitFeedback'])->name('feedback.store');
        Route::get('/fees', [ParentController::class, 'fees'])->name('fees');
    }
    );

    // Billing blades (simple views; access limited by nav visibility and auth)
    Route::middleware('module:accounts')->group(function () {
        Route::get('/billing/due', function () {
            return view('billing.due');
        }
        )->name('billing.due');
        Route::get('/billing/detailed-due-report', function () {
            return view('billing.detailed-due-report');
        }
        )->name('billing.detailed_due_report');
        Route::get('/billing/statement', function () {
            return view('billing.statement');
        }
        )->name('billing.statement');
        Route::get('/billing/cashier-setup', function () {
            return view('billing.principal_cashier_setup');
        }
        )->name('billing.cashier_setup');
        Route::get('/billing/cashier-dashboard', function () {
            return view('billing.cashier_dashboard');
        }
        )->name('billing.cashier_dashboard');
        Route::get('/billing/collect', function () {
            $schoolId = request()->attributes->get('current_school_id');
            $school = $schoolId ? \App\Models\School::find($schoolId) : auth()->user()->primarySchool();

            return view('billing.collect', ['school' => $school]);
        }
        )->name('billing.collect');
        Route::get('/billing/config', function () {
            return view('billing.config');
        }
        )->name('billing.config');
        Route::get('/billing/reports', function () {
            return view('billing.reports');
        }
        )->name('billing.reports');
        Route::get('/billing/collection-reports', function () {
            return view('billing.collection_reports');
        }
        )->name('billing.collection_reports');
        Route::get('/billing/waivers', function () {
            return view('billing.waivers');
        }
        )->name('billing.waivers');
        Route::get('/billing/receipts/{id}', [\App\Http\Controllers\Billing\ReceiptController::class, 'showWeb'])->name('billing.receipts.show');
        Route::get('/billing/receipts/{id}/download', [\App\Http\Controllers\Billing\ReceiptController::class, 'downloadPdf'])->name('billing.receipts.download');

        // SSLCommerz Callbacks moved to top level

    }
    );

    // Billing Settings
    Route::get('/billing/settings/fee-structures', [\App\Http\Controllers\Billing\SettingsController::class, 'feeStructureIndex'])->name('billing.settings.fee_structures');
    Route::post('/billing/settings/fee-structures', [\App\Http\Controllers\Billing\SettingsController::class, 'feeStructureStore'])->name('billing.settings.fee_structures.store');
    Route::get('/billing/settings/discounts', [\App\Http\Controllers\Billing\SettingsController::class, 'discountsIndex'])->name('billing.settings.discounts');
    Route::post('/billing/settings/discounts', [\App\Http\Controllers\Billing\SettingsController::class, 'discountsStore'])->name('billing.settings.discounts.store');
    Route::get('/billing/settings/categories', [\App\Http\Controllers\Billing\SettingsController::class, 'categoriesIndex'])->name('billing.settings.categories');
    Route::post('/billing/settings/categories', [\App\Http\Controllers\Billing\SettingsController::class, 'categoriesStore'])->name('billing.settings.categories.store');
    Route::patch('/billing/settings/categories/{category}', [\App\Http\Controllers\Billing\SettingsController::class, 'categoriesUpdate'])->name('billing.settings.categories.update');
    Route::delete('/billing/settings/categories/{category}', [\App\Http\Controllers\Billing\SettingsController::class, 'categoriesDestroy'])->name('billing.settings.categories.destroy');
    Route::get('/billing/settings/global-fees', [\App\Http\Controllers\Billing\SettingsController::class, 'globalFeesIndex'])->name('billing.settings.global_fees');
    Route::post('/billing/settings/global-fees', [\App\Http\Controllers\Billing\SettingsController::class, 'globalFeesStore'])->name('billing.settings.global_fees.store');

    // Fine Settings
    Route::middleware('module:accounts')->group(function () {
        Route::get('/billing/settings/fines', [\App\Http\Controllers\Billing\SettingsController::class, 'fineIndex'])->name('billing.settings.fines');
        Route::post('/billing/settings/fines', [\App\Http\Controllers\Billing\SettingsController::class, 'fineStore'])->name('billing.settings.fines.store');
    }
    );
});

// Public document verification endpoint (QR target)
Route::get('/verify/document/{code}', [\App\Http\Controllers\Documents\VerificationController::class, 'show'])->name('documents.verify');

// Portable (Signed) Marks Print routes - specifically for mobile app access
Route::get('/print/marks/{exam}/{examSubject}/{type}', [App\Http\Controllers\Principal\MarkEntryController::class, 'printPortable'])
    ->name('print.marks.portable')
    ->middleware('signed');

// CMS custom pages (WordPress-style clean URL: /about-us) — must be registered last
Route::get('/{slug}', [App\Http\Controllers\FrontendWebController::class, 'cmsPage'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('frontend.cms.page');
