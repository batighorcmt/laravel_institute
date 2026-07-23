<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Homework;
use App\Models\Attendance;
use App\Models\Result;


use App\Models\StudentLeave;
use App\Models\RoutineEntry;
use App\Models\ExtraClassAttendance;
use App\Models\LessonEvaluationRecord;
use App\Models\ParentFeedback;
use App\Models\Teacher;
use App\Models\ClassSubject;
use App\Models\StudentSubject;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\StudentResource;
use App\Http\Resources\HomeworkResource;
use App\Http\Resources\StudentAttendanceResource;
use App\Http\Resources\ResultResource;
use App\Http\Resources\TeacherLeaveResource;
use App\Http\Resources\StudentProfileResource;
use App\Http\Resources\RoutineResource;
use App\Http\Resources\ParentFeedbackResource;
use App\Http\Resources\TeacherResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    use \App\Traits\ResultCalculationTrait;

    private function getPrincipalTeacher($school)
    {
        $role = \App\Models\Role::where('name', 'principal')->first();
        if (!$role) return null;

        $userSchoolRole = \App\Models\UserSchoolRole::where('school_id', $school->id)
            ->where('role_id', $role->id)
            ->first();

        if (!$userSchoolRole) return null;

        return Teacher::where('user_id', $userSchoolRole->user_id)->first();
    }


    public function children(Request $request)
    {
        $students = $this->resolveChildren($request);
        
        return StudentResource::collection($students)->additional([
            'count' => $students->count(),
            'message' => 'সন্তান তালিকা',
        ]);
    }

    public function homework(Request $request)
    {
        $date = $request->get('date');
        $studentId = $request->get('student_id');
        $students = $this->resolveChildren($request);
        $schoolId = $request->attributes->get('current_school_id');
        
        $query = Homework::query()->with(['subject', 'teacher']);
        
        if ($schoolId) { 
            $query->forSchool($schoolId); 
        }

        $student = null;
        if ($studentId) {
            $student = $students->where('id', $studentId)->first();
        } else {
            $student = $students->first();
        }

        if ($student) {
            $enrollment = $student->currentEnrollment;
            $classId = $student->class_id ?? $enrollment?->class_id;
            $sectionId = $enrollment?->section_id;

            if ($classId) {
                $query->where('class_id', $classId);
            }
            if ($sectionId) {
                $query->where('section_id', $sectionId);
            }
        } else {
            // Apply filters based on all children if no specific student is found
            $classIds = $students->map(function($s) {
                return $s->class_id ?? $s->currentEnrollment?->class_id;
            })->filter()->unique();
            
            $sectionIds = $students->map(fn($s) => $s->currentEnrollment?->section_id)->filter()->unique();
            
            if ($classIds->isNotEmpty()) {
                $query->whereIn('class_id', $classIds);
            }
            if ($sectionIds->isNotEmpty()) {
                $query->whereIn('section_id', $sectionIds);
            }
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('homework_date', [$request->get('from_date'), $request->get('to_date')]);
        } elseif ($date) {
            $query->forDate($date);
        } else {
            $query->where(function($q) {
                $q->where('homework_date', '>=', Carbon::now()->subDays(60)->toDateString())
                  ->orWhere('submission_date', '>=', Carbon::now()->toDateString());
            });
        }

        $homeworks = $query->orderByDesc('homework_date')->get();

        return HomeworkResource::collection($homeworks)->additional([
            'date' => $date ?? Carbon::now()->toDateString(),
            'children_count' => $students->count(),
            'message' => $date ? 'নির্দিষ্ট দিনের হোমওয়ার্ক' : 'সাম্প্রতিক হোমওয়ার্ক',
        ]);
    }

    public function attendance(Request $request)
    {
        $date = $request->get('date'); // optional single date
        $month = $request->get('month'); // 1-12
        $year = $request->get('year');
        $studentId = $request->get('student_id');
        $students = $this->resolveChildren($request);
        $ids = $students->pluck('id');
        $query = Attendance::query()->whereIn('student_id', $ids);
        if ($studentId && $ids->contains($studentId)) {
            $query->where('student_id', $studentId);
        }
        if ($date) { 
            $query->whereDate('date', $date); 
        } elseif ($month && $year) {
            $query->whereMonth('date', $month)->whereYear('date', $year);
        }
        $query->orderByDesc('date');
        $records = $query->limit(200)->get();
        return StudentAttendanceResource::collection($records)->additional([
            'children' => $students->count(),
            'message' => 'হাজিরা তালিকা',
        ]);
    }



    public function leavesIndex(Request $request)
    {
        $schoolId = $request->attributes->get('current_school_id');
        $children = $this->resolveChildren($request);
        $studentIds = $children->pluck('id');

        $query = StudentLeave::query()->whereIn('student_id', $studentIds);
        if ($schoolId) { $query->forSchool($schoolId); }
        if ($request->filled('status')) { $query->where('status', $request->get('status')); }
        $leaves = $query->orderByDesc('start_date')->limit(100)->get();

        return \App\Http\Resources\StudentLeaveResource::collection($leaves)->additional([
            'children' => $children->count(),
            'message' => 'ছুটি আবেদন তালিকা',
        ]);
    }

    public function leavesStore(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');

        $children = $this->resolveChildren($request);
        $allowedIds = $children->pluck('id')->toArray();

        $validated = $request->validate([
            'student_id' => ['required','integer', function($attr,$value,$fail) use ($allowedIds){ if (!in_array((int)$value, $allowedIds, true)) { $fail('অবৈধ শিক্ষার্থী'); } }],
            'title' => ['nullable','string','max:150'],
            'reason' => ['required','string','max:255'],
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after_or_equal:start_date'],
            'type' => ['nullable','string','max:50'],
        ]);

        $student_id = (int)$validated['student_id'];
        $student = $children->firstWhere('id', $student_id);
        $finalSchoolId = $schoolId ?: ($student ? $student->school_id : null);
        $enrollment = $student?->currentEnrollment;

        $leave = StudentLeave::create([
            'school_id' => $finalSchoolId,
            'student_id' => $student_id,
            'class_id' => $enrollment?->class_id,
            'section_id' => $enrollment?->section_id,
            'title' => $validated['title'] ?? null,
            'type' => $validated['type'] ?? null,
            'reason' => $validated['reason'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'pending',
        ]);

        if ($enrollment?->section_id) {
            $section = \App\Models\Section::find($enrollment->section_id);
            $classTeacher = $section?->class_teacher_id ? Teacher::find($section->class_teacher_id) : null;
            if ($classTeacher?->user_id) {
                try {
                    app(\App\Services\PushNotificationService::class)
                        ->sendStudentLeaveNotification($classTeacher->user_id, $leave);
                } catch (\Throwable $e) {
                    \Log::error('Student leave push notification failed: '.$e->getMessage());
                }
            }
        }

        return (new \App\Http\Resources\StudentLeaveResource($leave))->additional([
            'message' => 'ছুটি আবেদন জমা হয়েছে',
        ]);
    }

    public function profile(Request $request)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        return new StudentProfileResource($student);
    }

    public function subjects(Request $request)
    {
        $studentId = $request->get('student_id');
        $students = $this->resolveChildren($request);
        $student = $studentId ? $students->firstWhere('id', $studentId) : $students->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $enrollment = $student->currentEnrollment;
        if (!$enrollment) {
            return response()->json(['data' => [], 'message' => 'অ্যাক্টিভ এনরোলমেন্ট পাওয়া যায়নি']);
        }

        // Check if student has specific subject assignments
        $assignedSubjects = StudentSubject::where('student_enrollment_id', $enrollment->id)
            ->where('status', 'active')
            ->with(['subject', 'classSubject'])
            ->get();

        if ($assignedSubjects->isNotEmpty()) {
            return response()->json([
                'data' => $assignedSubjects->sortBy(fn($s) => $s->classSubject->order_no ?? 999)->map(fn($s) => [
                    'id' => $s->subject_id,
                    'name' => $s->subject->name ?? 'N/A',
                    'code' => $s->subject->code ?? '',
                    'is_optional' => $s->is_optional,
                ])->values(),
                'message' => 'অ্যাসাইনকৃত বিষয় সমূহ',
            ]);
        }

        // Fallback to class subjects if no specific assignments
        $classId = $student->class_id ?? $enrollment->class_id;
        $subjects = ClassSubject::where('class_id', $classId)
            ->where('school_id', $student->school_id)
            ->where('status', 'active')
            ->whereHas('subject')
            ->with('subject')
            ->orderBy('order_no')
            ->get()
            ->unique('subject_id');

        return response()->json([
            'data' => $subjects->map(fn($s) => [
                'id' => $s->subject_id,
                'name' => $s->subject->name ?? 'N/A',
                'code' => $s->subject->code ?? '',
                'is_optional' => $s->is_optional,
            ])->values(),
            'message' => 'পঠিত বিষয় সমূহ',
        ]);
    }

    public function classRoutine(Request $request)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        // We need section_id from enrollment
        $enrollment = $student->currentEnrollment;
        if (!$enrollment) {
            return response()->json(['message' => 'এনরোলমেন্ট পাওয়া যায়নি'], 404);
        }

        $routine = RoutineEntry::where('class_id', $enrollment->class_id)
            ->where('section_id', $enrollment->section_id)
            ->with(['subject', 'teacher'])
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->get();

        return RoutineResource::collection($routine)->additional([
            'message' => 'ক্লাস রুটিন',
        ]);
    }



    public function lessonEvaluations(Request $request)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $subjectId = $request->get('subject_id');
        $teacherId = $request->get('teacher_id');
        $status = $request->get('status');

        $query = LessonEvaluationRecord::where('student_id', $student->id)
            ->with(['lessonEvaluation.subject', 'lessonEvaluation.teacher']);

        $query->whereHas('lessonEvaluation', function($q) use ($fromDate, $toDate, $subjectId, $teacherId, $status) {
            // Default to today if no filters at all
            if (!$fromDate && !$toDate && !$subjectId && !$teacherId && !$status) {
                $q->whereDate('evaluation_date', Carbon::today());
            } else {
                if ($fromDate) {
                    $q->whereDate('evaluation_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $q->whereDate('evaluation_date', '<=', $toDate);
                }
            }

            if ($subjectId) {
                $q->where('subject_id', $subjectId);
            }
            if ($teacherId) {
                $q->where('teacher_id', $teacherId);
            }
        });

        if ($status) {
            $query->where('status', $status);
        }

        $records = $query->latest()->get();

        return response()->json([
            'data' => $records->map(function($r) {
                $eval = $r->lessonEvaluation;
                return [
                    'id' => $r->id,
                    'date' => $eval && $eval->evaluation_date ? $eval->evaluation_date->toDateString() : 'N/A',
                    'subject' => $eval->subject->name ?? 'N/A',
                    'teacher' => $eval->teacher ? ($eval->teacher->full_name_bn ?: $eval->teacher->full_name) : 'N/A',
                    'notes' => $eval->notes ?? '',
                    'status' => $r->status,
                    'status_label' => $r->status_label,
                    'status_color' => $r->status_color,
                    'remarks' => $r->remarks,
                ];
            }),
            'message' => 'লেসন ইভ্যালুয়েশন রিপোর্ট',
        ]);
    }

    public function lessonEvaluationStats(Request $request)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $year = $request->get('year', date('Y'));

        $records = LessonEvaluationRecord::where('student_id', $student->id)
            ->whereHas('lessonEvaluation', function($q) use ($year) {
                $q->whereYear('evaluation_date', $year);
            })
            ->with(['lessonEvaluation.subject', 'lessonEvaluation.teacher'])
            ->get();

        $stats = $records->groupBy('lessonEvaluation.subject_id')->map(function($group) {
            $first = $group->first()->lessonEvaluation;
            return [
                'subject_id' => $first->subject_id,
                'subject_name' => $first->subject->name ?? 'N/A',
                'teacher_name' => $first->teacher ? ($first->teacher->full_name_bn ?: $first->teacher->full_name) : 'N/A',
                'completed' => $group->where('status', 'completed')->count(),
                'partial' => $group->where('status', 'partial')->count(),
                'not_done' => $group->where('status', 'not_done')->count(),
                'absent' => $group->where('status', 'absent')->count(),
            ];
        })->values();

        return response()->json([
            'year' => $year,
            'data' => $stats,
            'message' => 'বিষয়ভিত্তিক বাৎসরিক লেসন ইভ্যালুয়েশন পরিসংখ্যান',
        ]);
    }

    public function exams(Request $request)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $classId = $student->currentEnrollment?->class_id ?? $student->class_id;

        $exams = \App\Models\Exam::where('school_id', $student->school_id)
            ->where('class_id', $classId)
            ->whereIn('status', ['published', 'completed'])
            ->orderByDesc('start_date')
            ->get()->map(function($e) {
                return [
                    'id' => $e->id,
                    'name' => $e->name,
                    'start_date' => $e->start_date ? Carbon::parse($e->start_date)->format('d M, Y') : null,
                    'end_date' => $e->end_date ? Carbon::parse($e->end_date)->format('d M, Y') : null,
                    'result_publish_date' => $e->result_publish_date ? Carbon::parse($e->result_publish_date)->format('d M, Y') : null,

                    'status' => $e->status

                ];
            });

        return response()->json([
            'data' => $exams,
            'message' => 'পরীক্ষার তালিকা'
        ]);
    }

    public function examResults(Request $request, $examId)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $exam = \App\Models\Exam::find($examId);
        if (!$exam) {
            return response()->json(['message' => 'পরীক্ষা পাওয়া যায়নি'], 404);
        }

        $schoolModel = \App\Models\School::find($student->school_id);
        $classId = $student->currentEnrollment?->class_id ?? $student->class_id;
        
        $calc = $this->getCachedClassResults($schoolModel, $exam, $classId);

        if (!$calc || empty($calc['results']) || $calc['results']->isEmpty()) {
            return response()->json(['message' => 'এই পরীক্ষার কোনো ফলাফল পাওয়া যায়নি।'], 404);
        }

        $result = $calc['results']->firstWhere('student_id', $student->id);

        if (!$result) {
            return response()->json(['message' => 'এই পরীক্ষার কোনো ফলাফল পাওয়া যায়নি।'], 404);
        }

        // Only expose computed grades/GPA once a principal has explicitly published
        // this student's result via Principal\ResultController::publishResults()
        // (which flips Result.is_published). Exam.status alone (published/completed)
        // is not sufficient — a principal may mark an exam completed before marks
        // are finalized, or may unpublish results after the fact.
        if (empty($result->is_published)) {
            return response()->json([
                'published' => false,
                'message'   => 'ফলাফল এখনো প্রকাশ করা হয়নি।',
            ], 403);
        }

        // A subject with no Mark row at all is deliberately excluded from the
        // pass/fail count in ResultCalculationTrait (so an in-progress mark
        // entry doesn't wrongly compute as "Fail") — but that also means a
        // result computed from only 2 of 9 subjects can come out "Passed"
        // just because the other 7 haven't been touched yet. is_complete
        // (set in the trait) is true only once every subject has a record,
        // so gate on it here rather than showing a misleading Pass/Fail.
        if (empty($result->is_complete)) {
            return response()->json([
                'published' => false,
                'message'   => 'ফলাফল প্রস্তুত হচ্ছে। কিছু বিষয়ের মার্ক এখনো এন্ট্রি করা হয়নি।',
            ], 403);
        }

        $finalSubjects = $calc['finalSubjects'] ?? collect();

        // Build subjects list from precomputed subject_results on the result object
        // Preserve the order from subject_results (which mirrors the web marksheet order)
        $subjectsData = [];
        foreach ($result->subject_results as $key => $sr) {
            $fSub = $finalSubjects->get($key);

            $creativeFullMark  = $fSub['creative_full_mark']  ?? 0;
            $mcqFullMark       = $fSub['mcq_full_mark']       ?? 0;
            $practicalFullMark = $fSub['practical_full_mark'] ?? 0;

            $subjectsData[] = [
                'key'                 => $key,
                'name'                => $sr['name']     ?? ($fSub['name'] ?? '?'),
                'type'                => !empty($sr['display_only'])      ? 'display_only'
                                       : (($fSub['type'] ?? '') === 'combined' ? 'combined'    : 'single'),
                'creative_marks'      => $sr['creative']  ?? 0,
                'mcq_marks'           => $sr['mcq']       ?? 0,
                'practical_marks'     => $sr['practical'] ?? 0,
                'total_marks'         => $sr['total']     ?? 0,
                'creative_full_mark'  => $creativeFullMark,
                'mcq_full_mark'       => $mcqFullMark,
                'practical_full_mark' => $practicalFullMark,
                'full_marks'          => $sr['full_mark'] ?? ($fSub['total_full_mark'] ?? 0),
                'letter_grade'        => $sr['grade']     ?? 'F',
                'grade_point'         => $sr['gpa']       ?? 0,
                'is_optional'         => $sr['is_optional'] ?? false,
                'is_absent'           => $sr['is_absent']  ?? false,
                'is_failed'           => ($sr['grade'] ?? 'F') === 'F',
            ];
        }

        // Use computed fields from the Trait, fall back to stored DB values if needed
        $totalMarks = $result->computed_total_marks ?? $result->total_marks ?? 0;
        $totalGpa   = $result->computed_gpa        ?? $result->gpa         ?? 0;
        $totalGrade = $result->computed_letter      ?? $result->letter_grade ?? 'F';
        $position   = $result->class_position       ?? $result->position    ?? '-';
        $status     = $result->computed_status      ?? ($result->result_status ?? 'N/A');

        return response()->json([
            'exam' => [
                'id'   => $exam->id,
                'name' => $exam->name,
            ],
            'summary' => [
                'total_marks' => $totalMarks,
                'total_gpa'   => $totalGpa,
                'total_grade' => $totalGrade,
                'position'    => $position,
                'status'      => $status,
            ],
            'subjects'      => $subjectsData,
            'marksheet_url' => route('api.parent.exams.marksheet', ['exam' => $exam->id, 'student_id' => $student->id]),

            'message'       => 'পরীক্ষার ফলাফল',
        ]);
    }

    public function examMarksheetPdf(Request $request, $examId)
    {
        $studentId = $request->get('student_id');
        $children  = $this->resolveChildren($request);
        $student   = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $exam = \App\Models\Exam::find($examId);
        if (!$exam) {
            return response()->json(['message' => 'পরীক্ষা পাওয়া যায়নি'], 404);
        }

        $school  = \App\Models\School::find($student->school_id);
        $classId = $student->currentEnrollment?->class_id ?? $student->class_id;

        $calc = $this->getCachedClassResults($school, $exam, $classId);

        if (!$calc || empty($calc['results']) || $calc['results']->isEmpty()) {
            return response()->json(['message' => 'ফলাফল পাওয়া যায়নি।'], 404);
        }

        $result = $calc['results']->firstWhere('student_id', $student->id);

        if (!$result) {
            return response()->json(['message' => 'ফলাফল পাওয়া যায়নি।'], 404);
        }

        // Same gates as examResults() — the marksheet URL is only ever
        // surfaced there after these pass, but this endpoint is reachable
        // directly too, so it needs its own check rather than trusting the
        // caller went through examResults() first.
        if (empty($result->is_published)) {
            return response()->json(['message' => 'ফলাফল এখনো প্রকাশ করা হয়নি।'], 403);
        }
        if (empty($result->is_complete)) {
            return response()->json(['message' => 'ফলাফল প্রস্তুত হচ্ছে। কিছু বিষয়ের মার্ক এখনো এন্ট্রি করা হয়নি।'], 403);
        }

        $finalSubjects    = $calc['finalSubjects'];
        $principalTeacher = $this->getPrincipalTeacher($school);

        // Force lang=en for English font and labels as requested by user
        $request->merge(['lang' => 'en']);

        $content = view('principal.results.partials._marksheet_content', [
            'student'          => $student,
            'result'           => $result,
            'school'           => $school,
            'exam'             => $exam,
            'finalSubjects'    => $finalSubjects,
            'principalTeacher' => $principalTeacher,
        ])->render();
        $styles = view('principal.results.partials._marksheet_pdf_styles')->render();

        // Same markup + styles as Principal\ResultController::printMarksheet()'s
        // PDF-download branch — these must stay identical (share the partial
        // above rather than re-copying it), since this only renders correctly
        // in DomPDF's limited CSS support, not a regular browser.
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'.$styles.'</head><body>' . $content . '</body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => true]);

        $filename = 'Marksheet-' . ($student->student_id ?? $student->id) . '-' . $exam->id . '.pdf';

        return $pdf->download($filename);
    }




    public function teachers(Request $request)
    {
        $children = $this->resolveChildren($request);
        $student = $children->first();
        $schoolId = $student?->school_id;
        
        $query = Teacher::query()->active();
        if ($schoolId) {
            $query->forSchool($schoolId);
        }
        
        $teachers = $query->orderBy('serial_number')->get();

        return TeacherResource::collection($teachers)->additional([
            'message' => 'বিদ্যালয়ের সকল শিক্ষক তালিকা',
        ]);
    }

    public function feedbackIndex(Request $request)
    {
        $user = $request->user();
        $feedbacks = ParentFeedback::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return ParentFeedbackResource::collection($feedbacks)->additional([
            'message' => 'মতামত/অভিযোগ তালিকা',
        ]);
    }

    public function feedbackStore(Request $request)
    {
        $user = $request->user();

        $children = $this->resolveChildren($request);
        $allowedIds = $children->pluck('id')->toArray();
        $requestedStudentId = $request->filled('student_id') ? (int) $request->input('student_id') : null;
        $studentId = ($requestedStudentId && in_array($requestedStudentId, $allowedIds, true))
            ? $requestedStudentId
            : $children->first()?->id;

        $student = $studentId ? $children->firstWhere('id', $studentId) : null;
        $schoolId = $request->attributes->get('current_school_id') ?: $student?->school_id;

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $feedback = ParentFeedback::create([
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'student_id' => $studentId,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        $school = \App\Models\School::find($schoolId);
        $principalTeacher = $school ? $this->getPrincipalTeacher($school) : null;
        if ($principalTeacher?->user_id) {
            try {
                app(\App\Services\PushNotificationService::class)
                    ->sendFeedbackSubmittedNotification($principalTeacher->user_id, $feedback);
            } catch (\Throwable $e) {
                \Log::error('Feedback submitted push failed: '.$e->getMessage());
            }
        }

        return new ParentFeedbackResource($feedback);
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        $user = $request->user();
        $path = $request->file('photo')->store('avatars', 'public');

        $user->avatar = $path;
        $user->save();

        return response()->json([
            'message' => 'প্রোফাইল ছবি আপডেট হয়েছে',
            'photo_url' => Storage::url($path),
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'বর্তমান পাসওয়ার্ড সঠিক নয়'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->password_changed_at = now();
        $user->save();

        return response()->json(['message' => 'পাসওয়ার্ড সফলভাবে পরিবর্তিত হয়েছে']);
    }


    public function getFees(Request $request)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        // 1. Pending/Due Fees
        $dueFees = \App\Models\StudentFee::with(['feeStructure.category'])
            ->where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function (\App\Models\StudentFee $fee) {

                $fine = (float)$fee->calculateFine();
                $dueBase = (float)($fee->amount - $fee->paid_amount);
                
                if ($dueBase <= 0.01 && $fine <= 0.01) {
                    return null;
                }

                return [
                    'id' => $fee->id,
                    'category_name' => $fee->getFormattedName(),
                    'due_date' => $fee->getEffectiveDueDate(),
                    'amount' => (float)$fee->amount,
                    'paid_amount' => (float)$fee->paid_amount,
                    'fine' => $fine,
                    'total_due' => (float)max(0, $dueBase + $fine),
                    'status' => $fee->status,
                ];
            })->filter()->values();

        // 2. Paid Fees (Payments)
        $paidPayments = \App\Models\Payment::where('student_id', $student->id)
            ->where('status', 'settled')
            ->orderByDesc('received_at')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount_paid' => (float)$payment->amount_paid,
                    'fine_applied' => (float)$payment->fine_applied,
                    'payment_method' => $payment->payment_method,
                    'received_at' => $payment->received_at ? $payment->received_at->toDateTimeString() : null,
                    'receipt_id'  => $payment->id,
                    'receipt_url' => 'billing/fees/receipt/' . $payment->id . '/download',
                ];
            });

        // Get current academic year for payment
        $currentYear = \App\Models\AcademicYear::where('school_id', $student->school_id)
            ->where('is_current', true)
            ->first();

        $enrollment = $student->currentEnrollment()->with(['class', 'section'])->first();

        return response()->json([
            'due_fees' => $dueFees,
            'paid_fees' => $paidPayments,
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'student_id' => $student->student_id,
                'photo' => $student->photo,
                'class' => $enrollment->class->name ?? 'N/A',
                'section' => $enrollment->section->name ?? 'N/A',
                'roll' => $enrollment->roll_no ?? 'N/A',
            ],
            'academic_year_id' => $currentYear ? $currentYear->id : null,
            'message' => 'ফিসের হিসাব'
        ]);
    }

    public function getNotices(Request $request)
    {
        $children = $this->resolveChildren($request);
        if ($children->isEmpty()) return response()->json(['data' => []]);
        
        $schoolId = $children->first()->school_id;
        $classIds = $children->map(fn($s) => $s->class_id ?? $s->currentEnrollment?->class_id)->filter()->unique();
        $sectionIds = $children->map(fn($s) => $s->currentEnrollment?->section_id)->filter()->unique();
        $studentIds = $children->pluck('id')->unique();

        $notices = \App\Models\Notice::published()
            ->where('school_id', $schoolId)
            ->where(function ($q) use ($classIds, $sectionIds, $studentIds) {
                // 1. All or Students (no targets = everyone in that group)
                $q->whereIn('audience_type', ['all', 'students', 'parents']);

                // 2. Class Targets
                $q->orWhere(function ($qq) use ($classIds) {
                    $qq->whereHas('targets', function($t) use ($classIds) {
                        $t->where('targetable_type', \App\Models\SchoolClass::class)->whereIn('targetable_id', $classIds);
                    });
                });

                // 3. Section Targets
                $q->orWhere(function ($qq) use ($sectionIds) {
                    $qq->whereHas('targets', function($t) use ($sectionIds) {
                        $t->where('targetable_type', \App\Models\Section::class)->whereIn('targetable_id', $sectionIds);
                    });
                });

                // 4. Student specific targets
                $q->orWhere(function ($qq) use ($studentIds) {
                    $qq->whereHas('targets', function($t) use ($studentIds) {
                        $t->where('targetable_type', \App\Models\Student::class)->whereIn('targetable_id', $studentIds);
                    });
                });
            })

            ->with(['author:id,name'])
            ->orderByDesc('publish_at')
            ->limit(10)
            ->get()
            ->map(function (\App\Models\Notice $notice) use ($request) {

                $isRead = $notice->reads()->where('user_id', $request->user()->id)->exists();
                return [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'body' => $notice->body,
                    'author' => $notice->author->name ?? 'Admin',
                    'date' => $notice->publish_at->format('d M, Y'),
                    'is_unread' => !$isRead,
                    'is_read' => $isRead
                ];

            });

        return response()->json(['data' => $notices]);
    }

    // getCalculatedResults() (ResultCalculationTrait) recomputes results for
    // every student in the class — marks, per-student attendance over the
    // whole academic year, ranking, per-subject highest-mark — regardless of
    // how many students actually get filtered into the response. For a
    // parent/student checking one result that's the same expensive class-wide
    // pass every other parent in the same class triggers independently, which
    // is what made this endpoint painfully slow (or effectively hung) for
    // bigger classes. Cache the unfiltered class-wide computation so the
    // whole class shares one computation instead of recomputing it per
    // request, then let callers filter to their one student afterward.
    // Principal::publishResults()/unpublishResults() bust this key, so a
    // freshly (un)published result is never stale by more than the TTL below.
    private function getCachedClassResults($school, $exam, $classId)
    {
        $cacheKey = "parent_exam_results:{$school->id}:{$exam->id}:{$classId}";

        return Cache::remember($cacheKey, now()->addMinutes(20), function () use ($school, $exam, $classId) {
            return $this->getCalculatedResults($school, $exam->id, $classId);
        });
    }

    /* Utility: resolve parent children set */
    private function resolveChildren(Request $request)
    {
        $user = $request->user();
        
        // ১. সরাসরি ইউজার আইডির সাথে যুক্ত শিক্ষার্থী
        $directStudent = Student::active()->where('user_id', $user->id)->with('currentEnrollment')->first();
        if ($directStudent) {
            return collect([$directStudent]);
        }

        // ২. অভিভাবক হিসেবে যুক্ত শিক্ষার্থী
        // Scope to schools where this user actually holds an active parent role,
        // so a reused/shared guardian_phone at another school does not leak that
        // school's student record to this parent (see UserSchoolRole created in
        // Student::ensureUserAccount()).
        $parentSchoolIds = $user->getSchoolsForRole(\App\Models\Role::PARENT)->pluck('id');
        if ($parentSchoolIds->isEmpty()) {
            return collect();
        }

        $query = Student::query()->active()->with(['currentEnrollment', 'class', 'school']);

        $phone = $user->username;
        $cleanPhone = ltrim(str_replace(['+', '88'], '', $phone), '0');

        $query->whereIn('school_id', $parentSchoolIds)
            ->where(function($q) use ($user, $phone, $cleanPhone) {
            $q->where('guardian_phone', $phone)
              ->orWhere('guardian_phone', '0' . $cleanPhone)
              ->orWhere('guardian_phone', '880' . $cleanPhone)
              ->orWhere('guardian_phone', '+880' . $cleanPhone)
              ->orWhere('guardian_phone', $user->email);
        });

        return $query->get();
    }
}
