<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\StudentEnrollment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\RoutineEntry;
use App\Models\LessonEvaluation;
use App\Models\LessonEvaluationRecord;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Homework;
use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\StaffMember;
use App\Models\StaffAttendance;
use App\Models\Payment;
use App\Models\Holiday;
use App\Models\WeeklyHoliday;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrincipalReportController extends Controller
{
    /**
     * High-level overview data (school info, counts, module flags, fees)
     * for the principal web dashboard.
     */
    public function dashboardOverview(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);

        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }

        $school = School::find($schoolId);
        if (!$school) {
            return response()->json(['message' => 'School not found'], 404);
        }

        $currentYear = AcademicYear::forSchool($schoolId)->current()->first();
        $yearId = $currentYear?->id;

        $activeEnrollments = StudentEnrollment::join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.school_id', $schoolId)
            ->where('student_enrollments.status', 'active')
            ->where('students.status', 'active')
            ->when($yearId, fn($q) => $q->where('student_enrollments.academic_year_id', $yearId));

        $studentsTotal = (clone $activeEnrollments)->count('students.id');
        $studentsMale = (clone $activeEnrollments)->where('students.gender', 'male')->count('students.id');
        $studentsFemale = (clone $activeEnrollments)->where('students.gender', 'female')->count('students.id');

        $activeTeachers = Teacher::where('school_id', $schoolId)->where('status', 'active');
        $teachersActive = (clone $activeTeachers)->count();
        $staffActive = StaffMember::where('school_id', $schoolId)->where('status', 'active')->count();
        $classesTotal = SchoolClass::where('school_id', $schoolId)->where('status', 'active')->count();
        $sectionsTotal = Section::where('school_id', $schoolId)->where('status', 'active')->count();

        $activeTeacherUserIds = (clone $activeTeachers)->pluck('user_id')->filter()->values();
        $teacherAttendanceTotal = $activeTeacherUserIds->count();
        $teacherAttendancePresent = 0;
        $teacherAttendanceAbsent = 0;
        if ($teacherAttendanceTotal > 0) {
            $teacherAttendancePresent = TeacherAttendance::where('school_id', $schoolId)
                ->whereIn('user_id', $activeTeacherUserIds)
                ->whereDate('date', now()->toDateString())
                ->whereIn('status', ['present', 'late'])
                ->count();
            $teacherAttendanceAbsent = TeacherAttendance::where('school_id', $schoolId)
                ->whereIn('user_id', $activeTeacherUserIds)
                ->whereDate('date', now()->toDateString())
                ->where('status', 'absent')
                ->count();
        }

        $activeStaffUserIds = StaffMember::where('school_id', $schoolId)->where('status', 'active')
            ->pluck('user_id')->filter()->values();
        $staffAttendanceTotal = $activeStaffUserIds->count();
        $staffAttendancePresent = 0;
        $staffAttendanceAbsent = 0;
        if ($staffAttendanceTotal > 0) {
            $staffAttendancePresent = StaffAttendance::where('school_id', $schoolId)
                ->whereIn('user_id', $activeStaffUserIds)
                ->whereDate('date', now()->toDateString())
                ->whereIn('status', ['present', 'late'])
                ->count();
            $staffAttendanceAbsent = StaffAttendance::where('school_id', $schoolId)
                ->whereIn('user_id', $activeStaffUserIds)
                ->whereDate('date', now()->toDateString())
                ->where('status', 'absent')
                ->count();
        }

        $moduleSlugs = ['attendance', 'lesson_evaluation', 'exams', 'results', 'routine', 'accounts', 'extra_class', 'notices', 'admission', 'sms', 'documents'];
        $modules = collect($moduleSlugs)->mapWithKeys(fn($slug) => [$slug => $school->hasModule($slug)]);

        $feesToday = null;
        $feesMonth = null;
        if ($modules['accounts']) {
            $feesToday = (float) Payment::where('school_id', $schoolId)
                ->where('status', 'settled')
                ->whereDate('received_at', now()->toDateString())
                ->sum('amount_paid');
            $feesMonth = (float) Payment::where('school_id', $schoolId)
                ->where('status', 'settled')
                ->whereMonth('received_at', now()->month)
                ->whereYear('received_at', now()->year)
                ->sum('amount_paid');
        }

        return response()->json([
            'data' => [
                'school' => [
                    'id' => $school->id,
                    'name' => $school->name,
                    'name_bn' => $school->name_bn,
                    'logo' => $school->logo ? asset('storage/' . $school->logo) : null,
                ],
                'academic_year' => $currentYear ? ['id' => $currentYear->id, 'name' => $currentYear->name] : null,
                'counts' => [
                    'students_total' => $studentsTotal,
                    'students_male' => $studentsMale,
                    'students_female' => $studentsFemale,
                    'teachers_active' => $teachersActive,
                    'staff_active' => $staffActive,
                    'classes_total' => $classesTotal,
                    'sections_total' => $sectionsTotal,
                ],
                'teacher_attendance' => [
                    'total' => $teacherAttendanceTotal,
                    'present' => $teacherAttendancePresent,
                    'absent' => $teacherAttendanceAbsent,
                    'percentage' => $teacherAttendanceTotal > 0 ? round(($teacherAttendancePresent / $teacherAttendanceTotal) * 100, 1) : null,
                ],
                'staff_attendance' => [
                    'total' => $staffAttendanceTotal,
                    'present' => $staffAttendancePresent,
                    'absent' => $staffAttendanceAbsent,
                    'percentage' => $staffAttendanceTotal > 0 ? round(($staffAttendancePresent / $staffAttendanceTotal) * 100, 1) : null,
                ],
                'fees' => [
                    'today' => $feesToday,
                    'month' => $feesMonth,
                ],
                'modules' => $modules,
            ],
        ]);
    }

    /**
     * Resolve the principal's school id the same way every method here does.
     */
    private function resolveSchoolId(Request $request): ?int
    {
        $user = $request->user();
        return $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);
    }

    /**
     * Backfill serial_number for any active teacher/staff row that doesn't
     * have one yet, so the attendance-report ordering (by serial) is always
     * well-defined instead of silently falling back to id order forever.
     * Assigns the next number after the current max, in id order.
     */
    private function assignMissingSerialNumbers($query): void
    {
        $missing = (clone $query)->whereNull('serial_number')->orderBy('id')->get();
        if ($missing->isEmpty()) {
            return;
        }
        $next = ((clone $query)->max('serial_number') ?? 0) + 1;
        foreach ($missing as $row) {
            $row->update(['serial_number' => $next]);
            $next++;
        }
    }

    /**
     * Working-day count for a school+month: calendar days minus the
     * school's weekly off-days (WeeklyHoliday) and explicit holidays
     * (Holiday). Shared denominator for every monthly attendance report.
     * Mirrors the same algorithm used by the web print report
     * (resources/views/principal/attendance/monthly_report_print.blade.php)
     * so mobile and web always agree.
     */
    private function workingDaysBreakdown(int $schoolId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $totalDays = $start->daysInMonth;

        $weeklyOffDays = WeeklyHoliday::forSchool($schoolId)->active()
            ->pluck('day_number')->map(fn ($d) => (int) $d)->all();

        $holidayDates = Holiday::forSchool($schoolId)->active()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')->map(fn ($d) => $d->toDateString())->all();

        $workingDays = 0;
        $workingDayDates = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            // WeeklyHoliday.day_number is ISO-8601 (1=Monday..7=Sunday) — use
            // dayOfWeekIso, not dayOfWeek (0=Sunday..6=Saturday), or a Sunday
            // weekly off-day would never match (see the same distinction
            // documented in LessonEvaluationController::holidayInfo()).
            if (in_array($cursor->dayOfWeekIso, $weeklyOffDays, true)) continue;
            if (in_array($cursor->toDateString(), $holidayDates, true)) continue;
            $workingDays++;
            $workingDayDates[] = $cursor->toDateString();
        }

        return ['total_days' => $totalDays, 'working_days' => $workingDays, 'working_day_dates' => $workingDayDates];
    }

    /**
     * Sort a collection of rows (each an array with a 'percentage' and a
     * tie-break count key) descending by percentage — nulls (no attendance
     * taken at all) sink to the bottom — and stamp a 1-based 'rank'.
     */
    private function rankByPercentage($rows, string $tieBreakKey)
    {
        return $rows->sort(function ($a, $b) use ($tieBreakKey) {
            if ($a['percentage'] === null && $b['percentage'] === null) return 0;
            if ($a['percentage'] === null) return 1;
            if ($b['percentage'] === null) return -1;
            if ($a['percentage'] == $b['percentage']) return $b[$tieBreakKey] <=> $a[$tieBreakKey];
            return $b['percentage'] <=> $a['percentage'];
        })->values()->map(function ($row, $idx) {
            $row['rank'] = $idx + 1;
            return $row;
        })->values();
    }

    public function attendanceSummary(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);

        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }

        $date = $request->query('date', now()->toDateString());
        $dayOfWeek = now()->format('l'); // Monday, Tuesday, etc.

        // 1. Regular Class Attendance
        $currentYear = AcademicYear::forSchool($schoolId)->current()->first();
        $yearId = $currentYear?->id;

        $totalActiveStudents = StudentEnrollment::join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.school_id', $schoolId)
            ->where('student_enrollments.status', 'active')
            ->where('students.status', 'active')
            ->when($yearId, fn($q) => $q->where('student_enrollments.academic_year_id', $yearId))
            ->count();
        
        $presentToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('attendance.date', $date)
            ->where('attendance.school_id', $schoolId)
            ->where('students.status', 'active')
            ->where('student_enrollments.status', 'active')
            ->whereIn('attendance.status', ['present','late'])
            ->when($yearId, fn($q) => $q->where('student_enrollments.academic_year_id', $yearId))
            ->count();

        // 2. Extra Class Attendance — use Eloquent whereHas (proven approach)
        $extraQuery = \App\Models\ExtraClassAttendance::whereHas(
            'extraClass', fn($q) => $q->where('school_id', $schoolId)
        )->whereHas('student', fn($q) => $q->where('status', 'active'))
        ->whereDate('date', $date);

        $extraClassPresent = (clone $extraQuery)
            ->whereIn('status', ['present', 'late'])
            ->count();

        // Total enrolled students in active extra classes
        $extraClassTotal = DB::table('extra_class_enrollments')
            ->join('extra_classes', 'extra_classes.id', '=', 'extra_class_enrollments.extra_class_id')
            ->join('students', 'students.id', '=', 'extra_class_enrollments.student_id')
            ->where('extra_classes.school_id', $schoolId)
            ->where('extra_classes.status', 'active')
            ->where('extra_class_enrollments.status', 'active')
            ->where('students.status', 'active')
            ->when($yearId, fn($q) => $q->where('extra_classes.academic_year_id', $yearId))
            ->count();

        // Total active extra classes with at least one active enrolled student
        $totalExtraClasses = DB::table('extra_classes')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($yearId, fn($q) => $q->where('extra_classes.academic_year_id', $yearId))
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('extra_class_enrollments')
                    ->join('students', 'students.id', '=', 'extra_class_enrollments.student_id')
                    ->whereColumn('extra_class_enrollments.extra_class_id', 'extra_classes.id')
                    ->where('extra_class_enrollments.status', 'active')
                    ->where('students.status', 'active');
            })
            ->count();

        // Distinct extra classes with any attendance today
        $extraClassesWithAtt = (clone $extraQuery)
            ->select('extra_class_id')
            ->pluck('extra_class_id')
            ->unique()
            ->count();

        // 3. Lesson Evaluation Stats
        $routineExpected = RoutineEntry::where('school_id', $schoolId)
            ->where('day_of_week', $dayOfWeek)
            ->count();

        $evaluationsDone = LessonEvaluation::where('school_id', $schoolId)
            ->whereDate('evaluation_date', $date)
            ->count();

        // Count distinct class-sections that had attendance taken today
        $classSectionsWithAtt = DB::table('attendance')
            ->join('students', 'students.id', '=', 'attendance.student_id')
            ->where('attendance.date', $date)
            ->where('attendance.school_id', $schoolId)
            ->select('attendance.class_id', 'attendance.section_id')
            ->distinct()
            ->get()
            ->count();

        // Total sections that have at least one active enrolled student in current year
        $totalClassSections = DB::table('sections')
            ->where('sections.school_id', $schoolId)
            ->where('sections.status', 'active')
            ->whereExists(function ($q) use ($schoolId, $yearId) {
                $q->select(DB::raw(1))
                    ->from('student_enrollments')
                    ->join('students', 'students.id', '=', 'student_enrollments.student_id')
                    ->whereColumn('student_enrollments.section_id', 'sections.id')
                    ->where('student_enrollments.school_id', $schoolId)
                    ->where('student_enrollments.status', 'active')
                    ->where('students.status', 'active')
                    ->when($yearId, fn($sq) => $sq->where('student_enrollments.academic_year_id', $yearId));
            })
            ->count();

        // 4. Teacher Attendance (today)
        $totalTeachers = Teacher::forSchool($schoolId)->active()->count();
        $teacherAttToday = TeacherAttendance::where('school_id', $schoolId)
            ->where('date', $date)
            ->get(['status']);
        $teacherPresentToday = $teacherAttToday->whereIn('status', ['present', 'late'])->count();
        $teacherMarkedToday = $teacherAttToday->count();

        // 5. Staff Attendance (today) — only staff with a login account can
        // ever check in, so that's the denominator (not every roster row).
        $totalStaff = StaffMember::where('school_id', $schoolId)->active()->whereNotNull('user_id')->count();
        $staffAttToday = StaffAttendance::where('school_id', $schoolId)
            ->where('date', $date)
            ->get(['status']);
        $staffPresentToday = $staffAttToday->whereIn('status', ['present', 'late'])->count();
        $staffMarkedToday = $staffAttToday->count();

        return response()->json([
            'data' => [
                'date' => $date,
                'class_attendance' => [
                    'total' => $totalActiveStudents,
                    'present' => $presentToday,
                    'percentage' => $totalActiveStudents > 0 ? round(($presentToday / $totalActiveStudents) * 100, 1) : 0,
                    'total_sections' => $totalClassSections,
                    'sections_with_attendance' => $classSectionsWithAtt,
                ],
                'extra_class_attendance' => [
                    'total' => $extraClassTotal,
                    'present' => $extraClassPresent,
                    'percentage' => $extraClassTotal > 0 ? round(($extraClassPresent / $extraClassTotal) * 100, 1) : 0,
                    'total_classes' => $totalExtraClasses,
                    'classes_with_attendance' => $extraClassesWithAtt,
                ],
                'teacher_attendance' => [
                    'total' => $totalTeachers,
                    'present' => $teacherPresentToday,
                    'marked' => $teacherMarkedToday,
                    'percentage' => $totalTeachers > 0 ? round(($teacherPresentToday / $totalTeachers) * 100, 1) : 0,
                ],
                'staff_attendance' => [
                    'total' => $totalStaff,
                    'present' => $staffPresentToday,
                    'marked' => $staffMarkedToday,
                    'percentage' => $totalStaff > 0 ? round(($staffPresentToday / $totalStaff) * 100, 1) : 0,
                ],
                'lesson_evaluation' => [
                    'total_expected' => $routineExpected,
                    'completed' => $evaluationsDone,
                    'not_done' => max(0, $routineExpected - $evaluationsDone),
                ],
            ]
        ]);
    }

    /**
     * Return detailed attendance breakdown for principal mobile UI.
     * Expects request attribute 'current_school_id' to be set by middleware.
     * Optional query param: date (YYYY-MM-DD). If absent uses today.
     */
    public function attendanceDetails(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }
        $date = $request->query('date', now()->toDateString());

        // Compute totals and breakdowns similar to Principal\AttendanceController::dashboard
        $currentYear = AcademicYear::forSchool($schoolId)->current()->first();
        $yearVal = $currentYear?->id;

        $totalStudents = StudentEnrollment::join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.school_id', $schoolId)
            ->where('student_enrollments.status', 'active')
            ->where('students.status', 'active')
            ->when($yearVal, fn($q)=>$q->where('student_enrollments.academic_year_id', $yearVal))
            ->count();

        $presentToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('attendance.date', $date)
            ->where('attendance.school_id', $schoolId)
            ->where('students.status', 'active')
            ->where('student_enrollments.status', 'active')
            ->whereIn('attendance.status', ['present','late'])
            ->when($yearVal, fn($q) => $q->where('student_enrollments.academic_year_id', $yearVal))
            ->count();

        $absentToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('attendance.date', $date)
            ->where('attendance.school_id', $schoolId)
            ->where('students.status', 'active')
            ->where('student_enrollments.status', 'active')
            ->where('attendance.status', 'absent')
            ->when($yearVal, fn($q) => $q->where('student_enrollments.academic_year_id', $yearVal))
            ->count();

        $anyAttendanceToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('attendance.date', $date)
            ->where('attendance.school_id', $schoolId)
            ->where('students.status', 'active')
            ->where('student_enrollments.status', 'active')
            ->when($yearVal, fn($q) => $q->where('student_enrollments.academic_year_id', $yearVal))
            ->exists();

        $attendancePercent = ($totalStudents > 0 && $anyAttendanceToday)
            ? round(($presentToday / $totalStudents) * 100, 1)
            : null;

        // Section totals
        $sectionTotals = StudentEnrollment::select(
                'classes.id as class_id','classes.name as class_name','classes.bangla_name as class_name_bn','classes.numeric_value',
                'sections.id as section_id','sections.name as section_name','sections.bangla_name as section_name_bn',
                'sections.class_teacher_name',
                'teachers.initials as class_teacher_initials',
                DB::raw('COUNT(DISTINCT student_enrollments.student_id) as total'),
                DB::raw("SUM(CASE WHEN students.gender='male' THEN 1 ELSE 0 END) as total_male"),
                DB::raw("SUM(CASE WHEN students.gender='female' THEN 1 ELSE 0 END) as total_female")
            )
            ->join('classes','student_enrollments.class_id','=','classes.id')
            ->join('sections','student_enrollments.section_id','=','sections.id')
            ->leftJoin('teachers', 'sections.class_teacher_id', '=', 'teachers.id')
            ->join('students','students.id','=','student_enrollments.student_id')
            ->where('student_enrollments.school_id', $schoolId)
            ->where('student_enrollments.status','active')
            ->where('students.status', 'active')
            ->where('sections.status','active')
            ->when($yearVal, fn($q)=>$q->where('student_enrollments.academic_year_id', $yearVal))
            ->groupBy('classes.id','classes.name','classes.bangla_name','classes.numeric_value','sections.id','sections.name','sections.bangla_name','sections.class_teacher_name','teachers.initials')
            ->get();

        $sectionTotals = $sectionTotals->sortBy(function($r){
            return sprintf('%05d|%s', (int)$r->numeric_value, (string)$r->section_name);
        })->values();

        $attendanceGender = Attendance::select(
                'attendance.class_id','attendance.section_id',
                DB::raw("SUM(CASE WHEN students.gender='male' AND attendance.status IN ('present','late') THEN 1 ELSE 0 END) as present_male"),
                DB::raw("SUM(CASE WHEN students.gender='female' AND attendance.status IN ('present','late') THEN 1 ELSE 0 END) as present_female"),
                DB::raw("SUM(CASE WHEN students.gender='male' AND attendance.status='absent' THEN 1 ELSE 0 END) as absent_male"),
                DB::raw("SUM(CASE WHEN students.gender='female' AND attendance.status='absent' THEN 1 ELSE 0 END) as absent_female"),
                DB::raw("COUNT(DISTINCT CASE WHEN attendance.status IN ('present','late') THEN attendance.student_id END) as present_total"),
                DB::raw("COUNT(DISTINCT CASE WHEN attendance.status='absent' THEN attendance.student_id END) as absent_total")
            )
            ->join('students','students.id','=','attendance.student_id')
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('students.status','active')
            ->where('student_enrollments.status','active')
            ->where('attendance.date',$date)
            ->when($yearVal, fn($q) => $q->where('student_enrollments.academic_year_id', $yearVal))
            ->groupBy('attendance.class_id','attendance.section_id')
            ->get()
            ->mapWithKeys(function($r){
                $key = "{$r->class_id}|{$r->section_id}";
                return [$key => [
                    'present_male' => (int)$r->present_male,
                    'present_female' => (int)$r->present_female,
                    'absent_male' => (int)$r->absent_male,
                    'absent_female' => (int)$r->absent_female,
                    'present_total' => (int)$r->present_total,
                    'absent_total' => (int)$r->absent_total,
                ]];
            });

        $attendanceExists = Attendance::select('class_id','section_id')
            ->where('date',$date)
            ->distinct()->get()
            ->mapWithKeys(fn($r)=>["{$r->class_id}|{$r->section_id}"=>true]);

        $classBreakdown = [];
        $grandTotal = 0; $grandPresent = 0;
        foreach ($sectionTotals as $row) {
            $key = $row->class_id;
            $attKey = "{$row->class_id}|{$row->section_id}";
            $genderAtt = $attendanceGender->get($attKey, [
                'present_male'=>0,'present_female'=>0,'absent_male'=>0,'absent_female'=>0,'present_total'=>0,'absent_total'=>0
            ]);
            if (!isset($classBreakdown[$key])) {
                $classBreakdown[$key] = [
                    'class_id' => $row->class_id,
                    'class_name' => $row->class_name,
                    'class_name_bn' => $row->class_name_bn,
                    'numeric_value' => $row->numeric_value,
                    'sections' => [],
                    'total' => 0,
                    'total_male' => 0,
                    'total_female' => 0,
                    'present_male' => 0,
                    'present_female' => 0,
                    'absent_male' => 0,
                    'absent_female' => 0,
                    'present_total' => 0,
                    'absent_total' => 0,
                ];
            }
            $classBreakdown[$key]['sections'][] = [
                'section_id' => $row->section_id,
                'section_name' => $row->section_name,
                'section_name_bn' => $row->section_name_bn,
                'class_teacher_name' => $row->class_teacher_name ?? null,
                'class_teacher_initials' => $row->class_teacher_initials ?? null,
                'total' => (int)$row->total,
                'total_male' => (int)$row->total_male,
                'total_female' => (int)$row->total_female,
                'present_male' => $genderAtt['present_male'],
                'absent_male' => $genderAtt['absent_male'],
                'present_female' => $genderAtt['present_female'],
                'absent_female' => $genderAtt['absent_female'],
                'present_total' => $genderAtt['present_total'],
                'absent_total' => $genderAtt['absent_total'],
                'att_taken' => (bool)($attendanceExists["{$row->class_id}|{$row->section_id}"] ?? false),
            ];
            $classBreakdown[$key]['total'] += (int)$row->total;
            $classBreakdown[$key]['total_male'] += (int)$row->total_male;
            $classBreakdown[$key]['total_female'] += (int)$row->total_female;
            $classBreakdown[$key]['present_male'] += $genderAtt['present_male'];
            $classBreakdown[$key]['present_female'] += $genderAtt['present_female'];
            $classBreakdown[$key]['absent_male'] += $genderAtt['absent_male'];
            $classBreakdown[$key]['absent_female'] += $genderAtt['absent_female'];
            $classBreakdown[$key]['present_total'] += $genderAtt['present_total'];
            $classBreakdown[$key]['absent_total'] += $genderAtt['absent_total'];
            $grandTotal += (int)$row->total;
            $grandPresent += $genderAtt['present_total'];
        }

        $classWise = collect($classBreakdown)->sortBy('numeric_value')->map(function($c){
            $anyAtt = false;
            foreach ($c['sections'] as $s) { if (!empty($s['att_taken'])) { $anyAtt = true; break; } }
            $c['any_att'] = $anyAtt;
            $c['percentage'] = ($c['total']>0 && $anyAtt) ? round(($c['present_total']/$c['total'])*100,1) : null;
            return $c;
        })->values();

        $grandPercent = ($grandTotal>0 && $anyAttendanceToday) ? round(($grandPresent/$grandTotal)*100,1) : null;

        $genderCounts = Attendance::select('students.gender', DB::raw('COUNT(DISTINCT attendance.student_id) as cnt'))
            ->join('students','students.id','=','attendance.student_id')
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('attendance.date', $date)
            ->where('attendance.school_id', $schoolId)
            ->where('students.status', 'active')
            ->where('student_enrollments.status', 'active')
            ->whereIn('attendance.status', ['present','late'])
            ->when($yearVal, fn($q) => $q->where('student_enrollments.academic_year_id', $yearVal))
            ->groupBy('students.gender')
            ->pluck('cnt','gender');

        return response()->json([
            'data' => [
                'date' => $date,
                'total_students' => $totalStudents,
                'present_today' => $presentToday,
                'absent_today' => $absentToday,
                'present_percentage' => $attendancePercent,
                'class_wise' => $classWise,
                'grand_total' => $grandTotal,
                'grand_present' => $grandPresent,
                'grand_percent' => $grandPercent,
            ],
            'meta' => ['message' => 'success', 'gender_counts' => $genderCounts->toArray()]
        ]);
    }

    public function examResultsSummary(Request $request)
    {
        return response()->json([
            'data' => [
                'average_score' => null,
                'top_students' => [],
            ],
            'meta' => ['message' => 'exam results summary placeholder']
        ]);
    }

    /**
     * For a given date, list every routine period scheduled that day
     * (school-wide, optionally filtered) and mark whether a lesson
     * evaluation has been submitted for it. Used by the mobile app's
     * "Lesson Evaluation Report" screen to show a tick/cross per period.
     */
    public function lessonEvaluationPeriods(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }

        $dateParam = $request->query('date');
        $date = $dateParam ? Carbon::parse($dateParam)->startOfDay() : Carbon::today();
        $dayName = strtolower($date->format('l'));

        $entriesQuery = RoutineEntry::with(['class', 'section', 'subject', 'teacher.user'])
            ->where('school_id', $schoolId)
            ->where('day_of_week', $dayName);

        if ($request->filled('class_id')) {
            $entriesQuery->where('class_id', $request->get('class_id'));
        }
        if ($request->filled('section_id')) {
            $entriesQuery->where('section_id', $request->get('section_id'));
        }
        if ($request->filled('subject_id')) {
            $entriesQuery->where('subject_id', $request->get('subject_id'));
        }
        if ($request->filled('teacher_id')) {
            $entriesQuery->where('teacher_id', $request->get('teacher_id'));
        } elseif ($request->filled('teacher')) {
            $teacher = $request->get('teacher');
            if (is_numeric($teacher)) {
                $entriesQuery->where('teacher_id', $teacher);
            } else {
                $entriesQuery->whereHas('teacher.user', function ($q) use ($teacher) {
                    $q->where('name', 'like', "%{$teacher}%");
                });
            }
        }

        $entries = $entriesQuery->orderBy('period_number')->get();

        // Bulk-fetch all of this school's evaluations for the date (not just
        // those linking to today's entries) so we can match both by
        // routine_entry_id (normal case) and, for older records predating
        // that column, by teacher/class/section/subject as a fallback.
        $dayEvaluations = LessonEvaluation::forSchool($schoolId)
            ->forDate($date->toDateString())
            ->get();
        $byRoutineEntry = $dayEvaluations->whereNotNull('routine_entry_id')->keyBy('routine_entry_id');
        $byCompositeKey = $dayEvaluations->whereNull('routine_entry_id')->keyBy(
            fn($ev) => $ev->teacher_id . ':' . $ev->class_id . ':' . $ev->section_id . ':' . $ev->subject_id
        );

        $evaluationFor = function ($entry) use ($byRoutineEntry, $byCompositeKey) {
            if ($byRoutineEntry->has($entry->id)) {
                return $byRoutineEntry->get($entry->id);
            }
            $key = $entry->teacher_id . ':' . $entry->class_id . ':' . $entry->section_id . ':' . $entry->subject_id;
            return $byCompositeKey->get($key);
        };

        // For un-evaluated periods, batch-count enrolled students per
        // class/section pair so we can still show "total students".
        $academicYearId = AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');
        $unevaluatedPairs = $entries
            ->reject(fn($e) => $evaluationFor($e) !== null)
            ->map(fn($e) => $e->class_id . ':' . $e->section_id)
            ->unique();
        $enrollmentCounts = [];
        foreach ($unevaluatedPairs as $pair) {
            [$classId, $sectionId] = explode(':', $pair);
            $enrollmentCounts[$pair] = StudentEnrollment::where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('status', 'active')
                ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
                ->whereHas('student', fn($q) => $q->where('status', 'active'))
                ->count();
        }

        $items = $entries->map(function ($entry) use ($evaluationFor, $enrollmentCounts) {
            $evaluation = $evaluationFor($entry);
            $teacherName = null;
            try {
                $teacherName = $entry->teacher?->user?->name ?? $entry->teacher?->name ?? null;
            } catch (\Throwable $_) {}

            $base = [
                'routine_entry_id' => $entry->id,
                'period_number' => $entry->period_number,
                'start_time' => $entry->start_time ? substr($entry->start_time, 0, 5) : null,
                'end_time' => $entry->end_time ? substr($entry->end_time, 0, 5) : null,
                'class_id' => $entry->class_id,
                'section_id' => $entry->section_id,
                'subject_id' => $entry->subject_id,
                'class_name' => $entry->class?->name,
                'section_name' => $entry->section?->name,
                'subject_name' => $entry->subject?->name,
                'teacher_id' => $entry->teacher_id,
                'teacher_name' => $teacherName,
            ];

            if ($evaluation) {
                return array_merge($base, [
                    'evaluated' => true,
                    'id' => $evaluation->id,
                    'evaluation_date' => $evaluation->evaluation_date?->toDateString(),
                    'evaluation_time' => $evaluation->evaluation_time?->format('H:i'),
                    'submitted_at' => $evaluation->created_at?->format('Y-m-d H:i'),
                    'notes' => $evaluation->notes,
                    'status' => $evaluation->status,
                    'stats' => $evaluation->getCompletionStats(),
                ]);
            }

            $pairKey = $entry->class_id . ':' . $entry->section_id;
            return array_merge($base, [
                'evaluated' => false,
                'id' => null,
                'stats' => [
                    'total' => $enrollmentCounts[$pairKey] ?? 0,
                    'completed' => 0,
                    'partial' => 0,
                    'not_done' => 0,
                    'absent' => 0,
                    'completion_rate' => 0,
                ],
            ]);
        })->values();

        return response()->json([
            'date' => $date->toDateString(),
            'day_of_week' => $dayName,
            'items' => $items,
        ]);
    }

    public function lessonEvaluations(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }

        $query = \App\Models\LessonEvaluation::with(['teacher.user','class','section','subject', 'records' => function($q){
                $q->whereHas('student', function($s){ $s->where('status','active'); })->with(['student' => function($ss){ $ss->where('status','active'); }]);
            }])
            ->where('school_id', $schoolId);

        if ($request->filled('date')) {
            $query->whereDate('evaluation_date', $request->get('date'));
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->get('section_id'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->get('subject_id'));
        }
        // Filter evaluations that include records with a specific status
        if ($request->filled('status')) {
            $status = $request->get('status');
            $query->whereHas('records', function($q) use ($status) {
                $q->where('status', $status);
            });
        }
        // Filter by teacher (ID or name partial match)
        if ($request->filled('teacher')) {
            $teacher = $request->get('teacher');
            if (is_numeric($teacher)) {
                $query->where('teacher_id', $teacher);
            } else {
                $query->whereHas('teacher.user', function($q) use ($teacher) {
                    $q->where('name', 'like', "%{$teacher}%");
                });
            }
        }

        $items = $query->orderByDesc('evaluation_date')->paginate(25);

        $data = $items->map(function($ev) {
            $teacherName = null;
            try {
                $teacherName = $ev->teacher?->user?->name ?? $ev->teacher?->name ?? null;
            } catch (\Throwable $_) {}
            return [
                'id' => $ev->id,
                'evaluation_date' => $ev->evaluation_date?->toDateString(),
                'evaluation_time' => $ev->evaluation_time?->format('H:i'),
                'teacher_name' => $teacherName,
                'class_name' => $ev->class?->name,
                'section_name' => $ev->section?->name,
                'subject_name' => $ev->subject?->name,
                'notes' => $ev->notes,
                'status' => $ev->status,
                'stats' => $ev->getCompletionStats(),
            ];
        })->toArray();

        return response()->json([
            'data' => $data,
            'meta' => [
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Return a single lesson evaluation with all student records.
     * Used by mobile app details page to show the list of students.
     */
    public function lessonEvaluationDetail(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);
        
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }

        $evaluation = \App\Models\LessonEvaluation::with([
            'teacher.user',
            'class',
            'section',
            'subject',
            'routineEntry',
            'records' => function($q) {
                $q->whereHas('student', function($s) {
                    $s->where('status', 'active');
                })->with(['student' => function($ss) {
                    $ss->where('status', 'active');
                }]);
            }
        ])
        ->where('school_id', $schoolId)
        ->find($id);

        if (!$evaluation) {
            return response()->json(['message' => 'Evaluation not found'], 404);
        }

        $teacherName = null;
        try {
            $teacherName = $evaluation->teacher?->user?->name ?? $evaluation->teacher?->name ?? null;
        } catch (\Throwable $_) {}

        // "আগামীর পাঠ্য বিষয়" (next lesson's topic) isn't stored on the
        // evaluation itself — the submission form saves it as the paired
        // Homework record's description, keyed the same way it was created.
        $nextTopic = null;
        if ($evaluation->section_id) {
            $nextTopic = Homework::where('teacher_id', $evaluation->teacher_id)
                ->where('class_id', $evaluation->class_id)
                ->where('section_id', $evaluation->section_id)
                ->where('subject_id', $evaluation->subject_id)
                ->whereDate('homework_date', $evaluation->evaluation_date)
                ->value('description');
        }

        $records = $evaluation->records->map(function($record) {
            $student = $record->student;
            return [
                'id' => $record->id,
                'student_id' => $student?->id,
                'name' => $student?->full_name ?? $student?->student_name_bn ?? $student?->student_name_en,
                'roll_no' => $student?->roll_no,
                'status' => $record->status,
                'photo_url' => $student?->photo_url,
                'father_name' => $student?->father_name,
                'mother_name' => $student?->mother_name,
                'guardian_phone' => $student?->guardian_phone,
            ];
        })->toArray();

        return response()->json([
            'evaluation' => [
                'id' => $evaluation->id,
                'evaluation_date' => $evaluation->evaluation_date?->toDateString(),
                'evaluation_time' => $evaluation->evaluation_time?->format('h:i A'),
                'submitted_at' => $evaluation->created_at?->format('Y-m-d h:i A'),
                'period_number' => $evaluation->routineEntry?->period_number,
                'start_time' => $evaluation->routineEntry?->start_time
                    ? substr($evaluation->routineEntry->start_time, 0, 5)
                    : null,
                'end_time' => $evaluation->routineEntry?->end_time
                    ? substr($evaluation->routineEntry->end_time, 0, 5)
                    : null,
                'teacher_name' => $teacherName,
                'class_name' => $evaluation->class?->name,
                'section_name' => $evaluation->section?->name,
                'subject_name' => $evaluation->subject?->name,
                'notes' => $evaluation->notes,
                'next_topic' => $nextTopic,
                'status' => $evaluation->status,
                'stats' => $evaluation->getCompletionStats(),
                'records' => $records,
            ]
        ]);
    }

    /**
     * A student's lesson-evaluation profile for the principal — unlike the
     * teacher's per-subject studentHistory(), this spans every subject/every
     * teacher for the current academic year: subject-wise stat breakdown
     * plus a date-wise list optionally filtered to one subject.
     */
    public function lessonEvaluationStudentProfile(Request $request, $studentId)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }

        $student = Student::where('school_id', $schoolId)->find($studentId);
        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $academicYear = AcademicYear::where('school_id', $schoolId)->where('is_current', true)->first();

        $enrollment = StudentEnrollment::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->when($academicYear, fn($q) => $q->where('academic_year_id', $academicYear->id))
            ->with(['class', 'section', 'group'])
            ->orderByDesc('id')
            ->first();

        $subjectId = $request->query('subject_id') ? (int) $request->query('subject_id') : null;
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));

        $scopeEvaluations = function ($q) use ($schoolId, $academicYear, $subjectId) {
            $q->where('school_id', $schoolId);
            if ($academicYear) {
                $q->whereBetween('evaluation_date', [
                    $academicYear->start_date->toDateString(),
                    $academicYear->end_date->toDateString(),
                ]);
            }
            if ($subjectId) {
                $q->where('subject_id', $subjectId);
            }
        };

        // Subject-wise stat breakdown (always across ALL subjects, regardless
        // of the subject_id filter — that filter only narrows the entry list).
        $subjectStatsRaw = LessonEvaluationRecord::where('student_id', $student->id)
            ->whereHas('lessonEvaluation', function ($q) use ($schoolId, $academicYear) {
                $q->where('school_id', $schoolId);
                if ($academicYear) {
                    $q->whereBetween('evaluation_date', [
                        $academicYear->start_date->toDateString(),
                        $academicYear->end_date->toDateString(),
                    ]);
                }
            })
            ->join('lesson_evaluations', 'lesson_evaluations.id', '=', 'lesson_evaluation_records.lesson_evaluation_id')
            ->select('lesson_evaluations.subject_id', 'lesson_evaluation_records.status', DB::raw('count(*) as cnt'))
            ->groupBy('lesson_evaluations.subject_id', 'lesson_evaluation_records.status')
            ->get();

        $subjectNames = Subject::whereIn('id', $subjectStatsRaw->pluck('subject_id')->unique())->pluck('name', 'id');

        $subjectStats = [];
        foreach ($subjectStatsRaw->groupBy('subject_id') as $sid => $rows) {
            $counts = $rows->pluck('cnt', 'status');
            $subjectStats[] = [
                'subject_id' => $sid,
                'subject_name' => $subjectNames[$sid] ?? null,
                'total' => (int) $counts->sum(),
                'completed' => (int) ($counts['completed'] ?? 0),
                'partial' => (int) ($counts['partial'] ?? 0),
                'not_done' => (int) ($counts['not_done'] ?? 0),
                'absent' => (int) ($counts['absent'] ?? 0),
            ];
        }
        usort($subjectStats, fn ($a, $b) => strcmp((string) $a['subject_name'], (string) $b['subject_name']));

        $overall = [
            'total' => array_sum(array_column($subjectStats, 'total')),
            'completed' => array_sum(array_column($subjectStats, 'completed')),
            'partial' => array_sum(array_column($subjectStats, 'partial')),
            'not_done' => array_sum(array_column($subjectStats, 'not_done')),
            'absent' => array_sum(array_column($subjectStats, 'absent')),
        ];

        $evaluationsQuery = LessonEvaluation::where($scopeEvaluations)
            ->whereHas('records', fn ($q) => $q->where('student_id', $student->id))
            ->with(['records' => fn ($q) => $q->where('student_id', $student->id), 'subject', 'teacher.user'])
            ->orderByDesc('evaluation_date');

        $total = (clone $evaluationsQuery)->count();
        $evaluations = $evaluationsQuery->forPage($page, $perPage)->get();

        $entries = $evaluations->map(function ($ev) {
            $record = $ev->records->first();
            return [
                'date' => $ev->evaluation_date?->toDateString(),
                'subject_name' => $ev->subject?->name,
                'teacher_name' => $ev->teacher?->user?->name,
                'status' => $record?->status,
                'notes' => $ev->notes,
            ];
        })->values();

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'roll' => $enrollment?->roll_no,
                'photo_url' => $student->photo_url,
                'guardian_phone' => $student->guardian_phone,
                'class_name' => $enrollment?->class?->name,
                'section_name' => $enrollment?->section?->name,
                'group_name' => $enrollment?->group?->name,
            ],
            'academic_year' => $academicYear ? ['id' => $academicYear->id, 'name' => $academicYear->name] : null,
            'subjects' => Subject::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'subject_stats' => $subjectStats,
            'overall_summary' => $overall,
            'entries' => $entries,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }

    public function extraClassAttendanceDetails(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);
        
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }
        
                $date = $request->query('date', now()->toDateString());
        $currentYear = AcademicYear::forSchool($schoolId)->current()->first();
        $yearId = $currentYear?->id;


        $extraClasses = \DB::table('extra_classes')
            ->join('users', 'extra_classes.teacher_id', '=', 'users.id')
            ->leftJoin('teachers', 'users.id', '=', 'teachers.user_id')
            ->join('extra_class_enrollments', function($join) {
                $join->on('extra_classes.id', '=', 'extra_class_enrollments.extra_class_id')
                     ->where('extra_class_enrollments.status', 'active');
            })
            ->join('students', function($join) {
                $join->on('extra_class_enrollments.student_id', '=', 'students.id')
                     ->where('students.status', 'active');
            })
            ->where('extra_classes.school_id', $schoolId)
            ->where('extra_classes.status', 'active')
            ->when($yearId, fn($q) => $q->where('extra_classes.academic_year_id', $yearId))
            ->groupBy('extra_classes.id', 'extra_classes.name', 'users.name', 'teachers.initials')
            ->select(
                'extra_classes.id as section_id',
                'extra_classes.name as section_name',
                'users.name as class_teacher_name',
                'teachers.initials as class_teacher_initials',
                \DB::raw('COUNT(DISTINCT students.id) as total'),
                \DB::raw("SUM(CASE WHEN students.gender='male' THEN 1 ELSE 0 END) as total_male"),
                \DB::raw("SUM(CASE WHEN students.gender='female' THEN 1 ELSE 0 END) as total_female")
            )
            ->having('total', '>', 0)
            ->get();

        $attendances = \DB::table('extra_class_attendances')
            ->join('students', 'extra_class_attendances.student_id', '=', 'students.id')
            ->where('students.status', 'active')
            ->where('extra_class_attendances.date', $date)
            ->whereIn('extra_class_attendances.extra_class_id', $extraClasses->pluck('section_id'))
            ->select(
                'extra_class_attendances.extra_class_id as section_id',
                \DB::raw("SUM(CASE WHEN students.gender='male' AND extra_class_attendances.status IN ('present','late') THEN 1 ELSE 0 END) as present_male"),
                \DB::raw("SUM(CASE WHEN students.gender='female' AND extra_class_attendances.status IN ('present','late') THEN 1 ELSE 0 END) as present_female"),
                \DB::raw("SUM(CASE WHEN students.gender='male' AND extra_class_attendances.status='absent' THEN 1 ELSE 0 END) as absent_male"),
                \DB::raw("SUM(CASE WHEN students.gender='female' AND extra_class_attendances.status='absent' THEN 1 ELSE 0 END) as absent_female"),
                \DB::raw("COUNT(DISTINCT CASE WHEN extra_class_attendances.status IN ('present','late') THEN extra_class_attendances.student_id END) as present_total"),
                \DB::raw("COUNT(DISTINCT CASE WHEN extra_class_attendances.status='absent' THEN extra_class_attendances.student_id END) as absent_total")
            )
            ->groupBy('extra_class_attendances.extra_class_id')
            ->get()
            ->keyBy('section_id');

        $sections = [];
        $classTotal = 0;
        $classMale = 0;
        $classFemale = 0;
        $classPresent = 0;
        $classPresentMale = 0;
        $classPresentFemale = 0;
        $classAbsent = 0;
        $classAbsentMale = 0;
        $classAbsentFemale = 0;

        foreach ($extraClasses as $cls) {
            $att = $attendances->get($cls->section_id) ?? (object)[
                'present_male' => 0, 'present_female' => 0, 'absent_male' => 0, 'absent_female' => 0, 'present_total' => 0, 'absent_total' => 0
            ];
            $attTaken = isset($attendances[$cls->section_id]);

            $sections[] = [
                'section_id' => $cls->section_id,
                'section_name' => $cls->section_name,
                'class_teacher_name' => $cls->class_teacher_name,
                'class_teacher_initials' => $cls->class_teacher_initials ?? null,
                'total' => (int)$cls->total,
                'total_male' => (int)$cls->total_male,
                'total_female' => (int)$cls->total_female,
                'present_male' => (int)$att->present_male,
                'absent_male' => (int)$att->absent_male,
                'present_female' => (int)$att->present_female,
                'absent_female' => (int)$att->absent_female,
                'present_total' => (int)$att->present_total,
                'absent_total' => (int)$att->absent_total,
                'att_taken' => $attTaken,
            ];

            $classTotal += (int)$cls->total;
            $classMale += (int)$cls->total_male;
            $classFemale += (int)$cls->total_female;
            $classPresent += (int)$att->present_total;
            $classPresentMale += (int)$att->present_male;
            $classPresentFemale += (int)$att->present_female;
            $classAbsent += (int)$att->absent_total;
            $classAbsentMale += (int)$att->absent_male;
            $classAbsentFemale += (int)$att->absent_female;
        }

        $classWise = [
            [
                'class_id' => 1,
                'class_name' => 'Extra Classes',
                'numeric_value' => '1',
                'sections' => $sections,
                'total' => $classTotal,
                'total_male' => $classMale,
                'total_female' => $classFemale,
                'present_male' => $classPresentMale,
                'present_female' => $classPresentFemale,
                'absent_male' => $classAbsentMale,
                'absent_female' => $classAbsentFemale,
                'present_total' => $classPresent,
                'absent_total' => $classAbsent,
                'any_att' => count($attendances) > 0,
                'percentage' => ($classTotal > 0 && count($attendances) > 0) ? round(($classPresent / $classTotal) * 100, 1) : null,
            ]
        ];

        return response()->json([
            'data' => [
                'date' => $date,
                'class_wise' => $classWise,
            ]
        ]);
    }

    /**
     * Every active teacher's attendance status for a single date, plus a
     * summary. Used by the mobile "Teacher Attendance Report" Daily tab.
     */
    public function teacherAttendanceDetails(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }
        $date = $request->query('date', now()->toDateString());

        $this->assignMissingSerialNumbers(Teacher::forSchool($schoolId)->active());

        $teachers = Teacher::forSchool($schoolId)->active()->with('user')
            ->orderByRaw('COALESCE(serial_number, 999999) asc')
            ->orderBy('id')
            ->get();
        $attendances = TeacherAttendance::where('school_id', $schoolId)
            ->where('date', $date)
            ->get()
            ->keyBy('user_id');

        $present = 0; $late = 0; $absent = 0; $notMarked = 0;

        $list = $teachers->map(function ($t) use ($attendances, &$present, &$late, &$absent, &$notMarked) {
            $att = $attendances->get($t->user_id);
            $status = $att->status ?? 'not_marked';
            if ($status === 'present') $present++;
            elseif ($status === 'late') $late++;
            elseif ($status === 'absent') $absent++;
            else $notMarked++;

            $name = trim($t->first_name.' '.$t->last_name);

            return [
                'teacher_id' => $t->id,
                'user_id' => $t->user_id,
                'name' => $name !== '' ? $name : ($t->user->name ?? null),
                'name_bn' => trim(($t->first_name_bn ?? '').' '.($t->last_name_bn ?? '')) ?: null,
                'initials' => $t->initials,
                'designation' => $t->designation,
                'photo_url' => $t->photo_url,
                'serial_number' => $t->serial_number,
                'status' => $status,
                'check_in_time' => $att->check_in_time ?? null,
                'check_out_time' => $att->check_out_time ?? null,
                'remarks' => $att->remarks ?? null,
            ];
        })->values();

        $total = $teachers->count();
        $percentage = $total > 0 ? round((($present + $late) / $total) * 100, 1) : null;

        return response()->json([
            'data' => [
                'date' => $date,
                'summary' => [
                    'total' => $total,
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'not_marked' => $notMarked,
                    'percentage' => $percentage,
                ],
                'teachers' => $list,
            ],
        ]);
    }

    /**
     * Monthly teacher attendance: working days, days attendance was taken,
     * overall %, and a per-teacher leaderboard ranked by attendance %.
     */
    public function teacherAttendanceMonthly(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $wd = $this->workingDaysBreakdown($schoolId, $year, $month);

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $teachers = Teacher::forSchool($schoolId)->active()->with('user')->get();

        // Only count attendance recorded on an actual working day — matches
        // the web report's methodology and prevents days_attendance_taken
        // from ever exceeding working_days (e.g. a holiday punch shouldn't
        // inflate the count).
        $records = TeacherAttendance::where('school_id', $schoolId)
            ->whereBetween('date', [$start, $end])
            ->whereIn('date', $wd['working_day_dates'])
            ->get();

        $daysAttendanceTaken = $records->pluck('date')->map(fn ($d) => $d->toDateString())->unique()->count();
        $byTeacher = $records->groupBy('user_id');

        $totalPresent = 0; $totalLate = 0;

        $rows = $teachers->map(function ($t) use ($byTeacher, $daysAttendanceTaken, &$totalPresent, &$totalLate) {
            $recs = $byTeacher->get($t->user_id, collect());
            $present = $recs->where('status', 'present')->count();
            $late = $recs->where('status', 'late')->count();
            $explicitAbsent = $recs->where('status', 'absent')->count();
            $marked = $recs->count();
            $absentDays = max(0, $daysAttendanceTaken - $marked) + $explicitAbsent;
            $totalPresent += $present;
            $totalLate += $late;
            $pct = $daysAttendanceTaken > 0 ? round((($present + $late) / $daysAttendanceTaken) * 100, 1) : null;

            $name = trim($t->first_name.' '.$t->last_name);

            return [
                'teacher_id' => $t->id,
                'user_id' => $t->user_id,
                'name' => $name !== '' ? $name : ($t->user->name ?? null),
                'initials' => $t->initials,
                'designation' => $t->designation,
                'photo_url' => $t->photo_url,
                'present_days' => $present,
                'late_days' => $late,
                'absent_days' => $absentDays,
                'marked_days' => $marked,
                'percentage' => $pct,
            ];
        });

        $ranked = $this->rankByPercentage($rows, 'present_days');

        $totalTeachers = $teachers->count();
        $expected = $totalTeachers * $daysAttendanceTaken;
        $overallPercentage = $expected > 0 ? round((($totalPresent + $totalLate) / $expected) * 100, 1) : null;

        return response()->json([
            'data' => [
                'year' => $year,
                'month' => $month,
                'working_days' => $wd['working_days'],
                'total_days_in_month' => $wd['total_days'],
                'days_attendance_taken' => $daysAttendanceTaken,
                'total_teachers' => $totalTeachers,
                'overall' => [
                    'present' => $totalPresent,
                    'late' => $totalLate,
                    'percentage' => $overallPercentage,
                ],
                'teachers' => $ranked,
            ],
        ]);
    }

    /**
     * Monthly class (student) attendance per section: working days, days
     * taken, % attendance, and each section's rank among all of the
     * school's sections (শাখা) that month.
     */
    public function attendanceMonthly(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $wd = $this->workingDaysBreakdown($schoolId, $year, $month);

        // Resolve whichever academic year's date range actually contains the
        // requested report month, instead of always using whatever is
        // flagged is_current — "current" reflects today, not the month being
        // reported on, so a report for a month that belongs to an already-
        // finished academic year (e.g. right after the new year rolled over)
        // would otherwise pull the wrong year's roster and disagree with the
        // web report, where the principal explicitly picks academic_year_id.
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $yearId = AcademicYear::forSchool($schoolId)
            ->where('start_date', '<=', $monthEnd->toDateString())
            ->where('end_date', '>=', $monthStart->toDateString())
            ->value('id')
            ?? AcademicYear::forSchool($schoolId)->current()->value('id');
        $start = $monthStart->toDateString();
        $end = $monthEnd->toDateString();

        $sectionTotals = StudentEnrollment::select(
                'classes.id as class_id', 'classes.name as class_name', 'classes.bangla_name as class_name_bn', 'classes.numeric_value',
                'sections.id as section_id', 'sections.name as section_name', 'sections.bangla_name as section_name_bn',
                DB::raw('COUNT(DISTINCT student_enrollments.student_id) as total')
            )
            ->join('classes', 'student_enrollments.class_id', '=', 'classes.id')
            ->join('sections', 'student_enrollments.section_id', '=', 'sections.id')
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.school_id', $schoolId)
            ->where('student_enrollments.status', 'active')
            ->where('students.status', 'active')
            ->where('sections.status', 'active')
            ->when($yearId, fn ($q) => $q->where('student_enrollments.academic_year_id', $yearId))
            ->groupBy('classes.id', 'classes.name', 'classes.bangla_name', 'classes.numeric_value', 'sections.id', 'sections.name', 'sections.bangla_name')
            ->get();

        // whereIn(working_day_dates) keeps days_taken from ever exceeding
        // working_days (e.g. attendance recorded on a holiday shouldn't
        // inflate the count) — mirrors the web report's methodology.
        // 'half_day' counts as present, matching the web print report.
        //
        // Must join students and require students.status='active', same as
        // $sectionTotals above — otherwise a student whose account was later
        // deactivated (but whose enrollment row was never closed out) still
        // has their attendance rows summed into present_total here, while
        // $sectionTotals (the denominator) already excludes them. That
        // mismatch alone can make a section's own numerator/denominator
        // disagree, and made this diverge from the web report, which scopes
        // both sides to the same active-student roster.
        $attAgg = Attendance::select(
                'attendance.section_id',
                DB::raw('COUNT(DISTINCT attendance.date) as days_taken'),
                DB::raw("SUM(CASE WHEN attendance.status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present_total")
            )
            ->join('students', 'students.id', '=', 'attendance.student_id')
            ->where('attendance.school_id', $schoolId)
            ->where('students.status', 'active')
            ->whereBetween('attendance.date', [$start, $end])
            ->whereIn('attendance.date', $wd['working_day_dates'])
            ->groupBy('attendance.section_id')
            ->get()
            ->keyBy('section_id');

        $rows = $sectionTotals->map(function ($row) use ($attAgg) {
            $agg = $attAgg->get($row->section_id);
            $daysTaken = $agg ? (int) $agg->days_taken : 0;
            $present = $agg ? (int) $agg->present_total : 0;
            $expected = $row->total * $daysTaken;
            $pct = $expected > 0 ? round(($present / $expected) * 100, 1) : null;
            return [
                'class_id' => $row->class_id,
                'class_name' => $row->class_name,
                'class_name_bn' => $row->class_name_bn,
                'numeric_value' => $row->numeric_value,
                'section_id' => $row->section_id,
                'section_name' => $row->section_name,
                'section_name_bn' => $row->section_name_bn,
                'total_students' => (int) $row->total,
                'days_attendance_taken' => $daysTaken,
                'present_total' => $present,
                'percentage' => $pct,
            ];
        });

        $ranked = $this->rankByPercentage($rows, 'present_total');

        $byClass = $ranked->groupBy('class_id')->map(function ($sections) {
            $first = $sections->first();
            return [
                'class_id' => $first['class_id'],
                'class_name' => $first['class_name'],
                'class_name_bn' => $first['class_name_bn'],
                'numeric_value' => $first['numeric_value'],
                'sections' => $sections->sortBy('section_name')->values(),
            ];
        })->sortBy('numeric_value')->values();

        $totalStudentsAll = (int) $sectionTotals->sum('total');
        $daysAttendanceTakenSchool = Attendance::where('school_id', $schoolId)
            ->whereBetween('date', [$start, $end])
            ->whereIn('date', $wd['working_day_dates'])
            ->distinct('date')->count('date');
        $totalPresentAll = (int) $attAgg->sum('present_total');
        $expectedAll = $totalStudentsAll * $daysAttendanceTakenSchool;

        return response()->json([
            'data' => [
                'year' => $year,
                'month' => $month,
                'working_days' => $wd['working_days'],
                'total_days_in_month' => $wd['total_days'],
                'days_attendance_taken' => $daysAttendanceTakenSchool,
                'overall' => [
                    'total_students' => $totalStudentsAll,
                    'present_total' => $totalPresentAll,
                    'percentage' => $expectedAll > 0 ? round(($totalPresentAll / $expectedAll) * 100, 1) : null,
                ],
                'class_wise' => $byClass,
            ],
        ]);
    }

    /**
     * Monthly extra-class attendance, ranked the same way as
     * attendanceMonthly() but with each extra class treated as a branch.
     */
    public function extraClassAttendanceMonthly(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $wd = $this->workingDaysBreakdown($schoolId, $year, $month);

        $currentYear = AcademicYear::forSchool($schoolId)->current()->first();
        $yearId = $currentYear?->id;
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $extraClasses = DB::table('extra_classes')
            ->join('users', 'extra_classes.teacher_id', '=', 'users.id')
            ->leftJoin('teachers', 'users.id', '=', 'teachers.user_id')
            ->join('extra_class_enrollments', function ($join) {
                $join->on('extra_classes.id', '=', 'extra_class_enrollments.extra_class_id')
                     ->where('extra_class_enrollments.status', 'active');
            })
            ->join('students', function ($join) {
                $join->on('extra_class_enrollments.student_id', '=', 'students.id')
                     ->where('students.status', 'active');
            })
            ->where('extra_classes.school_id', $schoolId)
            ->where('extra_classes.status', 'active')
            ->when($yearId, fn ($q) => $q->where('extra_classes.academic_year_id', $yearId))
            ->groupBy('extra_classes.id', 'extra_classes.name', 'users.name', 'teachers.initials')
            ->select(
                'extra_classes.id as extra_class_id',
                'extra_classes.name as extra_class_name',
                'users.name as teacher_name',
                'teachers.initials as teacher_initials',
                DB::raw('COUNT(DISTINCT students.id) as total')
            )
            ->having('total', '>', 0)
            ->get();

        $extraClassIds = $extraClasses->pluck('extra_class_id');

        $attAgg = DB::table('extra_class_attendances')
            ->whereBetween('date', [$start, $end])
            ->whereIn('date', $wd['working_day_dates'])
            ->whereIn('extra_class_id', $extraClassIds)
            ->groupBy('extra_class_id')
            ->select(
                'extra_class_id',
                DB::raw('COUNT(DISTINCT date) as days_taken'),
                DB::raw("SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present_total")
            )
            ->get()
            ->keyBy('extra_class_id');

        $rows = $extraClasses->map(function ($cls) use ($attAgg) {
            $agg = $attAgg->get($cls->extra_class_id);
            $daysTaken = $agg ? (int) $agg->days_taken : 0;
            $present = $agg ? (int) $agg->present_total : 0;
            $expected = $cls->total * $daysTaken;
            $pct = $expected > 0 ? round(($present / $expected) * 100, 1) : null;
            return [
                'extra_class_id' => $cls->extra_class_id,
                'extra_class_name' => $cls->extra_class_name,
                'teacher_name' => $cls->teacher_name,
                'teacher_initials' => $cls->teacher_initials,
                'total_students' => (int) $cls->total,
                'days_attendance_taken' => $daysTaken,
                'present_total' => $present,
                'percentage' => $pct,
            ];
        });

        $ranked = $this->rankByPercentage($rows, 'present_total');

        $totalStudentsAll = (int) $extraClasses->sum('total');
        $daysAttendanceTakenSchool = DB::table('extra_class_attendances')
            ->whereIn('extra_class_id', $extraClassIds)
            ->whereBetween('date', [$start, $end])
            ->whereIn('date', $wd['working_day_dates'])
            ->distinct('date')->count('date');
        $totalPresentAll = (int) $attAgg->sum('present_total');
        $expectedAll = $totalStudentsAll * $daysAttendanceTakenSchool;

        return response()->json([
            'data' => [
                'year' => $year,
                'month' => $month,
                'working_days' => $wd['working_days'],
                'total_days_in_month' => $wd['total_days'],
                'days_attendance_taken' => $daysAttendanceTakenSchool,
                'overall' => [
                    'total_students' => $totalStudentsAll,
                    'present_total' => $totalPresentAll,
                    'percentage' => $expectedAll > 0 ? round(($totalPresentAll / $expectedAll) * 100, 1) : null,
                ],
                'extra_classes' => $ranked,
            ],
        ]);
    }

    /**
     * Every active staff member's attendance status for a single date, plus
     * a summary. Mirrors teacherAttendanceDetails() — used by the mobile
     * "Staff Attendance Report" Daily tab. Staff without a login account
     * yet (user_id null) are excluded since they can never check in.
     */
    public function staffAttendanceDetails(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }
        $date = $request->query('date', now()->toDateString());

        $this->assignMissingSerialNumbers(StaffMember::where('school_id', $schoolId)->active()->whereNotNull('user_id'));

        $staff = StaffMember::where('school_id', $schoolId)->active()->whereNotNull('user_id')->with(['user', 'designationRef'])
            ->orderByRaw('COALESCE(serial_number, 999999) asc')
            ->orderBy('id')
            ->get();
        $attendances = StaffAttendance::where('school_id', $schoolId)
            ->where('date', $date)
            ->get()
            ->keyBy('user_id');

        $present = 0; $late = 0; $absent = 0; $notMarked = 0;

        $list = $staff->map(function ($s) use ($attendances, &$present, &$late, &$absent, &$notMarked) {
            $att = $attendances->get($s->user_id);
            $status = $att->status ?? 'not_marked';
            if ($status === 'present') $present++;
            elseif ($status === 'late') $late++;
            elseif ($status === 'absent') $absent++;
            else $notMarked++;

            $name = trim($s->first_name.' '.$s->last_name);

            return [
                'staff_id' => $s->id,
                'user_id' => $s->user_id,
                'name' => $name !== '' ? $name : ($s->user->name ?? null),
                'name_bn' => $s->full_name_bn ?: null,
                'designation' => $s->designationRef ? ($s->designationRef->name_bn ?: $s->designationRef->name_en) : null,
                'photo_url' => $s->photo_url,
                'serial_number' => $s->serial_number,
                'status' => $status,
                'check_in_time' => $att->check_in_time ?? null,
                'check_out_time' => $att->check_out_time ?? null,
                'remarks' => $att->remarks ?? null,
            ];
        })->values();

        $total = $staff->count();
        $percentage = $total > 0 ? round((($present + $late) / $total) * 100, 1) : null;

        return response()->json([
            'data' => [
                'date' => $date,
                'summary' => [
                    'total' => $total,
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'not_marked' => $notMarked,
                    'percentage' => $percentage,
                ],
                'staff' => $list,
            ],
        ]);
    }

    /**
     * Monthly staff attendance: working days, days attendance was taken,
     * overall %, and a per-staff leaderboard ranked by attendance %.
     * Mirrors teacherAttendanceMonthly().
     */
    public function staffAttendanceMonthly(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $wd = $this->workingDaysBreakdown($schoolId, $year, $month);

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $staff = StaffMember::where('school_id', $schoolId)->active()->whereNotNull('user_id')->with(['user', 'designationRef'])->get();

        $records = StaffAttendance::where('school_id', $schoolId)
            ->whereBetween('date', [$start, $end])
            ->whereIn('date', $wd['working_day_dates'])
            ->get();

        $daysAttendanceTaken = $records->pluck('date')->map(fn ($d) => $d->toDateString())->unique()->count();
        $byStaff = $records->groupBy('user_id');

        $totalPresent = 0; $totalLate = 0;

        $rows = $staff->map(function ($s) use ($byStaff, $daysAttendanceTaken, &$totalPresent, &$totalLate) {
            $recs = $byStaff->get($s->user_id, collect());
            $present = $recs->where('status', 'present')->count();
            $late = $recs->where('status', 'late')->count();
            $explicitAbsent = $recs->where('status', 'absent')->count();
            $marked = $recs->count();
            $absentDays = max(0, $daysAttendanceTaken - $marked) + $explicitAbsent;
            $totalPresent += $present;
            $totalLate += $late;
            $pct = $daysAttendanceTaken > 0 ? round((($present + $late) / $daysAttendanceTaken) * 100, 1) : null;

            $name = trim($s->first_name.' '.$s->last_name);

            return [
                'staff_id' => $s->id,
                'user_id' => $s->user_id,
                'name' => $name !== '' ? $name : ($s->user->name ?? null),
                'designation' => $s->designationRef ? ($s->designationRef->name_bn ?: $s->designationRef->name_en) : null,
                'photo_url' => $s->photo_url,
                'present_days' => $present,
                'late_days' => $late,
                'absent_days' => $absentDays,
                'marked_days' => $marked,
                'percentage' => $pct,
            ];
        });

        $ranked = $this->rankByPercentage($rows, 'present_days');

        $totalStaff = $staff->count();
        $expected = $totalStaff * $daysAttendanceTaken;
        $overallPercentage = $expected > 0 ? round((($totalPresent + $totalLate) / $expected) * 100, 1) : null;

        return response()->json([
            'data' => [
                'year' => $year,
                'month' => $month,
                'working_days' => $wd['working_days'],
                'total_days_in_month' => $wd['total_days'],
                'days_attendance_taken' => $daysAttendanceTaken,
                'total_staff' => $totalStaff,
                'overall' => [
                    'present' => $totalPresent,
                    'late' => $totalLate,
                    'percentage' => $overallPercentage,
                ],
                'staff' => $ranked,
            ],
        ]);
    }
}

