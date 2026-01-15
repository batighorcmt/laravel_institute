<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\ExtraClass;
use App\Models\ExtraClassAttendance;
use App\Models\ExtraClassEnrollment;
use App\Models\Setting;
use App\Models\SmsTemplate;
use App\Models\Student;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        if (!$extraClassId) {
            return redirect()->route('principal.institute.extra-classes.attendance.index', $school)
                ->with('error', 'Please select an extra class');
        }

        $extraClass = ExtraClass::with(['schoolClass', 'section', 'subject', 'teacher'])
            ->findOrFail($extraClassId);

        if ($extraClass->school_id !== $school->id) abort(404);

        // Get enrolled students
        $students = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)
            ->where('status', 'active')
            ->with(['student', 'assignedSection'])
            ->get()
            ->map(function ($enrollment) {
                return (object)[
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->student_name_bn ?? $enrollment->student->student_name_en,
                    'roll_no' => $enrollment->student->currentEnrollment->roll_no ?? 'N/A',
                    'section_name' => $enrollment->assignedSection->name ?? 'N/A',
                ];
            })
            ->sortBy('roll_no');

        // Get existing attendance for this date
        $attendanceRecords = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $date)
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
        if ($extraClass->school_id !== $school->id) abort(404);

        DB::beginTransaction();
        try {
            // Delete existing attendance for this extra class and date
            ExtraClassAttendance::where('extra_class_id', $extraClass->id)
                ->where('date', $validated['date'])
                ->delete();

            // Insert new attendance records
            foreach ($validated['attendance'] as $att) {
                ExtraClassAttendance::create([
                    'extra_class_id' => $extraClass->id,
                    'student_id' => $att['student_id'],
                    'date' => $validated['date'],
                    'status' => $att['status'],
                    'remarks' => $att['remarks'] ?? null,
                ]);
            }

            // Send SMS notifications
            $smsCount = $this->sendExtraAttendanceSms($school, $validated['attendance'], $extraClass, $validated['date']);

            DB::commit();
            return redirect()->route('principal.institute.extra-classes.attendance.take', [
                'school' => $school,
                'extra_class_id' => $extraClass->id,
                'date' => $validated['date']
            ])->with('success', 'Attendance recorded successfully! ' . $smsCount . ' SMS sent.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save attendance: ' . $e->getMessage());
        }
    }

    public function dailyReport(School $school, Request $request)
    {
        $extraClassId = $request->query('extra_class_id');
        $date = $request->query('date', now()->toDateString());
        $print = $request->query('print', 0);

        if (!$extraClassId) {
            return redirect()->route('principal.institute.extra-classes.attendance.index', $school)
                ->with('error', 'Please select an extra class');
        }

        $extraClass = ExtraClass::with(['schoolClass', 'section', 'subject', 'teacher', 'academicYear'])
            ->findOrFail($extraClassId);

        if ($extraClass->school_id !== $school->id) abort(404);

        // Get attendance records
        $attendances = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $date)
            ->with(['student' => function ($q) {
                $q->with(['currentEnrollment.section']);
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

        if (!$extraClassId) {
            return redirect()->route('principal.institute.extra-classes.attendance.index', $school)
                ->with('error', 'Please select an extra class');
        }

        $extraClass = ExtraClass::with(['schoolClass', 'section', 'subject', 'teacher', 'academicYear'])
            ->findOrFail($extraClassId);

        if ($extraClass->school_id !== $school->id) abort(404);

        [$yearNum, $monthNum] = explode('-', $month);
        $startDate = sprintf('%04d-%02d-01', $yearNum, $monthNum);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNum, $yearNum);
        $endDate = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $daysInMonth);

        // Get enrolled students
        $students = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)
            ->with(['student.currentEnrollment.section', 'assignedSection'])
            ->get()
            ->map(function ($enrollment) {
                return (object)[
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

    private function sendExtraAttendanceSms(School $school, array $attendanceData, $extraClass, $date)
    {
        $sentCount = 0;
        foreach ($attendanceData as $att) {
            $newStatus = $att['status'];
            // For extra class, always send
            $send = true;

            if ($send) {
                $template = SmsTemplate::where(function($q) use ($school) {
                    $q->where('school_id', $school->id)->orWhereNull('school_id');
                })->where('type', 'extra_class')->where('title', 'extra_class_' . $newStatus)->first();
                if ($template) {
                    $student = Student::find($att['student_id']);
                    if ($student && $student->guardian_phone) {
                        $enrollment = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)->where('student_id', $att['student_id'])->first();
                        $assignedSection = $enrollment ? $enrollment->assignedSection : null;
                        $roll_no = $enrollment && $enrollment->student->currentEnrollment ? $enrollment->student->currentEnrollment->roll_no : null;
                        $class_name = $extraClass->schoolClass ? $extraClass->schoolClass->name : null;
                        $section_name = $assignedSection ? $assignedSection->name : null;
                        $message = $this->replacePlaceholders($template->content, $student, $newStatus, $date);
                        $extra = [
                            'recipient_id' => $student->id,
                            'recipient_name' => $student->student_name_en,
                            'recipient_type' => 'student',
                            'recipient_category' => 'guardian',
                            'roll_number' => $roll_no,
                            'class_name' => $class_name,
                            'section_name' => $section_name,
                        ];
                        $smsService = new SmsService($school);
                        $result = $smsService->sendSms($student->guardian_phone, $message, 'extra_attendance', $extra);
                        if ($result) $sentCount++;
                    }
                }
            }
        }
        return $sentCount;
    }

    private function replacePlaceholders($content, $student, $status, $date)
    {
        return str_replace(
            ['{student_name}', '{status}', '{date}'],
            [$student->student_name_en, $status, $date],
            $content
        );
    }
}
