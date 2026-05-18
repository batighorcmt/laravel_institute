<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ExtraClass;
use App\Models\ExtraClassAttendance;
use App\Models\ExtraClassEnrollment;
use App\Models\School;
use App\Services\AttendanceSmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExtraClassAttendanceController extends Controller
{
    public function index(School $school)
    {
        $extraClasses = ExtraClass::where('school_id', $school->id)
            ->where('status', 'active')
            ->with(['schoolClass', 'section', 'subject'])
            ->orderBy('name')
            ->get();

        return view('principal.institute.extra-classes.attendance.index', compact('school', 'extraClasses'));
    }

    public function take(School $school, Request $request)
    {
        $extraClassId = $request->query('extra_class_id');
        $date = $request->query('date', now()->toDateString());

        if (! $extraClassId) {
            return redirect()->route('principal.institute.extra-classes.attendance.index', $school)
                ->with('error', 'Please select an extra class');
        }

        $extraClass = ExtraClass::with(['schoolClass', 'section', 'subject', 'teacher'])
            ->findOrFail($extraClassId);

        if ($extraClass->school_id !== $school->id) {
            abort(404);
        }

        // Get enrolled students (only active student records)
        $students = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)
            ->where('status', 'active')
            ->whereHas('student', fn ($q) => $q->where('status', 'active'))
            ->with(['student' => fn ($q) => $q->where('status', 'active')->with('currentEnrollment.section'), 'assignedSection'])
            ->get()
            ->map(function ($enrollment) {
                return (object) [
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->student_name_bn ?? $enrollment->student->student_name_en,
                    'roll_no' => $enrollment->student->currentEnrollment->roll_no ?? 'N/A',
                    'section_name' => $enrollment->assignedSection->name ?? 'N/A',
                ];
            })
            ->sortBy('roll_no');

        // Get existing attendance for this date (only for active enrolled students)
        $studentIds = $students->pluck('id')->all();
        $attendanceRecords = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $date)
            ->when(! empty($studentIds), fn ($q) => $q->whereIn('student_id', $studentIds))
            ->get()
            ->keyBy('student_id');

        return view('principal.institute.extra-classes.attendance.take', compact(
            'school',
            'extraClass',
            'students',
            'date',
            'attendanceRecords'
        ));
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'extra_class_id' => 'required|exists:extra_classes,id',
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late,excused',
            'attendance.*.remarks' => 'nullable|string|max:500',
        ]);

        $extraClass = ExtraClass::findOrFail($validated['extra_class_id']);
        if ($extraClass->school_id !== $school->id) {
            abort(404);
        }

        // Ensure submitted student IDs belong to active enrollments for this extra class
        $enrolledIds = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)
            ->where('status', 'active')
            ->whereHas('student', fn ($q) => $q->where('status', 'active'))
            ->pluck('student_id')
            ->all();

        foreach ($validated['attendance'] as $att) {
            if (! in_array($att['student_id'], $enrolledIds)) {
                return back()->with('error', 'Invalid student selected for attendance.');
            }
        }

        $previousStatuses = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $validated['date'])
            ->pluck('status', 'student_id')
            ->toArray();
        $isExistingRecord = $previousStatuses !== [];

        $attendancePayload = [];
        foreach ($validated['attendance'] as $att) {
            $attendancePayload[$att['student_id']] = ['status' => $att['status']];
        }

        DB::beginTransaction();
        try {
            ExtraClassAttendance::where('extra_class_id', $extraClass->id)
                ->where('date', $validated['date'])
                ->delete();

            foreach ($validated['attendance'] as $att) {
                ExtraClassAttendance::create([
                    'extra_class_id' => $extraClass->id,
                    'student_id' => $att['student_id'],
                    'date' => $validated['date'],
                    'status' => $att['status'],
                    'remarks' => $att['remarks'] ?? null,
                ]);
            }

            DB::commit();

            $smsReport = (new AttendanceSmsService)->enqueueAttendanceSms(
                $school,
                $attendancePayload,
                $extraClass->class_id,
                $extraClass->section_id,
                $validated['date'],
                $isExistingRecord,
                $previousStatuses,
                Auth::id(),
                'extra_class'
            );

            $pushService = new \App\Services\PushNotificationService;
            foreach ($validated['attendance'] as $att) {
                $oldStatus = $previousStatuses[$att['student_id']] ?? null;
                if ($oldStatus === null || $oldStatus !== $att['status']) {
                    $pushService->sendAttendanceNotification($att['student_id'], $att['status'], $validated['date'], 'extra_class');
                }
            }

            return redirect()->route('principal.institute.extra-classes.attendance.take', [
                'school' => $school,
                'extra_class_id' => $extraClass->id,
                'date' => $validated['date'],
            ])->with('success', 'Attendance recorded successfully! '.$smsReport['sent'].' SMS and Push notifications sent.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to save attendance: '.$e->getMessage());
        }
    }

    public function dailyReport(School $school, Request $request)
    {
        $extraClassId = $request->query('extra_class_id');
        $date = $request->query('date', now()->toDateString());
        $print = $request->query('print', 0);

        if (! $extraClassId) {
            return redirect()->route('principal.institute.extra-classes.attendance.index', $school)
                ->with('error', 'Please select an extra class');
        }

        $extraClass = ExtraClass::with(['schoolClass', 'section', 'subject', 'teacher', 'academicYear'])
            ->findOrFail($extraClassId);

        if ($extraClass->school_id !== $school->id) {
            abort(404);
        }

        // Get attendance records only for active students
        $attendances = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $date)
            ->whereHas('student', fn ($q) => $q->where('status', 'active'))
            ->with(['student' => function ($q) {
                $q->where('status', 'active')->with(['currentEnrollment.section']);
            }])
            ->get();

        // Statistics
        $stats = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
        ];
        $stats['percentage'] = $stats['total'] > 0
            ? round((($stats['present'] + $stats['late']) / $stats['total']) * 100, 1)
            : 0;

        return view('principal.institute.extra-classes.attendance.daily-report', compact(
            'school',
            'extraClass',
            'date',
            'attendances',
            'stats',
            'print'
        ));
    }

    public function monthlyReport(School $school, Request $request)
    {
        $extraClassId = $request->query('extra_class_id');
        $month = $request->query('month', now()->format('Y-m'));
        $print = $request->query('print', 0);

        if (! $extraClassId) {
            return redirect()->route('principal.institute.extra-classes.attendance.index', $school)
                ->with('error', 'Please select an extra class');
        }

        $extraClass = ExtraClass::with(['schoolClass', 'section', 'subject', 'teacher', 'academicYear'])
            ->findOrFail($extraClassId);

        if ($extraClass->school_id !== $school->id) {
            abort(404);
        }

        [$yearNum, $monthNum] = explode('-', $month);
        $startDate = sprintf('%04d-%02d-01', $yearNum, $monthNum);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNum, $yearNum);
        $endDate = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $daysInMonth);

        // Get enrolled students (only active student records)
        $students = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)
            ->where('status', 'active')
            ->whereHas('student', fn ($q) => $q->where('status', 'active'))
            ->with(['student' => fn ($q) => $q->where('status', 'active')->with('currentEnrollment.section'), 'assignedSection'])
            ->get()
            ->map(function ($enrollment) {
                return (object) [
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->student_name_bn ?? $enrollment->student->student_name_en,
                    'roll_no' => $enrollment->student->currentEnrollment->roll_no ?? 'N/A',
                    'section_name' => $enrollment->assignedSection->name ?? 'N/A',
                ];
            })
            ->sortBy('roll_no')
            ->values();

        // Get attendance matrix
        $attendanceMatrix = [];
        if ($students->count() > 0) {
            $studentIds = $students->pluck('id')->all();
            $attRecords = ExtraClassAttendance::select('student_id', 'date', 'status')
                ->where('extra_class_id', $extraClass->id)
                ->whereIn('student_id', $studentIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            foreach ($attRecords as $record) {
                $dateKey = is_string($record->date)
                    ? $record->date
                    : Carbon::parse($record->date)->toDateString();
                $attendanceMatrix[$record->student_id][$dateKey] = $record->status;
            }
        }

        // Generate all dates for the month
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dates[] = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $d);
        }

        return view('principal.institute.extra-classes.attendance.monthly-report', compact(
            'school',
            'extraClass',
            'month',
            'students',
            'dates',
            'attendanceMatrix',
            'print'
        ));
    }

    public function dashboard(School $school, Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        // Current academic year (use ID foreign key)
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearVal = $currentYear?->id;

        // Total active students in extra classes
        $totalStudents = ExtraClassEnrollment::join('extra_classes', 'extra_class_enrollments.extra_class_id', '=', 'extra_classes.id')
            ->where('extra_classes.school_id', $school->id)
            ->where('extra_class_enrollments.status', 'active')
            ->where('extra_classes.status', 'active')
            ->whereHas('student', fn ($q) => $q->where('status', 'active'))
            ->distinct('extra_class_enrollments.student_id')
            ->count('extra_class_enrollments.student_id');

        // Today's attendance counts
        $presentToday = ExtraClassAttendance::join('students', 'students.id', '=', 'extra_class_attendances.student_id')
            ->where('extra_class_attendances.date', $date)
            ->where('students.school_id', $school->id)
            ->where('students.status', 'active')
            ->whereIn('extra_class_attendances.status', ['present', 'late'])
            ->count();

        $absentToday = ExtraClassAttendance::join('students', 'students.id', '=', 'extra_class_attendances.student_id')
            ->where('extra_class_attendances.date', $date)
            ->where('students.school_id', $school->id)
            ->where('students.status', 'active')
            ->where('extra_class_attendances.status', 'absent')
            ->count();

        $anyAttendanceToday = ExtraClassAttendance::join('students', 'students.id', '=', 'extra_class_attendances.student_id')
            ->where('extra_class_attendances.date', $date)
            ->where('students.school_id', $school->id)
            ->where('students.status', 'active')
            ->exists();

        $attendancePercent = ($totalStudents > 0 && $anyAttendanceToday)
            ? round(($presentToday / $totalStudents) * 100, 1)
            : null;

        // Extra Class breakdown
        $extraClassTotals = ExtraClassEnrollment::select(
            'extra_classes.id as extra_class_id',
            'extra_classes.name as extra_class_name',
            DB::raw('COUNT(DISTINCT extra_class_enrollments.student_id) as total'),
            DB::raw("SUM(CASE WHEN students.gender='male' THEN 1 ELSE 0 END) as total_male"),
            DB::raw("SUM(CASE WHEN students.gender='female' THEN 1 ELSE 0 END) as total_female")
        )
            ->join('extra_classes', 'extra_class_enrollments.extra_class_id', '=', 'extra_classes.id')
            ->join('students', 'students.id', '=', 'extra_class_enrollments.student_id')
            ->where('extra_classes.school_id', $school->id)
            ->where('extra_class_enrollments.status', 'active')
            ->where('extra_classes.status', 'active')
            ->where('students.status', 'active')
            ->groupBy('extra_classes.id', 'extra_classes.name')
            ->get();

        $attendanceGender = ExtraClassAttendance::select(
            'extra_class_id',
            DB::raw("SUM(CASE WHEN students.gender='male' AND extra_class_attendances.status IN ('present','late') THEN 1 ELSE 0 END) as present_male"),
            DB::raw("SUM(CASE WHEN students.gender='female' AND extra_class_attendances.status IN ('present','late') THEN 1 ELSE 0 END) as present_female"),
            DB::raw("SUM(CASE WHEN students.gender='male' AND extra_class_attendances.status='absent' THEN 1 ELSE 0 END) as absent_male"),
            DB::raw("SUM(CASE WHEN students.gender='female' AND extra_class_attendances.status='absent' THEN 1 ELSE 0 END) as absent_female"),
            DB::raw("COUNT(DISTINCT CASE WHEN extra_class_attendances.status IN ('present','late') THEN extra_class_attendances.student_id END) as present_total"),
            DB::raw("COUNT(DISTINCT CASE WHEN extra_class_attendances.status='absent' THEN extra_class_attendances.student_id END) as absent_total")
        )
            ->join('students', 'students.id', '=', 'extra_class_attendances.student_id')
            ->where('extra_class_attendances.date', $date)
            ->where('students.status', 'active')
            ->groupBy('extra_class_id')
            ->get()
            ->keyBy('extra_class_id');

        $attendanceExists = ExtraClassAttendance::select('extra_class_id')
            ->where('date', $date)
            ->distinct()->pluck('extra_class_id')->all();

        $extraClassWise = $extraClassTotals->map(function ($row) use ($attendanceGender, $attendanceExists) {
            $genderAtt = $attendanceGender->get($row->extra_class_id);
            $present_male = $genderAtt ? (int) $genderAtt->present_male : 0;
            $present_female = $genderAtt ? (int) $genderAtt->present_female : 0;
            $absent_male = $genderAtt ? (int) $genderAtt->absent_male : 0;
            $absent_female = $genderAtt ? (int) $genderAtt->absent_female : 0;
            $present_total = $genderAtt ? (int) $genderAtt->present_total : 0;
            $absent_total = $genderAtt ? (int) $genderAtt->absent_total : 0;
            $att_taken = in_array($row->extra_class_id, $attendanceExists);

            return (object) [
                'extra_class_id' => $row->extra_class_id,
                'extra_class_name' => $row->extra_class_name,
                'total' => (int) $row->total,
                'total_male' => (int) $row->total_male,
                'total_female' => (int) $row->total_female,
                'present_male' => $present_male,
                'present_female' => $present_female,
                'absent_male' => $absent_male,
                'absent_female' => $absent_female,
                'present_total' => $present_total,
                'absent_total' => $absent_total,
                'att_taken' => $att_taken,
                'percentage' => ($row->total > 0 && $att_taken) ? round(($present_total / $row->total) * 100, 1) : null,
            ];
        });

        $grandTotal = $extraClassWise->sum('total');
        $grandPresent = $extraClassWise->sum('present_total');
        $grandPercent = ($grandTotal > 0 && $anyAttendanceToday) ? round(($grandPresent / $grandTotal) * 100, 1) : null;

        $genderCounts = ExtraClassAttendance::select('students.gender', DB::raw('COUNT(DISTINCT extra_class_attendances.student_id) as cnt'))
            ->join('students', 'students.id', '=', 'extra_class_attendances.student_id')
            ->where('extra_class_attendances.date', $date)
            ->where('students.school_id', $school->id)
            ->where('students.status', 'active')
            ->whereIn('extra_class_attendances.status', ['present', 'late'])
            ->groupBy('students.gender')
            ->pluck('cnt', 'gender');

        $absentees = ExtraClassAttendance::select(
            'extra_class_attendances.student_id',
            'students.student_name_bn',
            'students.student_name_en',
            'students.gender',
            'extra_classes.name as class_name'
        )
            ->join('students', 'students.id', '=', 'extra_class_attendances.student_id')
            ->join('extra_classes', 'extra_classes.id', '=', 'extra_class_attendances.extra_class_id')
            ->where('students.school_id', $school->id)
            ->where('students.status', 'active')
            ->where('extra_class_attendances.date', $date)
            ->where('extra_class_attendances.status', 'absent')
            ->orderBy('extra_classes.name')
            ->get();

        $absentees = $absentees->map(function ($s) use ($date) {
            $lastPresent = ExtraClassAttendance::where('student_id', $s->student_id)
                ->whereIn('status', ['present', 'late'])
                ->where('date', '<', $date)
                ->orderByDesc('date')
                ->value('date');

            if ($lastPresent) {
                $streak = (new \DateTime($date))->diff(new \DateTime($lastPresent))->days;
                $s->streak_days = max(1, $streak);
            } else {
                $s->streak_days = 1;
            }

            $s->latest_remarks = ExtraClassAttendance::where('student_id', $s->student_id)
                ->whereNotNull('remarks')
                ->where('remarks', '!=', '')
                ->orderByDesc('date')
                ->value('remarks');

            return $s;
        });

        $barLabels = $extraClassWise->pluck('extra_class_name');
        $barData = $extraClassWise->pluck('percentage');
        $genderLabels = $genderCounts->keys();
        $genderData = $genderCounts->values();

        return view('principal.institute.extra-classes.attendance.dashboard', compact(
            'school', 'date', 'totalStudents', 'presentToday', 'absentToday', 'attendancePercent',
            'extraClassWise', 'barLabels', 'barData', 'genderLabels', 'genderData', 'absentees', 'grandTotal', 'grandPresent', 'grandPercent'
        ));
    }
}
