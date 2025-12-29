<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdmissionFlowController;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\PrincipalController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\SchoolController;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\StrictRoleMiddleware;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Principal\ShiftController as PrincipalShiftController;
use App\Http\Controllers\Principal\SectionController as PrincipalSectionController;
use App\Http\Controllers\Principal\GroupController as PrincipalGroupController;
use App\Http\Controllers\Principal\ClassController as PrincipalClassController;
use App\Http\Controllers\Principal\ClassSubjectController as PrincipalClassSubjectController;
use App\Http\Controllers\Principal\TeamController as PrincipalTeamController;
use App\Http\Controllers\Principal\RoutineController as PrincipalRoutineController;
use App\Http\Controllers\Principal\TeacherController as PrincipalTeacherController;
use App\Http\Controllers\Principal\AdmissionController as PrincipalAdmissionController;
use App\Http\Controllers\Principal\PaymentSettingsController as PrincipalPaymentSettingsController;

// Public routes
Route::get('/', function () {
    return redirect('/login');
});

// Public admission flow
Route::prefix('admission/{schoolCode}')->group(function() {
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
    Route::get('/login', function(string $schoolCode) {
        $school = \App\Models\School::where('code', $schoolCode)->first();
        return view('admission.login', ['school' => $school]);
    })->name('admission.login.page');
    // Applicant logout: clear only the applicant session key
    Route::post('/applicant-logout', function(string $schoolCode) {
        session()->forget('admission_applicant');
        return redirect()->route('admission.index', $schoolCode);
    })->name('admission.logout');
    Route::match(['GET','POST'],'/payment/success/{appId}', [AdmissionFlowController::class,'paymentSuccess'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.payment.success');
    Route::match(['GET','POST'],'/payment/fail/{appId}', [AdmissionFlowController::class,'paymentFail'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.payment.fail');
    Route::match(['GET','POST'],'/payment/cancel/{appId}', [AdmissionFlowController::class,'paymentCancel'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.payment.cancel');
    // Admission fee payment routes
    Route::post('/admission-fee/initiate', [AdmissionFlowController::class,'admissionFeeInitiate'])->name('admission.fee.initiate');
    Route::match(['GET','POST'],'/admission-fee/success/{appId}', [AdmissionFlowController::class,'admissionFeeSuccess'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.fee.success');
    Route::match(['GET','POST'],'/admission-fee/fail/{appId}', [AdmissionFlowController::class,'admissionFeeFail'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.fee.fail');
    Route::match(['GET','POST'],'/admission-fee/cancel/{appId}', [AdmissionFlowController::class,'admissionFeeCancel'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('admission.fee.cancel');
    // Printable receipt for admission fee
    Route::get('/admission-fee/receipt/{appId}/{payment}', [AdmissionFlowController::class,'admissionFeeReceipt'])->name('admission.fee.receipt');
});
Route::post('/admission-fee/ipn', [AdmissionFlowController::class,'admissionFeeIpn'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('admission.fee.ipn');
Route::post('/admission/payment/ipn', [AdmissionFlowController::class,'paymentIpn'])
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
Route::middleware(['auth'])->group(function () {
    
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
    })->name('dashboard');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');

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
    });

    // Principal Routes (role-protected)
    Route::prefix('principal')->name('principal.')->middleware(['role:principal'])->group(function () {
        Route::get('/dashboard', [PrincipalController::class, 'dashboard'])->name('dashboard');
        // Institute management for Principal
        Route::get('/institute', [PrincipalController::class, 'institute'])->name('institute');
        Route::get('/institute/{school}/manage', [PrincipalController::class, 'manageSchool'])->name('institute.manage');

        // Nested resources under a specific school
        Route::prefix('institute/{school}')->name('institute.')->middleware(['role:principal,school'])->group(function () {
            // Class Routine
            Route::prefix('routine')->name('routine.')->group(function(){
                Route::get('/', [PrincipalRoutineController::class,'panel'])->name('panel');
                    Route::get('/print', [PrincipalRoutineController::class,'printView'])->name('print');
                Route::get('/subjects', [PrincipalRoutineController::class,'subjects'])->name('subjects');
                Route::get('/grid', [PrincipalRoutineController::class,'grid'])->name('grid');
                Route::get('/period-count', [PrincipalRoutineController::class,'periodCount'])->name('period-count');
                Route::post('/period-count', [PrincipalRoutineController::class,'setPeriodCount'])->name('period-count.set');
                Route::post('/entry', [PrincipalRoutineController::class,'saveEntry'])->name('entry.save');
                Route::delete('/entry', [PrincipalRoutineController::class,'deleteEntry'])->name('entry.delete');
            });
            // Teachers management
            Route::prefix('teachers')->name('teachers.')->group(function(){
                Route::get('/', [PrincipalTeacherController::class,'index'])->name('index');
                Route::get('/create', [PrincipalTeacherController::class,'create'])->name('create');
                Route::post('/', [PrincipalTeacherController::class,'store'])->name('store');
                Route::get('/{teacher}/edit', [PrincipalTeacherController::class,'edit'])->name('edit');
                Route::put('/{teacher}', [PrincipalTeacherController::class,'update'])->name('update');
                Route::delete('/{teacher}', [PrincipalTeacherController::class,'destroy'])->name('destroy');
                Route::post('/{teacher}/reset-password', [PrincipalTeacherController::class,'resetPassword'])->name('reset-password');
            });
            // Attendance routes
            Route::prefix('attendance')->name('attendance.')->group(function () {
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
            });

            // Teacher Attendance Settings
            Route::prefix('teacher-attendance')->name('teacher-attendance.')->group(function () {
                Route::prefix('settings')->name('settings.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Principal\Institute\TeacherAttendanceSettingsController::class, 'index'])->name('index');
                    Route::post('/', [App\Http\Controllers\Principal\Institute\TeacherAttendanceSettingsController::class, 'store'])->name('store');
                });
                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('/daily', [App\Http\Controllers\Principal\Institute\TeacherAttendanceReportController::class, 'dailyReport'])->name('daily');
                    Route::get('/daily/print', [App\Http\Controllers\Principal\Institute\TeacherAttendanceReportController::class, 'dailyReportPrint'])->name('daily.print');
                    Route::get('/monthly', [App\Http\Controllers\Principal\Institute\TeacherAttendanceReportController::class, 'monthlyReport'])->name('monthly');
                    Route::get('/monthly/print', [App\Http\Controllers\Principal\Institute\TeacherAttendanceReportController::class, 'monthlyReportPrint'])->name('monthly.print');
                });
            });

            // Teacher Leaves (review by principal only; no super admin bypass)
            Route::prefix('teacher-leaves')->name('teacher-leaves.')->middleware(['strict_role:principal,school'])->group(function () {
                Route::get('/', [App\Http\Controllers\Principal\Institute\TeacherLeaveController::class, 'index'])->name('index');
                Route::post('/{leave}/approve', [App\Http\Controllers\Principal\Institute\TeacherLeaveController::class, 'approve'])->name('approve');
                Route::post('/{leave}/reject', [App\Http\Controllers\Principal\Institute\TeacherLeaveController::class, 'reject'])->name('reject');
            });

            // Admission settings and applications
            Route::prefix('admissions')->name('admissions.')->group(function(){
                Route::get('/settings', [PrincipalAdmissionController::class,'settings'])->name('settings');
                Route::post('/settings', [PrincipalAdmissionController::class,'updateSettings'])->name('settings.update');
                // Per-class admission settings
                Route::get('/class-settings', [\App\Http\Controllers\Principal\AdmissionClassSettingController::class,'index'])->name('class-settings.index');
                Route::post('/class-settings', [\App\Http\Controllers\Principal\AdmissionClassSettingController::class,'store'])->name('class-settings.store');
                Route::put('/class-settings/{setting}', [\App\Http\Controllers\Principal\AdmissionClassSettingController::class,'update'])->name('class-settings.update');
                Route::delete('/class-settings/{setting}', [\App\Http\Controllers\Principal\AdmissionClassSettingController::class,'destroy'])->name('class-settings.destroy');
                Route::get('/applications', [PrincipalAdmissionController::class,'applications'])->name('applications');
                Route::get('/applications/print', [PrincipalAdmissionController::class,'applicationsPrint'])->name('applications.print');
                Route::get('/applications/print.csv', [PrincipalAdmissionController::class,'applicationsPrintCsv'])->name('applications.print.csv');
                Route::get('/applications/summary', [PrincipalAdmissionController::class,'summary'])->name('applications.summary');
                Route::get('/applications/{application}', [PrincipalAdmissionController::class,'show'])->name('applications.show');
                Route::get('/applications/{application}/copy', [PrincipalAdmissionController::class,'copy'])->name('applications.copy');
                Route::post('/applications/{application}/accept', [PrincipalAdmissionController::class,'accept'])->name('applications.accept');
                Route::post('/applications/{application}/cancel', [PrincipalAdmissionController::class,'cancel'])->name('applications.cancel');
                Route::get('/applications/{application}/admit-card', [PrincipalAdmissionController::class,'admitCard'])->name('applications.admit_card');
                Route::get('/applications/{application}/edit', [PrincipalAdmissionController::class,'edit'])->name('applications.edit');
                Route::post('/applications/{application}/update', [PrincipalAdmissionController::class,'update'])->name('applications.update');
                Route::post('/applications/{application}/reset-password', [PrincipalAdmissionController::class,'resetPassword'])->name('applications.reset_password');
                Route::get('/applications/{application}/payments', [PrincipalAdmissionController::class,'applicationPayments'])->name('applications.payments.details');
                Route::get('/payments', [PrincipalAdmissionController::class,'payments'])->name('payments');
                Route::get('/payments/{payment}/invoice', [PrincipalAdmissionController::class,'paymentInvoice'])->name('payments.invoice');

                // Admission Enrollment - Convert passed students to enrolled students
                Route::get('/enrollment', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class,'index'])->name('enrollment.index');
                Route::get('/enrollment/print', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class,'print'])->name('enrollment.print');
                Route::get('/enrollment/{admission_application}/data', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class,'create'])->name('enrollment.create');
                Route::post('/enrollment', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class,'store'])->name('enrollment.store');
                // Use full nested route name for clarity and to avoid RouteNotFoundException
                // Fix: Remove duplicate {school} param, keep only {application}
                Route::post('/enrollment/{application}/pay', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class,'payFee'])->name('principal.institute.admissions.enrollment.fee.pay');
                Route::get('/enrollment/{student}/subjects', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class,'subjects'])->name('enrollment.subjects');
                Route::post('/enrollment/{student}/subjects', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class,'storeSubjects'])->name('enrollment.subjects.store');

                // Admission Permission & Fee (Modal endpoints)
                Route::get('/permission/{application}/data', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class,'permissionData'])->name('permission.data');
                Route::post('/permission/store', [\App\Http\Controllers\Principal\AdmissionEnrollmentController::class,'permissionStore'])->name('permission.store');

                // Admission Exam management
                Route::prefix('exams')->name('exams.')->group(function(){
                    Route::get('/', [\App\Http\Controllers\Principal\AdmissionExamController::class,'index'])->name('index');
                    Route::get('/create', [\App\Http\Controllers\Principal\AdmissionExamController::class,'create'])->name('create');
                    Route::post('/', [\App\Http\Controllers\Principal\AdmissionExamController::class,'store'])->name('store');
                    Route::get('/{exam}/edit', [\App\Http\Controllers\Principal\AdmissionExamController::class,'edit'])->name('edit');
                    Route::put('/{exam}', [\App\Http\Controllers\Principal\AdmissionExamController::class,'update'])->name('update');
                    Route::delete('/{exam}', [\App\Http\Controllers\Principal\AdmissionExamController::class,'destroy'])->name('destroy');
                    // Marks entry
                    Route::get('/{exam}/marks', [\App\Http\Controllers\Principal\AdmissionExamController::class,'marks'])->name('marks');
                    Route::post('/{exam}/marks', [\App\Http\Controllers\Principal\AdmissionExamController::class,'marksStore'])->name('marks.store');
                    Route::get('/{exam}/results', [\App\Http\Controllers\Principal\AdmissionExamController::class,'results'])->name('results');
                    Route::get('/{exam}/results/print', [\App\Http\Controllers\Principal\AdmissionExamController::class,'resultsPrint'])->name('results.print');
                    Route::post('/{exam}/results/send-sms', [\App\Http\Controllers\Principal\AdmissionExamController::class,'sendResultsSms'])->name('results.send-sms');
                });

                // Admission Seat Plans
                Route::prefix('seat-plans')->name('seat-plans.')->group(function(){
                    Route::get('/', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'index'])->name('index');
                    Route::get('/create', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'create'])->name('create');
                    Route::post('/', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'store'])->name('store');
                    Route::get('/{seatPlan}/edit', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'edit'])->name('edit');
                    Route::put('/{seatPlan}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'update'])->name('update');
                    Route::delete('/{seatPlan}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'destroy'])->name('destroy');
                    // Room management & allocation stubs (future expansion)
                    Route::get('/{seatPlan}/rooms', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'rooms'])->name('rooms');
                    Route::post('/{seatPlan}/rooms', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'storeRoom'])->name('rooms.store');
                    Route::delete('/rooms/{room}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'deleteRoom'])->name('rooms.delete');
                    Route::get('/rooms/{room}/edit', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'editRoom'])->name('rooms.edit');
                    Route::put('/rooms/{room}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'updateRoom'])->name('rooms.update');
                    // Per-room allocation routes
                    Route::get('/{seatPlan}/rooms/{room}/allocate', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'allocateRoom'])->name('rooms.allocate');
                    Route::post('/{seatPlan}/rooms/{room}/allocate', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'storeRoomAllocation'])->name('rooms.allocate.store');
                    Route::delete('/{seatPlan}/rooms/{room}/allocations/{allocation}', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'deleteRoomAllocation'])->name('rooms.allocations.delete');
                    Route::get('/{seatPlan}/rooms/{room}/print', [\App\Http\Controllers\Principal\AdmissionSeatPlanController::class,'printRoom'])->name('rooms.print');
                });
            });

            // Exam Management Routes
            Route::prefix('exams')->name('exams.')->group(function(){
                Route::get('/', [App\Http\Controllers\Principal\ExamController::class,'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Principal\ExamController::class,'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Principal\ExamController::class,'store'])->name('store');
                Route::get('/{exam}', [App\Http\Controllers\Principal\ExamController::class,'show'])->name('show');
                Route::get('/{exam}/edit', [App\Http\Controllers\Principal\ExamController::class,'edit'])->name('edit');
                Route::put('/{exam}', [App\Http\Controllers\Principal\ExamController::class,'update'])->name('update');
                Route::delete('/{exam}', [App\Http\Controllers\Principal\ExamController::class,'destroy'])->name('destroy');
                
                // Exam Subjects
                Route::post('/{exam}/subjects', [App\Http\Controllers\Principal\ExamController::class,'addSubject'])->name('subjects.add');
                Route::put('/{exam}/subjects/{examSubject}', [App\Http\Controllers\Principal\ExamController::class,'updateSubject'])->name('subjects.update');
                Route::delete('/{exam}/subjects/{examSubject}', [App\Http\Controllers\Principal\ExamController::class,'removeSubject'])->name('subjects.remove');
            });

            // Mark Entry Routes
            Route::prefix('marks')->name('marks.')->group(function(){
                Route::get('/', [App\Http\Controllers\Principal\MarkEntryController::class,'index'])->name('index');
                Route::get('/{exam}', [App\Http\Controllers\Principal\MarkEntryController::class,'show'])->name('show');
                Route::get('/{exam}/subjects/{examSubject}/entry', [App\Http\Controllers\Principal\MarkEntryController::class,'entryForm'])->name('entry');
                Route::post('/{exam}/subjects/{examSubject}/save', [App\Http\Controllers\Principal\MarkEntryController::class,'saveMark'])->name('save');
                Route::get('/{exam}/subjects/{examSubject}/print-blank', [App\Http\Controllers\Principal\MarkEntryController::class,'printBlank'])->name('print-blank');
                Route::get('/{exam}/subjects/{examSubject}/print-filled', [App\Http\Controllers\Principal\MarkEntryController::class,'printFilled'])->name('print-filled');
                Route::post('/{exam}/calculate-results', [App\Http\Controllers\Principal\MarkEntryController::class,'calculateResults'])->name('calculate-results');
            });

            // Seat Plan Routes
            Route::prefix('seat-plans')->name('seat-plans.')->group(function(){
                Route::get('/', [App\Http\Controllers\Principal\SeatPlanController::class,'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Principal\SeatPlanController::class,'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Principal\SeatPlanController::class,'store'])->name('store');
                Route::get('/{seatPlan}', [App\Http\Controllers\Principal\SeatPlanController::class,'show'])->name('show');
                Route::get('/{seatPlan}/edit', [App\Http\Controllers\Principal\SeatPlanController::class,'edit'])->name('edit');
                Route::put('/{seatPlan}', [App\Http\Controllers\Principal\SeatPlanController::class,'update'])->name('update');
                Route::delete('/{seatPlan}', [App\Http\Controllers\Principal\SeatPlanController::class,'destroy'])->name('destroy');
                
                // Room Management
                Route::get('/{seatPlan}/rooms', [App\Http\Controllers\Principal\SeatPlanController::class,'manageRooms'])->name('rooms');
                Route::post('/{seatPlan}/rooms', [App\Http\Controllers\Principal\SeatPlanController::class,'storeRoom'])->name('rooms.store');
                Route::get('/{seatPlan}/rooms/{room}/edit', [App\Http\Controllers\Principal\SeatPlanController::class,'editRoom'])->name('rooms.edit');
                Route::put('/{seatPlan}/rooms/{room}', [App\Http\Controllers\Principal\SeatPlanController::class,'updateRoom'])->name('rooms.update');
                Route::delete('/{seatPlan}/rooms/{room}', [App\Http\Controllers\Principal\SeatPlanController::class,'destroyRoom'])->name('rooms.destroy');
                
                // Seat Allocation
                Route::get('/{seatPlan}/allocate', [App\Http\Controllers\Principal\SeatPlanController::class,'allocateSeats'])->name('allocate');
                Route::post('/{seatPlan}/allocate', [App\Http\Controllers\Principal\SeatPlanController::class,'storeAllocation'])->name('allocate.store');
                Route::delete('/{seatPlan}/allocations/{allocation}', [App\Http\Controllers\Principal\SeatPlanController::class,'removeAllocation'])->name('allocations.remove');
                Route::get('/{seatPlan}/search-students', [App\Http\Controllers\Principal\SeatPlanController::class,'searchStudents'])->name('search-students');
                Route::get('/{seatPlan}/find-student', [App\Http\Controllers\Principal\SeatPlanController::class,'findStudent'])->name('find-student');
                
                // Print
                Route::get('/{seatPlan}/rooms/{room}/print', [App\Http\Controllers\Principal\SeatPlanController::class,'printRoom'])->name('rooms.print');
                Route::get('/{seatPlan}/print-all', [App\Http\Controllers\Principal\SeatPlanController::class,'printAll'])->name('print-all');
            });

            // Result Management Routes
            Route::prefix('results')->name('results.')->group(function(){
                // Marksheet
                Route::get('/marksheet', [App\Http\Controllers\Principal\ResultController::class,'marksheet'])->name('marksheet');
                Route::get('/marksheet/{exam}/{student}/print', [App\Http\Controllers\Principal\ResultController::class,'printMarksheet'])->name('marksheet.print');
                
                // Merit List
                Route::get('/merit-list', [App\Http\Controllers\Principal\ResultController::class,'meritList'])->name('merit-list');
                Route::get('/merit-list/{exam}/{classId}/print', [App\Http\Controllers\Principal\ResultController::class,'printMeritList'])->name('merit-list.print');
                
                // Tabulation Sheet
                Route::get('/tabulation', [App\Http\Controllers\Principal\ResultController::class,'tabulation'])->name('tabulation');
                Route::get('/tabulation/{exam}/{classId}/print', [App\Http\Controllers\Principal\ResultController::class,'printTabulation'])->name('tabulation.print');
                
                // Statistics
                Route::get('/statistics', [App\Http\Controllers\Principal\ResultController::class,'statistics'])->name('statistics');
                
                // Publish/Unpublish
                Route::post('/{exam}/publish', [App\Http\Controllers\Principal\ResultController::class,'publishResults'])->name('publish');
                Route::post('/{exam}/unpublish', [App\Http\Controllers\Principal\ResultController::class,'unpublishResults'])->name('unpublish');
            });

            // Holiday management (per school)
            Route::prefix('settings')->group(function(){
                Route::get('holidays', [\App\Http\Controllers\Principal\HolidayController::class,'index'])->name('holidays.index');
                Route::post('holidays', [\App\Http\Controllers\Principal\HolidayController::class,'store'])->name('holidays.store');
                Route::patch('holidays/{holiday}', [\App\Http\Controllers\Principal\HolidayController::class,'update'])->name('holidays.update');
                Route::delete('holidays/{holiday}', [\App\Http\Controllers\Principal\HolidayController::class,'destroy'])->name('holidays.destroy');
                Route::post('weekly-holidays', [\App\Http\Controllers\Principal\HolidayController::class,'updateWeekly'])->name('weekly-holidays.update');
                // SMS Settings
                Route::get('sms', [\App\Http\Controllers\Principal\SmsSettingsController::class,'index'])->name('sms.index');
                Route::post('sms/api', [\App\Http\Controllers\Principal\SmsSettingsController::class,'saveApi'])->name('sms.api.save');
                Route::post('sms/attendance', [\App\Http\Controllers\Principal\SmsSettingsController::class,'saveAttendance'])->name('sms.attendance.save');
                Route::post('sms/templates', [\App\Http\Controllers\Principal\SmsSettingsController::class,'storeTemplate'])->name('sms.templates.store');
                Route::patch('sms/templates/{template}', [\App\Http\Controllers\Principal\SmsSettingsController::class,'updateTemplate'])->name('sms.templates.update');
                Route::delete('sms/templates/{template}', [\App\Http\Controllers\Principal\SmsSettingsController::class,'destroyTemplate'])->name('sms.templates.destroy');
                // SMS Panel + Logs
                Route::get('sms/panel', [\App\Http\Controllers\Principal\SmsController::class,'panel'])->name('sms.panel');
                Route::post('sms/send', [\App\Http\Controllers\Principal\SmsController::class,'send'])->name('sms.send');
                Route::get('sms/logs', [\App\Http\Controllers\Principal\SmsController::class,'logs'])->name('sms.logs');
                Route::get('sms/logs/{log}', [\App\Http\Controllers\Principal\SmsController::class,'view'])->name('sms.logs.view');
                // Online Payments (SSLCommerz)
                Route::get('payments', [PrincipalPaymentSettingsController::class,'index'])->name('payments.index');
                Route::post('payments', [PrincipalPaymentSettingsController::class,'save'])->name('payments.save');
            });

            Route::resource('shifts', PrincipalShiftController::class)->except(['show']);
            Route::resource('sections', PrincipalSectionController::class)->except(['show']);
            Route::resource('groups', PrincipalGroupController::class)->except(['show']);
            Route::resource('classes', PrincipalClassController::class)->except(['show']);
            Route::resource('subjects', \App\Http\Controllers\Principal\SubjectController::class)->except(['show']);
            Route::resource('academic-years', \App\Http\Controllers\Principal\AcademicYearController::class)->except(['show']);
            Route::patch('academic-years/{academic_year}/current', [\App\Http\Controllers\Principal\AcademicYearController::class,'setCurrent'])->name('academic-years.set-current');
            
            // Extra Classes routes
            Route::prefix('extra-classes')->name('extra-classes.')->group(function () {
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
                });
            });
            
            // Bulk student import routes should be defined before the resource route
            // to avoid the 'bulk' segment being interpreted as a student ID.
            Route::get('students/bulk', [\App\Http\Controllers\Principal\StudentController::class,'bulkForm'])->name('students.bulk');
            Route::post('students/bulk', [\App\Http\Controllers\Principal\StudentController::class,'bulkImport'])->name('students.bulk.import');
            Route::get('students/bulk/template', [\App\Http\Controllers\Principal\StudentController::class,'bulkTemplate'])->name('students.bulk.template');
            Route::post('students/bulk/queue', [\App\Http\Controllers\Principal\StudentController::class,'bulkEnqueue'])->name('students.bulk.queue');
            Route::get('students/bulk/status/{id}', [\App\Http\Controllers\Principal\StudentController::class,'bulkStatus'])->name('students.bulk.status');
            Route::get('students/bulk/report/{id}', [\App\Http\Controllers\Principal\StudentController::class,'bulkReport'])->name('students.bulk.report');

            Route::resource('students', \App\Http\Controllers\Principal\StudentController::class);
            Route::patch('students/{student}/status', [\App\Http\Controllers\Principal\StudentController::class,'toggleStatus'])->name('students.toggle-status');
            Route::post('students/{student}/enrollments', [\App\Http\Controllers\Principal\StudentController::class,'addEnrollment'])->name('students.enrollments.add');
            Route::delete('students/{student}/enrollments/{enrollment}', [\App\Http\Controllers\Principal\StudentController::class,'removeEnrollment'])->name('students.enrollments.remove');
            Route::post('students/{student}/teams', [\App\Http\Controllers\Principal\StudentController::class,'attachTeam'])->name('students.teams.attach');
            Route::delete('students/{student}/teams/{team}', [\App\Http\Controllers\Principal\StudentController::class,'detachTeam'])->name('students.teams.detach');
            // Student subject assignment
            Route::get('enrollments/{enrollment}/subjects', [\App\Http\Controllers\Principal\StudentSubjectController::class,'edit'])->name('enrollments.subjects.edit');
            Route::post('enrollments/{enrollment}/subjects', [\App\Http\Controllers\Principal\StudentSubjectController::class,'update'])->name('enrollments.subjects.update');
            // Meta endpoints for dynamic dropdowns
            Route::get('meta/sections', [\App\Http\Controllers\Principal\MetaController::class,'sections'])->name('meta.sections');
            Route::get('meta/students', [\App\Http\Controllers\Principal\MetaController::class,'students'])->name('meta.students');
            Route::get('meta/groups', [\App\Http\Controllers\Principal\MetaController::class,'groups'])->name('meta.groups');
            Route::get('meta/next-roll', [\App\Http\Controllers\Principal\MetaController::class,'nextRoll'])->name('meta.next-roll');
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
            });

            // Teams CRUD
            Route::resource('teams', PrincipalTeamController::class)->except(['show']);
            Route::get('teams/{team}/add-students', [PrincipalTeamController::class, 'addStudents'])->name('teams.add-students');
            Route::post('teams/{team}/add-students', [PrincipalTeamController::class, 'storeStudents'])->name('teams.store-students');
            Route::get('teams/{team}/members', [PrincipalTeamController::class, 'members'])->name('teams.members');

            // Documents (Prottayon, Certificate, Testimonial, Settings)
            Route::prefix('documents')->name('documents.')->group(function(){
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
                Route::post('/testimonial/generate', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'generate'])->name('testimonial.generate');
                Route::get('/testimonial/print/{document}', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'print'])->name('testimonial.print');
                Route::get('/testimonial/history', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'history'])->name('testimonial.history');
                Route::get('/testimonial/{document}/edit', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'edit'])->name('testimonial.edit');
                Route::put('/testimonial/{document}', [\App\Http\Controllers\Principal\Documents\TestimonialController::class, 'update'])->name('testimonial.update');

                // Settings (backgrounds, colors for print pages)
                Route::get('/settings', [\App\Http\Controllers\Principal\Documents\SettingsController::class, 'index'])->name('settings.index');
                Route::post('/settings', [\App\Http\Controllers\Principal\Documents\SettingsController::class, 'store'])->name('settings.store');
            });
        });
    });

    // Teacher Routes (role-protected)
    Route::prefix('teacher')->name('teacher.')->middleware(['role:teacher'])->group(function () {
        Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
        
        // Teacher Attendance
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [App\Http\Controllers\Teacher\AttendanceController::class, 'index'])->name('index');
            Route::post('/check-in', [App\Http\Controllers\Teacher\AttendanceController::class, 'checkIn'])->name('check-in');
            Route::post('/check-out', [App\Http\Controllers\Teacher\AttendanceController::class, 'checkOut'])->name('check-out');
            Route::get('/my-attendance', [App\Http\Controllers\Teacher\AttendanceController::class, 'myAttendance'])->name('my-attendance');
        });

        // Teacher Leaves
        Route::prefix('leave')->name('leave.')->group(function () {
            Route::get('/', [App\Http\Controllers\Teacher\LeaveController::class, 'index'])->name('index');
            Route::get('/apply', [App\Http\Controllers\Teacher\LeaveController::class, 'create'])->name('create');
            Route::post('/apply', [App\Http\Controllers\Teacher\LeaveController::class, 'store'])->name('store');
        });

        // School-specific teacher routes
        Route::prefix('institute/{school}')->name('institute.')->middleware(['role:teacher,school'])->group(function () {
            // Student Attendance (Class/Section based)
            Route::prefix('attendance/class')->name('attendance.class.')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\StudentAttendanceController::class, 'index'])->name('index');
                Route::get('/take', [App\Http\Controllers\Teacher\StudentAttendanceController::class, 'take'])->name('take');
                Route::post('/store', [App\Http\Controllers\Teacher\StudentAttendanceController::class, 'store'])->name('store');
            });

            // Extra Class Attendance (teacher-assigned)
            Route::prefix('attendance/extra-classes')->name('attendance.extra-classes.')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\ExtraClassAttendanceController::class, 'index'])->name('index');
                Route::get('/take', [App\Http\Controllers\Teacher\ExtraClassAttendanceController::class, 'take'])->name('take');
                Route::post('/store', [App\Http\Controllers\Teacher\ExtraClassAttendanceController::class, 'store'])->name('store');
            });

            // Team Attendance (stub; to be implemented)
            Route::prefix('attendance/team')->name('attendance.team.')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\TeamAttendanceController::class, 'index'])->name('index');
            });

            // Lesson Evaluation
            Route::prefix('lesson-evaluation')->name('lesson-evaluation.')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\LessonEvaluationController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Teacher\LessonEvaluationController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Teacher\LessonEvaluationController::class, 'store'])->name('store');
                Route::get('/{lessonEvaluation}', [App\Http\Controllers\Teacher\LessonEvaluationController::class, 'show'])->name('show');
            });

            // Homework
            Route::prefix('homework')->name('homework.')->group(function () {
                Route::get('/', [App\Http\Controllers\Teacher\HomeworkController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Teacher\HomeworkController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Teacher\HomeworkController::class, 'store'])->name('store');
                Route::get('/{homework}', [App\Http\Controllers\Teacher\HomeworkController::class, 'show'])->name('show');
                Route::delete('/{homework}', [App\Http\Controllers\Teacher\HomeworkController::class, 'destroy'])->name('destroy');
            });

            // Directories (Students and Teachers)
            Route::prefix('directory')->name('directory.')->group(function(){
                Route::get('/students', [App\Http\Controllers\Teacher\DirectoryController::class, 'students'])->name('students');
                Route::get('/students/{student}', [App\Http\Controllers\Teacher\DirectoryController::class, 'studentShow'])->name('students.show');
                Route::get('/teachers', [App\Http\Controllers\Teacher\DirectoryController::class, 'teachers'])->name('teachers');
            });

            // Billing collection page for teachers (restricted by assigned class in controller)
            Route::get('/billing/collect', [App\Http\Controllers\Teacher\Billing\CollectController::class, 'create'])->name('billing.collect');
        });
    });

    // Parent Routes (role-protected)
    Route::prefix('parent')->name('parent.')->middleware(['role:parent'])->group(function () {
        Route::get('/dashboard', [ParentController::class, 'dashboard'])->name('dashboard');
    });

    // Billing blades (simple views; access limited by nav visibility and auth)
    Route::get('/billing/due', function () { return view('billing.due'); })->name('billing.due');
    Route::get('/billing/statement', function () { return view('billing.statement'); })->name('billing.statement');
    Route::get('/billing/collect', function () { return view('billing.collect'); })->name('billing.collect');

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
    Route::get('/billing/settings/fines', [\App\Http\Controllers\Billing\SettingsController::class, 'fineIndex'])->name('billing.settings.fines');
    Route::post('/billing/settings/fines', [\App\Http\Controllers\Billing\SettingsController::class, 'fineStore'])->name('billing.settings.fines.store');
});

// Public document verification endpoint (QR target)
Route::get('/verify/document/{code}', [\App\Http\Controllers\Documents\VerificationController::class, 'show'])->name('documents.verify');
