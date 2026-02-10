<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\ExtraClass;
use App\Models\ExtraClassEnrollment;
use App\Models\ExtraClassAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExtraClassAttendanceController extends Controller
{
    public function index(School $school)
    {
        $userId = Auth::id();
        $extraClasses = ExtraClass::where('school_id', $school->id)
            ->where('status', 'active')
            ->where('teacher_id', $userId)
            ->with(['schoolClass','section','subject'])
            ->orderBy('name')
            ->get();

        return view('teacher.attendance.extra-classes.index', compact('school','extraClasses'));
    }

    public function take(School $school, Request $request)
    {
        $userId = Auth::id();
        $extraClassId = $request->query('extra_class_id');
        $dateParam = $request->query('date', Carbon::today()->toDateString());
        $dateObj = Carbon::parse($dateParam);
        if ($dateObj->isFuture()) {
            $dateObj = Carbon::today();
        }
        $date = $dateObj->toDateString();

        if (!$extraClassId) {
            return redirect()->route('teacher.institute.attendance.extra-classes.index', $school)
                ->with('error', 'এক্সট্রা ক্লাস নির্বাচন করুন');
        }

        $extraClass = ExtraClass::with(['schoolClass','section','subject','teacher'])
            ->where('school_id', $school->id)
            ->where('teacher_id', $userId)
            ->findOrFail($extraClassId);

        // Load enrolled students mapped for table
        $students = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)
            ->where('status','active')
            ->whereHas('student', fn($q) => $q->where('status','active'))
            ->with(['student' => fn($q)=>$q->where('status','active')->with('currentEnrollment'), 'assignedSection'])
            ->get()
            ->map(function ($enrollment) {
                return (object) [
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->student_name_bn ?? $enrollment->student->student_name_en,
                    'roll_no' => optional($enrollment->student->currentEnrollment)->roll_no ?? 'N/A',
                    'section_name' => optional($enrollment->assignedSection)->name ?? 'N/A',
                ];
            })
            ->sortBy('roll_no')
            ->values();

        $studentIds = $students->pluck('id')->all();
        $attendanceRecords = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $date)
            ->when(!empty($studentIds), fn($q)=>$q->whereIn('student_id', $studentIds))
            ->get()
            ->keyBy('student_id');

        $isToday = Carbon::parse($date)->isSameDay(Carbon::today());
        $flatRecords = $attendanceRecords->values();
        $stats = [
            'total' => $flatRecords->count(),
            'present' => $flatRecords->where('status','present')->count(),
            'absent' => $flatRecords->where('status','absent')->count(),
            'late' => $flatRecords->where('status','late')->count(),
            'excused' => $flatRecords->where('status','excused')->count(),
        ];

        return view('teacher.attendance.extra-classes.take', compact('school','extraClass','students','date','attendanceRecords','isToday','stats'));
    }

    public function store(Request $request, School $school)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'extra_class_id' => 'required|exists:extra_classes,id',
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late,excused',
            'attendance.*.remarks' => 'nullable|string|max:500',
        ]);

        $extraClass = ExtraClass::where('school_id', $school->id)
            ->where('teacher_id', $userId)
            ->findOrFail($validated['extra_class_id']);

        // Enforce: teacher can only submit for TODAY
        $isToday = Carbon::parse($validated['date'])->isSameDay(Carbon::today());
        if (!$isToday) {
            return back()->with('error', 'You can only record attendance for today.');
        }

        // Validate submitted student IDs belong to active enrollments
        $enrolledIds = ExtraClassEnrollment::where('extra_class_id', $validated['extra_class_id'])
            ->where('status','active')
            ->whereHas('student', fn($q)=>$q->where('status','active'))
            ->pluck('student_id')
            ->map(fn($v)=>(int)$v)
            ->toArray();
        $submittedIds = collect($validated['attendance'])->pluck('student_id')->map(fn($v)=>(int)$v)->unique()->toArray();
        $missing = array_diff($submittedIds, $enrolledIds);
        if (!empty($missing)) {
            return back()->with('error', 'কিছু শিক্ষার্থী সক্রিয়ভাবে এনরোল করা নেই বা নিষ্ক্রীয় অবস্থায় আছে।')->withInput();
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
            return redirect()->route('teacher.institute.attendance.extra-classes.take', [
                'school' => $school,
                'extra_class_id' => $extraClass->id,
                'date' => $validated['date'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back();
        }
    }
}
