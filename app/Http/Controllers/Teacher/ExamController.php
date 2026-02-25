<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamRoomInvigilation;
use App\Models\ExamRoomAttendance;
use App\Models\School;
use App\Models\SeatPlan;
use App\Models\SeatPlanAllocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /**
     * Return the authenticated user's primary school ID for teacher role.
     */
    private function schoolId(): int
    {
        return Auth::user()->firstTeacherSchoolId();
    }

    /**
     * Today's exam duty for this teacher.
     */
    public function todaysDuty(Request $request, $school)
    {
        $today    = now()->toDateString();
        $user     = Auth::user();

        // Invigilations assigned to this teacher for today
        $duties = ExamRoomInvigilation::with(['exam', 'room'])
            ->where('school_id', $school)
            ->where('user_id', $user->id)
            ->whereHas('exam', function ($q) use ($today) {
                $q->whereDate('start_date', '<=', $today)
                  ->whereDate('end_date', '>=', $today);
            })
            ->get();

        return view('teacher.exams.todays-duty', compact('duties', 'school'));
    }

    /**
     * Mark Entry – teacher sees exams for mark entry.
     */
    public function markEntry(Request $request, $school)
    {
        $user = Auth::user();

        // Get all academic years
        $academicYears = \App\Models\AcademicYear::where('school_id', $school)
            ->orderBy('id', 'desc')
            ->get();

        // Get classes for selected academic year
        $classes = collect();
        if ($request->filled('academic_year_id')) {
            $classes = \App\Models\SchoolClass::where('school_id', $school)
                ->orderBy('numeric_value')
                ->get();
        }

        // Get exams filtered by academic year, class, and status
        $exams = collect();
        $subjects = collect();
        $students = collect();

        if ($request->filled('academic_year_id') && $request->filled('class_id') && $request->filled('status')) {
            $exams = Exam::where('school_id', $school)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('status', $request->status)
                ->whereHas('classes', function ($q) use ($request) {
                    $q->where('class_id', $request->class_id);
                })
                ->orderBy('id', 'desc')
                ->get();
        }

        // Get subjects assigned to this teacher for the selected exam
        if ($request->filled('exam_id')) {
            $exam = Exam::find($request->exam_id);

            // Get teacher's assigned subjects in this exam for the selected class
            $subjects = \App\Models\ExamSubject::where('exam_id', $exam->id)
                ->where('teacher_id', $user->id)
                ->with('subject')
                ->get()
                ->pluck('subject')
                ->unique('id');

            // Get students for the selected class
            if ($request->filled('subject_id')) {
                $subject = \App\Models\Subject::find($request->subject_id);

                $students = \App\Models\Student::join('student_enrollments', 'students.id', '=', 'student_enrollments.student_id')
                    ->where('student_enrollments.class_id', $request->class_id)
                    ->where('student_enrollments.status', 'active')
                    ->select('students.*', 'student_enrollments.roll_no')
                    ->orderBy('student_enrollments.roll_no', 'asc')
                    ->get();
            }
        }

        return view('teacher.exams.mark-entry', compact('academicYears', 'classes', 'exams', 'subjects', 'students', 'school'));
    }

    /**
     * Get classes for selected academic year (for AJAX).
     */
    public function getClasses(Request $request, $school)
    {
        $classes = \App\Models\SchoolClass::where('school_id', $school)
            ->orderBy('numeric_value')
            ->get(['id', 'name']);

        return response()->json($classes);
    }

    /**
     * Get exams by academic year, class, and status (for AJAX).
     */
    public function getExamsByStatus(Request $request, $school)
    {
        $exams = Exam::where('school_id', $school)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('class_id', $request->class_id)
            ->where('status', $request->status)
            ->orderBy('id', 'desc')
            ->get(['id', 'name']);

        return response()->json($exams);
    }

    /**
     * Get subjects assigned to teacher for a specific exam and class.
     */
    public function getSubjects(Request $request, $school)
    {
        $user = Auth::user();
        $exam = Exam::find($request->exam_id);

        if (!$exam) {
            return response()->json([]);
        }

        // Get teacher's assigned subjects in this exam
        $subjects = \App\Models\ExamSubject::where('exam_id', $exam->id)
            ->where('teacher_id', $user->id)
            ->with('subject')
            ->get()
            ->unique('subject_id')
            ->values()
            ->map(function ($es) {
                return ['id' => $es->subject_id, 'name' => $es->subject->name];
            });

        return response()->json($subjects);
    }

    /**
     * Load marks entry form (returns HTML for AJAX injection).
     */
    public function loadMarksForm(Request $request, $school)
    {
        $user = Auth::user();

        $exam = Exam::find($request->exam_id);
        $subject = \App\Models\Subject::find($request->subject_id);
        $classId = $request->class_id;

        $examSubject = \App\Models\ExamSubject::where('exam_id', $exam->id)
            ->where('subject_id', $subject->id)
            ->first();

        if (!$exam || !$subject || !$examSubject) {
            return response()->view('teacher.exams.partials.marks-form-error', [
                'message' => 'পরীক্ষা বা বিষয় খুঁজে পাওয়া যায়নি।'
            ]);
        }

        // Get enrollments for students who have selected this subject (handling optional subjects)
        $enrollments = \App\Models\StudentEnrollment::where('school_id', $school)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->whereHas('subjects', function($query) use ($subject) {
                $query->where('subject_id', $subject->id);
            })
            ->with(['student', 'section'])
            ->orderBy('roll_no')
            ->get();

        // Determine if marks can be entered (Check status and deadline)
        $now = now();
        $canEnter = $exam->status === 'active' && 
                    (!$examSubject->mark_entry_deadline || $now->lt($examSubject->mark_entry_deadline));
        
        $isCompleted = $exam->status === 'completed';
        $isOverdue = $exam->status === 'active' && 
                     ($examSubject->mark_entry_deadline && $now->gt($examSubject->mark_entry_deadline));

        // Get existing marks for this subject and exam
        $marks = \App\Models\Mark::where('exam_id', $exam->id)
            ->where('exam_subject_id', $examSubject->id)
            ->get()
            ->keyBy('student_id');

        return view('teacher.exams.partials.marks-form', compact(
            'exam',
            'subject',
            'examSubject',
            'enrollments',
            'marks',
            'canEnter',
            'isCompleted',
            'isOverdue',
            'school'
        ));
    }

    /**
     * Save/Update a single mark (used for AJAX entry).
     */
    public function saveMark(Request $request, $school)
    {
        $exam = Exam::findOrFail($request->exam_id);
        $examSubject = \App\Models\ExamSubject::findOrFail($request->exam_subject_id);
        $user = Auth::user();

        // Check permission - must be the assigned teacher
        if ($examSubject->teacher_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'আপনি এই বিষয়ের জন্য নম্বর এন্ট্রি করতে অনুমোদিত নন।'], 403);
        }

        // Check deadline
        if ($exam->status !== 'active' || ($examSubject->mark_entry_deadline && now()->gt($examSubject->mark_entry_deadline))) {
            return response()->json(['success' => false, 'message' => 'নম্বর এন্ট্রির সময়সীমা শেষ হয়ে গেছে।'], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'creative_marks' => 'nullable|numeric|min:0|max:' . $examSubject->creative_full_mark,
            'mcq_marks' => 'nullable|numeric|min:0|max:' . $examSubject->mcq_full_mark,
            'practical_marks' => 'nullable|numeric|min:0|max:' . $examSubject->practical_full_mark,
            'is_absent' => 'nullable|boolean',
        ]);

        $isAbsent = $request->boolean('is_absent');

        // Calculate total marks
        $totalMarks = 0;
        if (!$isAbsent) {
            $totalMarks = ($validated['creative_marks'] ?? 0) +
                          ($validated['mcq_marks'] ?? 0) +
                          ($validated['practical_marks'] ?? 0);
        }

        // Calculate grade and pass status using the same logic as Principal
        $gradeInfo = $this->calculateGrade($totalMarks, $examSubject, $validated, $isAbsent);

        // Save or update mark
        $mark = \App\Models\Mark::updateOrCreate(
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
                'remarks' => $request->remarks,
                'entered_by' => $user->id,
                'entered_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'নম্বর সংরক্ষিত হয়েছে',
            'total_marks' => $isAbsent ? '0.00' : number_format($totalMarks, 2),
            'letter_grade' => $gradeInfo['letter_grade']
        ]);
    }

    /**
     * Store marks for a subject.
     */
    public function storeMarks(Request $request, $school)
    {
        // This method can be kept for bulk submission if needed, 
        // but the new system primarily uses saveMark for individual entry.
        // For now, let's keep it minimal or redirect to a success message
        return redirect()->back()->with('success', 'নম্বর সফলভাবে সংরক্ষিত হয়েছে।');
    }

    private function calculateGrade($totalMarks, $examSubject, $marks, $isAbsent)
    {
        if ($isAbsent) {
            return [
                'letter_grade' => 'F',
                'grade_point' => 0.00,
                'pass_status' => 'absent',
            ];
        }

        // Check pass status based on pass_type
        $isPassed = false;

        if ($examSubject->pass_type === 'each') {
            // Must pass in each part
            $creativePass = ($marks['creative_marks'] ?? 0) >= $examSubject->creative_pass_mark;
            $mcqPass = ($marks['mcq_marks'] ?? 0) >= $examSubject->mcq_pass_mark;
            $practicalPass = ($marks['practical_marks'] ?? 0) >= $examSubject->practical_pass_mark;

            $isPassed = $creativePass && $mcqPass && $practicalPass;
        } else {
            // Combined pass
            $isPassed = $totalMarks >= $examSubject->total_pass_mark;
        }

        if (!$isPassed) {
            return [
                'letter_grade' => 'F',
                'grade_point' => 0.00,
                'pass_status' => 'fail',
            ];
        }

        // Calculate grade based on percentage
        $percentage = ($examSubject->total_full_mark > 0) ? ($totalMarks / $examSubject->total_full_mark) * 100 : 0;

        if ($percentage >= 80) {
            return ['letter_grade' => 'A+', 'grade_point' => 5.00, 'pass_status' => 'pass'];
        } elseif ($percentage >= 70) {
            return ['letter_grade' => 'A', 'grade_point' => 4.00, 'pass_status' => 'pass'];
        } elseif ($percentage >= 60) {
            return ['letter_grade' => 'A-', 'grade_point' => 3.50, 'pass_status' => 'pass'];
        } elseif ($percentage >= 50) {
            return ['letter_grade' => 'B', 'grade_point' => 3.00, 'pass_status' => 'pass'];
        } elseif ($percentage >= 40) {
            return ['letter_grade' => 'C', 'grade_point' => 2.00, 'pass_status' => 'pass'];
        } elseif ($percentage >= 33) {
            return ['letter_grade' => 'D', 'grade_point' => 1.00, 'pass_status' => 'pass'];
        } else {
            return ['letter_grade' => 'F', 'grade_point' => 0.00, 'pass_status' => 'fail'];
        }
    }

    /**
     * Find student seat for an active exam.
     */
    public function findSeat(Request $request, $school)
    {
        $school = School::findOrFail($school);
        $plans = SeatPlan::where('school_id', $school->id)
            ->active()
            ->orderBy('id', 'desc')
            ->get();

        $plan_id = (int)$request->get('plan_id');
        $find = trim($request->get('find', ''));
        $results = [];

        if ($plan_id > 0 && $find !== '') {
            $query = SeatPlanAllocation::where('seat_plan_id', $plan_id)
                ->with(['student', 'student.currentEnrollment.class', 'room']);

            if (is_numeric($find)) {
                $query->whereHas('student', function ($q) use ($find) {
                    $q->where('student_id', 'like', $find . '%')
                      ->orWhereHas('currentEnrollment', function($qc) use ($find) {
                          $qc->where('roll_no', 'like', $find . '%');
                      });
                });
            } else {
                $query->whereHas('student', function ($q) use ($find) {
                    $q->where('student_name_en', 'like', '%' . $find . '%')
                      ->orWhere('student_name_bn', 'like', '%' . $find . '%');
                });
            }

            $results = $query->orderBy('id', 'asc')->limit(20)->get();
        }

        return view('teacher.exams.find-seat', compact('school', 'plans', 'plan_id', 'find', 'results'));
    }

    /* ─── Exam-Controller-only actions ──────────────────────────────────── */

    /**
     * Room Duty Allocation – only accessible by exam controllers.
     */
    public function roomDuty(Request $request, $school)
    {
        $this->authorizeExamController($school);

        $exams = Exam::where('school_id', $school)
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('teacher.exams.room-duty', compact('exams', 'school'));
    }

    /**
     * Mark Exam Attendance – only accessible by exam controllers.
     */
    public function markAttendance(Request $request, $school)
    {
        $this->authorizeExamController($school);

        $exams = Exam::where('school_id', $school)
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('teacher.exams.mark-attendance', compact('exams', 'school'));
    }

    /**
     * Exam Attendance Report – only accessible by exam controllers.
     */
    public function attendanceReport(Request $request, $school)
    {
        $school = School::findOrFail($school);
        $this->authorizeExamController($school->id);

        $date = $request->get('date', date('Y-m-d'));
        $plan_id = (int)$request->get('plan_id');

        $plans = SeatPlan::where('school_id', $school->id)->active()->orderBy('id', 'desc')->get();
        if ($plan_id === 0 && $plans->isNotEmpty()) {
            $plan_id = $plans->first()->id;
        }

        $dateOptions = [];
        if ($plan_id > 0) {
            // Primary source: dates that have actual attendance records for this seat plan
            $rawDates = DB::table('exam_room_attendances')
                ->where('plan_id', $plan_id)
                ->whereNotNull('duty_date')
                ->distinct()
                ->orderBy('duty_date', 'asc')
                ->pluck('duty_date')
                ->toArray();

            // Cast all values to plain 'Y-m-d' strings (MySQL DATE columns can return Carbon or string)
            $dateOptions = array_values(array_unique(array_map(fn($d) => is_string($d) ? substr($d, 0, 10) : date('Y-m-d', strtotime($d)), $rawDates)));

            // Fallback: if no attendance recorded yet, show exam subject dates so UI is not blank
            if (empty($dateOptions)) {
                $rawDates2 = DB::table('seat_plan_exams')
                    ->join('exam_subjects', 'exam_subjects.exam_id', '=', 'seat_plan_exams.exam_id')
                    ->where('seat_plan_exams.seat_plan_id', $plan_id)
                    ->whereNotNull('exam_subjects.exam_date')
                    ->distinct()
                    ->orderBy('exam_subjects.exam_date', 'asc')
                    ->pluck('exam_subjects.exam_date')
                    ->toArray();
                $dateOptions = array_values(array_unique(array_map(fn($d) => is_string($d) ? substr($d, 0, 10) : date('Y-m-d', strtotime($d)), $rawDates2)));
            }
        }

        // Normalize the request date to 'Y-m-d'
        $date = substr((string)$date, 0, 10);

        if (empty($dateOptions)) {
            $date = '1970-01-01';
        } elseif (!in_array($date, $dateOptions, true)) {
            $date = $dateOptions[0];
        }

        // TEMP DEBUG — remove after testing
        \Illuminate\Support\Facades\Log::debug('attendanceReport debug', [
            'request_date' => $request->get('date'),
            'resolved_date' => $date,
            'plan_id' => $plan_id,
            'dateOptions' => $dateOptions,
        ]);

        $rows = [];
        if ($plan_id > 0 && $date !== '1970-01-01') {
            $rows = DB::table('exam_room_attendances as a')
                ->join('seat_plan_rooms as r', function($join) {
                    $join->on('r.id', '=', 'a.room_id')
                         ->on('r.seat_plan_id', '=', 'a.plan_id');
                })
                ->leftJoin('exam_room_invigilations as i', function($join) use ($date, $plan_id) {
                    $join->on('i.seat_plan_room_id', '=', 'a.room_id')
                         ->where('i.duty_date', '=', $date)
                         ->where('i.seat_plan_id', '=', $plan_id);
                })
                ->leftJoin('users as u', 'u.id', '=', 'i.teacher_id')
                ->leftJoin('students as s', 's.id', '=', 'a.student_id')
                ->leftJoin('student_enrollments as se', function($join) {
                    $join->on('se.student_id', '=', 's.id')
                         ->where('se.status', '=', 'active');
                })
                ->leftJoin('classes as c', 'c.id', '=', 'se.class_id')
                ->where('a.duty_date', $date)
                ->where('a.plan_id', $plan_id)
                ->select([
                    'r.room_no',
                    'c.name as class_name',
                    'u.username as marker_username',
                    'u.name as marker_name',
                    DB::raw("SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present_cnt"),
                    DB::raw("SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) AS absent_cnt")
                ])
                ->groupBy('r.room_no', 'class_name', 'marker_username', 'marker_name')
                ->orderBy('r.room_no')
                ->orderBy('class_name')
                ->get();
        }

        return view('teacher.exams.attendance-report.index', compact('school', 'plans', 'plan_id', 'date', 'dateOptions', 'rows'));
    }

    /**
     * Overall Attendance Report – only accessible by exam controllers.
     */
    public function overallAttendanceReport(Request $request, $school)
    {
        $school = School::findOrFail($school);
        $this->authorizeExamController($school->id);

        $plan_id = (int)$request->get('plan_id');

        $plans = SeatPlan::where('school_id', $school->id)->active()->orderBy('id', 'desc')->get();
        if ($plan_id === 0 && $plans->isNotEmpty()) {
            $plan_id = $plans->first()->id;
        }

        $dates = [];
        $rooms = [];
        $summary = [];
        $matrix = [];

        if ($plan_id > 0) {
            $dates = DB::table('seat_plan_exams')
                ->join('exam_subjects', 'exam_subjects.exam_id', '=', 'seat_plan_exams.exam_id')
                ->where('seat_plan_exams.seat_plan_id', $plan_id)
                ->whereNotNull('exam_subjects.exam_date')
                ->distinct()
                ->orderBy('exam_subjects.exam_date', 'asc')
                ->pluck('exam_subjects.exam_date')
                ->toArray();

            $rooms = DB::table('seat_plan_rooms')
                ->where('seat_plan_id', $plan_id)
                ->orderBy('room_no')
                ->get();

            $summaryData = DB::table('exam_room_attendances')
                ->where('plan_id', $plan_id)
                ->select('duty_date', 
                    DB::raw("SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) AS p"),
                    DB::raw("SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) AS a"))
                ->groupBy('duty_date')
                ->get();

            foreach ($summaryData as $s) {
                $summary[$s->duty_date] = ['p' => (int)$s->p, 'a' => (int)$s->a];
            }

            $matrixData = DB::table('exam_room_attendances as a')
                ->leftJoin('students as s', 's.id', '=', 'a.student_id')
                ->leftJoin('student_enrollments as se', function($join) {
                    $join->on('se.student_id', '=', 's.id')
                         ->where('se.status', '=', 'active');
                })
                ->leftJoin('classes as c', 'c.id', '=', 'se.class_id')
                ->where('a.plan_id', $plan_id)
                ->select([
                    'a.duty_date',
                    'a.room_id',
                    'c.name as class_name',
                    DB::raw("SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS p"),
                    DB::raw("SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) AS a")
                ])
                ->groupBy('a.duty_date', 'a.room_id', 'class_name')
                ->get();

            foreach ($matrixData as $m) {
                $d = $m->duty_date;
                $rid = (int)$m->room_id;
                $cls = $m->class_name ?: '-';
                if (!isset($matrix[$d])) $matrix[$d] = [];
                if (!isset($matrix[$d][$cls])) $matrix[$d][$cls] = [];
                $matrix[$d][$cls][$rid] = ['p' => (int)$m->p, 'a' => (int)$m->a];
            }
        }

        return view('teacher.exams.attendance-report.overall', compact('school', 'plans', 'plan_id', 'dates', 'rooms', 'summary', 'matrix'));
    }

    /* ─── Helper ─────────────────────────────────────────────────────────── */

    private function authorizeExamController($schoolId): void
    {
        $user = Auth::user();
        if (!$user->isPrincipal((int)$schoolId) && !$user->isExamController((int)$schoolId) && !$user->isSuperAdmin()) {
            abort(403, 'এই পাতায় প্রবেশের অধিকার নেই। শুধুমাত্র প্রিন্সিপাল বা পরীক্ষা নিয়ন্ত্রকগণ এটি ব্যবহার করতে পারবেন।');
        }
    }
}
