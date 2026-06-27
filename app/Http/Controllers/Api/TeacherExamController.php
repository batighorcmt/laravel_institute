<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamRoomAttendance;
use App\Models\ExamRoomInvigilation;
use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\SeatPlan;
use App\Models\SeatPlanAllocation;
use App\Models\SeatPlanRoom;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class TeacherExamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) {
            return response()->json(['message' => 'School context unavailable'], 422);
        }

        $isExamController = $user->isExamController($schoolId) || $user->isPrincipal($schoolId) || $user->isSuperAdmin();

        return response()->json([
            'is_exam_controller' => $isExamController,
        ]);
    }

    /**
     * Today's exam duty for this teacher.
     * For Exam Controllers, allows filtering by plan_id and date.
     */
    public function todaysDuty(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        $isController = $user->isExamController($schoolId) || $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        $planId = $request->get('plan_id');
        $date = $request->get('date', now()->toDateString());

        if ($isController && $planId) {
            // If plan is selected, we show all rooms from SeatPlanRoom
            $rooms = \App\Models\SeatPlanRoom::with(['invigilations' => function ($q) use ($date) {
                $q->where('duty_date', $date);
            }, 'invigilations.teacher', 'seatPlan', 'seatPlan.classes'])
                ->where('seat_plan_id', $planId)
                ->orderBy('room_no')
                ->get();

            return response()->json($rooms->map(fn ($r) => [
                'id' => $r->invigilations->first()?->id ?? 0,
                'is_assigned' => $r->invigilations->isNotEmpty(),
                'duty_date' => $date,
                'seat_plan_id' => $r->seat_plan_id,
                'seat_plan_room_id' => $r->id,
                'room_no' => $r->room_no,
                'room_title' => $r->title,
                'building' => $r->building,
                'floor' => $r->floor,
                'seat_plan' => $r->seatPlan?->name,
                'shift' => $r->seatPlan?->shift,
                'classes' => $r->seatPlan?->classes->pluck('name')->toArray() ?? [],
                'teacher_name' => $r->invigilations->first()?->teacher?->name,
                'teacher_user_id' => $r->invigilations->first()?->teacher_id,
            ]));
        }

        // Default: teacher duties (today + future)
        $today = now()->toDateString();
        $query = ExamRoomInvigilation::with(['room', 'seatPlan', 'seatPlan.classes'])
            ->where('school_id', $schoolId)
            ->where('teacher_id', $user->id)
            ->where('duty_date', '>=', $today)
            ->orderBy('duty_date');

        $duties = $query->get();

        return response()->json($duties->map(fn ($d) => [
            'id' => $d->id,
            'duty_date' => $d->duty_date->toDateString(),
            'seat_plan_id' => $d->seat_plan_id,
            'seat_plan_room_id' => $d->seat_plan_room_id,
            'room_no' => $d->room?->room_no,
            'room_title' => $d->room?->title,
            'building' => $d->room?->building,
            'floor' => $d->room?->floor,
            'seat_plan' => $d->seatPlan?->name,
            'shift' => $d->seatPlan?->shift,
            'classes' => $d->seatPlan?->classes->pluck('name')->toArray() ?? [],
            'is_today' => $d->duty_date->toDateString() === $today,
        ]));
    }

    public function dutyMeta(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        $planId = $request->get('plan_id');

        $plans = SeatPlan::where('school_id', $schoolId)->active()->orderBy('id', 'desc')->get(['id', 'name', 'shift'])->map(fn($p) => ['id' => $p->id, 'name' => $p->name . ($p->shift ? ' (' . ucfirst($p->shift) . ')' : '')]);

        $dates = [];
        if ($planId) {
            $dates = ExamSubject::whereIn('exam_id', function ($q) use ($planId) {
                $q->select('exam_id')->from('seat_plan_exams')->where('seat_plan_id', $planId);
            })
                ->whereNotNull('exam_date')
                ->distinct()
                ->orderBy('exam_date')
                ->pluck('exam_date')
                ->map(fn ($d) => $d->format('Y-m-d'))
                ->toArray();
        }

        return response()->json([
            'plans' => $plans,
            'dates' => $dates,
        ]);
    }

    /**
     * Find student seat.
     */
    public function findSeat(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        $find = trim($request->get('find', ''));
        $planId = $request->get('plan_id');

        $plans = SeatPlan::where('school_id', $schoolId)->active()->get(['id', 'name', 'shift'])->map(fn($p) => ['id' => $p->id, 'name' => $p->name . ($p->shift ? ' (' . ucfirst($p->shift) . ')' : '')]);

        $results = [];
        if ($planId && $find !== '') {
            $query = SeatPlanAllocation::where('seat_plan_id', $planId)
                ->with(['student.currentEnrollment.class', 'student.currentEnrollment.section', 'room']);

            $query->where(function ($q2) use ($find) {
                if (is_numeric($find)) {
                    $q2->whereHas('student.currentEnrollment', function ($q) use ($find) {
                        $q->where('roll_no', $find);
                    })->orWhereHas('student', function ($q) use ($find) {
                        $q->where('student_name_en', 'like', '%'.$find.'%')
                            ->orWhere('student_name_bn', 'like', '%'.$find.'%');
                    });
                } else {
                    $q2->whereHas('student', function ($q) use ($find) {
                        $q->where('student_name_en', 'like', '%'.$find.'%')
                            ->orWhere('student_name_bn', 'like', '%'.$find.'%');
                    });
                }
            });

            $results = $query->orderBy('id', 'asc')->limit(20)->get()->map(fn ($r) => [
                'student_name' => $r->student?->full_name,
                'student_id' => $r->student?->student_id,
                'roll' => $r->student?->currentEnrollment?->roll_no ?? $r->student?->roll,
                'photo_url' => $r->student?->photo_url,
                'class_name' => $r->student?->currentEnrollment?->class?->name,
                'section_name' => $r->student?->currentEnrollment?->section?->name,
                'room_no' => $r->room?->room_no,
                'seat' => $r->seat_number,
                'col_no' => $r->col_no,
                'bench_no' => $r->bench_no,
                'position' => $r->position,
            ]);
        }

        return response()->json([
            'plans' => $plans,
            'results' => $results,
        ]);
    }

    /**
     * Students list for room attendance.
     */
    public function roomAttendanceStudents(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        $planId = $request->get('plan_id');
        $roomId = $request->get('room_id');
        $date = $request->get('date', now()->toDateString());

        if (! $planId || ! $roomId) {
            return response()->json(['message' => 'Plan and Room IDs are required'], 422);
        }

        // Access check
        $isAuthorized = $user->isExamController($schoolId) || $user->isPrincipal($schoolId) || $user->isSuperAdmin() ||
            ExamRoomInvigilation::where('seat_plan_id', $planId)
                ->where('seat_plan_room_id', $roomId)
                ->where('teacher_id', $user->id)
                ->where('duty_date', $date)
                ->exists();

        if (! $isAuthorized) {
            return response()->json(['message' => 'Unauthorized access to this room'], 403);
        }

        $allocations = SeatPlanAllocation::with(['student', 'student.currentEnrollment.class'])
            ->where('seat_plan_id', $planId)
            ->where('room_id', $roomId)
            ->get();

        $attendances = ExamRoomAttendance::where('plan_id', $planId)
            ->where('room_id', $roomId)
            ->where('duty_date', $date)
            ->get()
            ->keyBy('student_id');

        $students = $allocations->map(function ($alloc) use ($attendances) {
            $student = $alloc->student;
            if (! $student) {
                return null;
            }

            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'roll' => $student->roll_no ?? $student->currentEnrollment?->roll_no,
                'class_name' => $student->currentEnrollment?->class?->name ?? 'N/A',
                'class_numeric' => $student->currentEnrollment?->class?->numeric_value ?? 999,
                'photo_url' => $student->photo_url,
                'gender' => $student->gender,
                'status' => $attendances->has($student->id) ? $attendances->get($student->id)->status : null,
                'col_no' => $alloc->col_no ?? 999,
                'bench_no' => $alloc->bench_no ?? 999,
                'position' => $alloc->position,
            ];
        })->filter()->sortBy(function($item) {
            return sprintf('%03d-%03d-%03d', $item['class_numeric'], $item['col_no'], $item['bench_no']);
        })->values();

        $genderStats = [
            'male' => $students->where('gender', 'male')->count(),
            'female' => $students->where('gender', 'female')->count(),
            'other' => $students->whereNotIn('gender', ['male', 'female'])->count(),
        ];

        $classStats = $students->groupBy('class_name')->map(fn ($group) => $group->count());

        return response()->json([
            'date' => $date,
            'students' => $students,
            'stats' => [
                'total' => $students->count(),
                'present' => $students->where('status', 'present')->count(),
                'absent' => $students->where('status', 'absent')->count(),
                'gender' => $genderStats,
                'classes' => $classStats,
            ],
        ]);
    }

    /**
     * Submit room attendance for a single student.
     */
    public function submitRoomAttendance(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        $request->validate([
            'plan_id' => 'required|integer',
            'room_id' => 'required|integer',
            'date' => 'required|date',
            'student_id' => 'required|integer',
            'status' => 'required|string|in:present,absent',
        ]);

        $planId = $request->plan_id;
        $roomId = $request->room_id;
        $date = $request->date;

        // Access check
        $isAuthorized = $user->isExamController($schoolId) || $user->isPrincipal($schoolId) || $user->isSuperAdmin() ||
            ExamRoomInvigilation::where('seat_plan_id', $planId)
                ->where('seat_plan_room_id', $roomId)
                ->where('teacher_id', $user->id)
                ->where('duty_date', $date)
                ->exists();

        if (! $isAuthorized) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        ExamRoomAttendance::updateOrCreate(
            [
                'school_id' => $schoolId,
                'duty_date' => $date,
                'plan_id' => $planId,
                'room_id' => $roomId,
                'student_id' => $request->student_id,
            ],
            ['status' => $request->status]
        );

        return response()->json(['message' => 'Attendance saved successfully']);
    }

    /**
     * Bulk submit room attendance.
     */
    public function bulkSubmitRoomAttendance(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        $request->validate([
            'plan_id' => 'required|integer',
            'room_id' => 'required|integer',
            'date' => 'required|date',
            'status' => 'required|string|in:present,absent',
        ]);

        $planId = $request->plan_id;
        $roomId = $request->room_id;
        $date = $request->date;

        // Access check
        $isAuthorized = $user->isExamController($schoolId) || $user->isPrincipal($schoolId) || $user->isSuperAdmin() ||
            ExamRoomInvigilation::where('seat_plan_id', $planId)
                ->where('seat_plan_room_id', $roomId)
                ->where('teacher_id', $user->id)
                ->where('duty_date', $date)
                ->exists();

        if (! $isAuthorized) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $studentIds = SeatPlanAllocation::where('seat_plan_id', $planId)
            ->where('room_id', $roomId)
            ->pluck('student_id');

        foreach ($studentIds as $sid) {
            ExamRoomAttendance::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'duty_date' => $date,
                    'plan_id' => $planId,
                    'room_id' => $roomId,
                    'student_id' => $sid,
                ],
                ['status' => $request->status]
            );
        }

        return response()->json(['message' => 'Bulk attendance saved successfully']);
    }

    /**
     * Mark Entry - Meta data (years, classes, exams, subjects)
     */
    public function markEntryMeta(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        $academicYears = AcademicYear::where('school_id', $schoolId)->orderBy('id', 'desc')->get(['id', 'name']);

        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('numeric_value')->get(['id', 'name']);

        return response()->json([
            'academic_years' => $academicYears,
            'classes' => $classes,
        ]);
    }

    public function getExams(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request, $request->user());
        $request->validate([
            'academic_year_id' => 'required|integer',
            'class_id' => 'required|integer',
            'status' => 'nullable|string|in:active,completed,draft',
        ]);

        $query = Exam::where('school_id', $schoolId)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('class_id', $request->class_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $exams = $query->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'status']);

        return response()->json($exams);
    }

    public function getSubjects(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        $request->validate([
            'exam_id' => 'required|integer',
        ]);

        $subjects = ExamSubject::where('exam_id', $request->exam_id)
            ->where('teacher_id', $user->id)
            ->with('subject')
            ->get()
            ->unique('subject_id')
            ->values()
            ->map(fn ($es) => [
                'id' => $es->subject_id,
                'exam_subject_id' => $es->id,
                'name' => $es->subject?->name,
                'full_marks' => $es->total_full_mark,
                'deadline' => $es->mark_entry_deadline?->toDateTimeString(),
            ]);

        return response()->json($subjects);
    }

    public function getStudents(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request, $request->user());
        $request->validate([
            'exam_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'class_id' => 'required|integer',
        ]);

        $user = $request->user();
        $exam = Exam::findOrFail($request->exam_id);
        $examSubject = ExamSubject::where('exam_id', $request->exam_id)
            ->where('subject_id', $request->subject_id)
            ->when(! ($user->isPrincipal($schoolId) || $user->isSuperAdmin() || $user->isExamController($schoolId)), function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })
            ->firstOrFail();

        $enrollments = StudentEnrollment::where('school_id', $schoolId)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('class_id', $request->class_id)
            ->where('status', 'active')
            ->whereHas('student', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('subjects', function ($query) use ($request) {
                $query->where('subject_id', $request->subject_id);
            })
            ->with(['student', 'section'])
            ->orderBy('roll_no')
            ->get();

        $marks = Mark::where('exam_id', $exam->id)
            ->where('exam_subject_id', $examSubject->id)
            ->get()
            ->keyBy('student_id');

        $decimal = \App\Models\Setting::getDecimalPosition($schoolId);

        $data = $enrollments->map(function ($en) use ($marks, $decimal) {
            $m = $marks->get($en->student_id);

            return [
                'student_id' => $en->student_id,
                'student_name' => $en->student?->full_name,
                'roll' => $en->roll_no,
                'section' => $en->section?->name,
                'mark' => $m ? [
                    'creative' => ! is_null($m->creative_marks) ? number_format($m->creative_marks, $decimal, '.', '') : null,
                    'mcq' => ! is_null($m->mcq_marks) ? number_format($m->mcq_marks, $decimal, '.', '') : null,
                    'practical' => ! is_null($m->practical_marks) ? number_format($m->practical_marks, $decimal, '.', '') : null,
                    'total' => number_format($m->total_marks, $decimal, '.', ''),
                    'letter_grade' => $m->letter_grade,
                    'is_absent' => (bool) $m->is_absent,
                ] : null,
            ];
        });

        $isDeadlinePassed = $examSubject->mark_entry_deadline && now()->greaterThan($examSubject->mark_entry_deadline);
        $readOnly = ($exam->status !== 'active') || ($isDeadlinePassed && ! ($user->isPrincipal($schoolId) || $user->isSuperAdmin()));

        $message = null;
        if ($exam->status === 'completed') {
            $message = 'এই পরীক্ষাটি সম্পন্ন হয়েছে। নম্বর শুধু দেখা যাবে।';
        } elseif ($isDeadlinePassed) {
            $message = 'নম্বর এন্ট্রির সময়সীমা শেষ হয়েছে।';
        }

        return response()->json([
            'exam_subject' => [
                'id' => $examSubject->id,
                'creative_full' => $examSubject->creative_full_mark,
                'mcq_full' => $examSubject->mcq_full_mark,
                'practical_full' => $examSubject->practical_full_mark,
                'pass_type' => $examSubject->pass_type,
                'deadline' => $examSubject->mark_entry_deadline?->toDateTimeString(),
            ],
            'students' => $data,
            'read_only' => $readOnly,
            'message' => $message,
            'decimal_position' => $decimal,
            'print_blank_url' => URL::signedRoute('print.marks.portable', ['exam' => $exam->id, 'examSubject' => $examSubject->id, 'type' => 'print-blank', 'lang' => 'en', 'print' => 1]),
            'print_filled_url' => URL::signedRoute('print.marks.portable', ['exam' => $exam->id, 'examSubject' => $examSubject->id, 'type' => 'print-filled', 'lang' => 'en', 'print' => 1]),
        ]);
    }

    public function saveMark(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'exam_subject_id' => 'required|exists:exam_subjects,id',
            'student_id' => 'required|exists:students,id',
            'creative_marks' => 'nullable|numeric',
            'mcq_marks' => 'nullable|numeric',
            'practical_marks' => 'nullable|numeric',
            'is_absent' => 'nullable|boolean',
            'remarks' => 'nullable|string',
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);
        $examSubject = ExamSubject::findOrFail($validated['exam_subject_id']);

        if ($examSubject->teacher_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($exam->status !== 'active') {
            return response()->json(['message' => 'Exam is not active'], 422);
        }

        $isDeadlinePassed = $examSubject->mark_entry_deadline && now()->greaterThan($examSubject->mark_entry_deadline);
        if ($isDeadlinePassed && ! ($user->isPrincipal($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'নম্বর এন্ট্রির সময়সীমা শেষ হয়েছে।'], 422);
        }

        $isAbsent = $request->boolean('is_absent');
        $totalMarks = 0;
        if (! $isAbsent) {
            $totalMarks = ($validated['creative_marks'] ?? 0) +
                          ($validated['mcq_marks'] ?? 0) +
                          ($validated['practical_marks'] ?? 0);
        }

        $gradeInfo = $this->calculateGrade($totalMarks, $examSubject, $validated, $isAbsent);

        Mark::updateOrCreate(
            [
                'exam_id' => $exam->id,
                'exam_subject_id' => $examSubject->id,
                'student_id' => $validated['student_id'],
                'subject_id' => $examSubject->subject_id,
            ],
            [
                'creative_marks' => $isAbsent ? null : ($validated['creative_marks'] ?? null),
                'mcq_marks' => $isAbsent ? null : ($validated['mcq_marks'] ?? null),
                'practical_marks' => $isAbsent ? null : ($validated['practical_marks'] ?? null),
                'total_marks' => $isAbsent ? null : $totalMarks,
                'letter_grade' => $gradeInfo['letter_grade'],
                'grade_point' => $gradeInfo['grade_point'],
                'pass_status' => $gradeInfo['pass_status'],
                'is_absent' => $isAbsent,
                'remarks' => $validated['remarks'] ?? null,
                'entered_by' => $user->id,
                'entered_at' => now(),
            ]
        );

        $schoolModel = \App\Models\School::findOrFail($schoolId);
        app(\App\Services\ExamResultSyncService::class)->syncAfterMarkSaved($schoolModel, $exam);

        return response()->json([
            'success' => true,
            'total_marks' => number_format($totalMarks, \App\Models\Setting::getDecimalPosition($schoolId), '.', ''),
            'letter_grade' => $gradeInfo['letter_grade'],
        ]);
    }

    private function calculateGrade($totalMarks, $examSubject, $marks, $isAbsent)
    {
        if ($isAbsent) {
            return ['letter_grade' => 'F', 'grade_point' => 0.00, 'pass_status' => 'absent'];
        }

        $isPassed = false;
        if ($examSubject->pass_type === 'each') {
            $creativePass = ($marks['creative_marks'] ?? 0) >= $examSubject->creative_pass_mark;
            $mcqPass = ($marks['mcq_marks'] ?? 0) >= $examSubject->mcq_pass_mark;
            $practicalPass = ($marks['practical_marks'] ?? 0) >= $examSubject->practical_pass_mark;
            $isPassed = $creativePass && $mcqPass && $practicalPass;
        } else {
            $isPassed = $totalMarks >= $examSubject->total_pass_mark;
        }

        if (! $isPassed) {
            return ['letter_grade' => 'F', 'grade_point' => 0.00, 'pass_status' => 'fail'];
        }

        $percentage = ($examSubject->total_full_mark > 0) ? ($totalMarks / $examSubject->total_full_mark) * 100 : 0;
        if ($percentage >= 80) {
            return ['letter_grade' => 'A+', 'grade_point' => 5.00, 'pass_status' => 'pass'];
        }
        if ($percentage >= 70) {
            return ['letter_grade' => 'A', 'grade_point' => 4.00, 'pass_status' => 'pass'];
        }
        if ($percentage >= 60) {
            return ['letter_grade' => 'A-', 'grade_point' => 3.50, 'pass_status' => 'pass'];
        }
        if ($percentage >= 50) {
            return ['letter_grade' => 'B', 'grade_point' => 3.00, 'pass_status' => 'pass'];
        }
        if ($percentage >= 40) {
            return ['letter_grade' => 'C', 'grade_point' => 2.00, 'pass_status' => 'pass'];
        }
        if ($percentage >= 33) {
            return ['letter_grade' => 'D', 'grade_point' => 1.00, 'pass_status' => 'pass'];
        }

        return ['letter_grade' => 'F', 'grade_point' => 0.00, 'pass_status' => 'fail'];
    }

    /* ─── Exam Controller Exclusive Actions ─── */

    public function attendanceReport(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $user->isExamController($schoolId) && ! $user->isPrincipal($schoolId) && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $planId = $request->get('plan_id');
        $date = $request->get('date');

        $plans = SeatPlan::where('school_id', $schoolId)->active()->get(['id', 'name', 'shift'])->map(fn($p) => ['id' => $p->id, 'name' => $p->name . ($p->shift ? ' (' . ucfirst($p->shift) . ')' : '')]);

        $rows = [];
        $absentStudents = [];

        if ($planId && $date) {
            $allRooms = SeatPlanRoom::where('seat_plan_id', $planId)->get();

            $rawAttendances = ExamRoomAttendance::with(['student.currentEnrollment.class', 'room'])
                ->where('duty_date', $date)
                ->where('plan_id', $planId)
                ->get();

            $invigilations = DB::table('exam_room_invigilations as i')
                ->leftJoin('users as u', 'u.id', '=', 'i.teacher_id')
                ->leftJoin('teachers as t', 't.user_id', '=', 'u.id')
                ->where('i.duty_date', $date)
                ->where('i.seat_plan_id', $planId)
                ->select(
                    'i.seat_plan_room_id', 
                    'u.name as invigilator_en',
                    't.first_name_bn',
                    't.last_name_bn'
                )
                ->get()
                ->keyBy('seat_plan_room_id');

            $roomMap = [];
            foreach ($allRooms as $room) {
                $roomId = $room->id;
                $roomNo = $room->room_no;
                $invigilation = $invigilations->get($roomId);
                
                $invigilator_bn = trim(($invigilation->first_name_bn ?? '') . ' ' . ($invigilation->last_name_bn ?? ''));
                $invigilator = $invigilator_bn ?: ($invigilation->invigilator_en ?? 'N/A');

                $roomMap[$roomId] = [
                    'room_no' => $roomNo,
                    'invigilator' => $invigilator,
                    'present_cnt' => 0,
                    'absent_cnt' => 0,
                    'classes' => [],
                    'attendance_taken' => false,
                ];
            }

            foreach ($rawAttendances as $att) {
                $roomId = $att->room_id;
                
                if (!isset($roomMap[$roomId])) {
                    $invigilation = $invigilations->get($roomId);
                    $invigilator_bn = trim(($invigilation->first_name_bn ?? '') . ' ' . ($invigilation->last_name_bn ?? ''));
                    $invigilator = $invigilator_bn ?: ($invigilation->invigilator_en ?? 'N/A');

                    $roomMap[$roomId] = [
                        'room_no' => $att->room->room_no ?? 'N/A',
                        'invigilator' => $invigilator,
                        'present_cnt' => 0,
                        'absent_cnt' => 0,
                        'classes' => [],
                        'attendance_taken' => true,
                    ];
                } else {
                    $roomMap[$roomId]['attendance_taken'] = true;
                }

                $isP = $att->status === 'present';
                $isA = $att->status === 'absent';
                
                if ($isP) $roomMap[$roomId]['present_cnt']++;
                if ($isA) $roomMap[$roomId]['absent_cnt']++;

                $classModel = $att->student->currentEnrollment->class ?? null;
                $className = $classModel ? ($classModel->bangla_name ?: $classModel->name) : 'Unknown';
                
                if (!isset($roomMap[$roomId]['classes'][$className])) {
                    $roomMap[$roomId]['classes'][$className] = [
                        'class_name' => $className,
                        'present_cnt' => 0,
                        'absent_cnt' => 0,
                    ];
                }
                
                if ($isP) $roomMap[$roomId]['classes'][$className]['present_cnt']++;
                if ($isA) $roomMap[$roomId]['classes'][$className]['absent_cnt']++;
            }

            foreach ($roomMap as &$r) {
                $r['classes'] = array_values($r['classes']);
            }

            $rows = array_values($roomMap);

            $absentRecords = ExamRoomAttendance::with(['student.currentEnrollment.class', 'room'])
                ->where('duty_date', $date)
                ->where('plan_id', $planId)
                ->where('status', 'absent')
                ->get();

            $absentStudents = $absentRecords->map(function ($a) {
                $classModel = $a->student?->currentEnrollment?->class;
                return [
                    'id' => $a->student?->id,
                    'name' => $a->student?->full_name,
                    'roll' => $a->student?->currentEnrollment?->roll_no ?? $a->student?->roll_no,
                    'class_name' => $classModel ? ($classModel->bangla_name ?: $classModel->name) : null,
                    'photo_url' => $a->student?->photo_url,
                    'room_no' => $a->room?->room_no,
                ];
            })->values();
        }

        return response()->json([
            'plans' => $plans,
            'rows' => $rows,
            'absent_students' => $absentStudents,
        ]);
    }

    public function teachersList(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        $teachers = \App\Models\Teacher::where('school_id', $schoolId)
            ->where('status', 'active')
            ->get(['user_id', 'first_name', 'last_name', 'initials']);

        return response()->json($teachers->map(fn ($t) => [
            'user_id' => $t->user_id,
            'name' => $t->full_name,
            'initials' => $t->initials,
            'display_name' => $t->full_name.($t->initials ? " ({$t->initials})" : ''),
        ]));
    }

    public function assignDuty(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        if (! $user->isExamController($schoolId) && ! $user->isPrincipal($schoolId) && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'plan_id' => 'required|integer',
            'date' => 'required|date',
            'allocations' => 'required|array',
            'allocations.*.room_id' => 'required|integer',
            'allocations.*.teacher_user_id' => 'nullable|integer',
        ]);

        $notifier = new PushNotificationService();

        foreach ($request->allocations as $allocation) {
            if (empty($allocation['teacher_user_id'])) {
                ExamRoomInvigilation::where('school_id', $schoolId)
                    ->where('duty_date', $request->date)
                    ->where('seat_plan_id', $request->plan_id)
                    ->where('seat_plan_room_id', $allocation['room_id'])
                    ->delete();
            } else {
                $invigilation = ExamRoomInvigilation::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'duty_date' => $request->date,
                        'seat_plan_id' => $request->plan_id,
                        'seat_plan_room_id' => $allocation['room_id'],
                    ],
                    [
                        'teacher_id' => $allocation['teacher_user_id'],
                        'assigned_by' => $user->id,
                    ]
                );

                // Fetch room & plan info for notification
                $room     = SeatPlanRoom::with('seatPlan')->find($allocation['room_id']);
                $roomNo   = $room?->room_no ?? (string) $allocation['room_id'];
                $shift    = $room?->seatPlan?->shift ?? null;

                // Send push notification to the assigned teacher
                $notifier->sendInvigilationDutyNotification(
                    teacherUserId:   $allocation['teacher_user_id'],
                    dutyDate:        $request->date,
                    roomNo:          (string) $roomNo,
                    shift:           $shift,
                    invigilationId:  $invigilation->id
                );
            }
        }

        return response()->json(['message' => 'Duty allocations saved successfully']);
    }

    protected function resolveSchoolId(Request $request, $user, $explicit = null): ?int
    {
        if ($explicit) {
            return (int) $explicit;
        }
        $attr = $request->attributes->get('current_school_id');
        if ($attr) {
            return (int) $attr;
        }
        $firstActive = $user->firstTeacherSchoolId();
        if ($firstActive) {
            return (int) $firstActive;
        }
        $any = $user->schoolRoles()->whereHas('role', fn ($q) => $q->where('name', 'teacher'))->value('school_id');

        return $any ? (int) $any : null;
    }
}
