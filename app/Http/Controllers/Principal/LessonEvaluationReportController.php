<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\LessonEvaluation;
use App\Models\School;
use Illuminate\Http\Request;

class LessonEvaluationReportController extends Controller
{
    public function index(School $school, Request $request)
    {
        $lang = $request->get('lang', 'bn');
        $query = LessonEvaluation::with(['teacher', 'class', 'section', 'subject'])
            ->forSchool($school->id)
            ->orderByDesc('evaluation_date');

        // Date Range filter
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if ($fromDate) {
            $query->whereDate('evaluation_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('evaluation_date', '<=', $toDate);
        }

        // Additional filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->get('section_id'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->get('subject_id'));
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->get('teacher_id'));
        }

        // Per-page control
        $perPage = (int) $request->get('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100, 200])) {
            $perPage = 25;
        }

        $evaluations = $query->paginate($perPage)->withQueryString();

        // Meta for filters
        $classes = \App\Models\SchoolClass::forSchool($school->id)->ordered()->get();
        $teachers = \App\Models\Teacher::forSchool($school->id)->active()->orderBy('first_name')->get();
        $subjects = \App\Models\Subject::forSchool($school->id)->orderBy('name')->get();

        // Sections only if class is selected
        $sections = collect();
        if ($request->filled('class_id')) {
            $sections = \App\Models\Section::forSchool($school->id)->where('class_id', $request->class_id)->ordered()->get();
        }

        return view('principal.lesson-evaluations.index', compact(
            'school', 'evaluations', 'fromDate', 'toDate', 'perPage', 
            'classes', 'sections', 'subjects', 'teachers', 'lang'
        ));
    }

    public function show(School $school, LessonEvaluation $lessonEvaluation)
    {
        // Load only records that belong to active students
        $lessonEvaluation->load(['teacher', 'class', 'section', 'subject', 'records' => function($q){
            $q->whereHas('student', fn($s)=>$s->where('status','active'))->with(['student' => fn($s)=>$s->where('status','active')]);
        }]);

        $stats = $lessonEvaluation->getCompletionStats();

        return view('principal.lesson-evaluations.show', compact('school', 'lessonEvaluation', 'stats'));
    }

    /**
     * JSON endpoint for mobile clients: single evaluation with records and student info
     */
    public function apiShow($id)
    {
        $ev = LessonEvaluation::with(['teacher.user','class','section','subject','records' => function($q){
                $q->whereHas('student', fn($s)=>$s->where('status','active'))->with(['student' => fn($s)=>$s->where('status','active')]);
            }])
            ->findOrFail($id);

        $data = [
            'evaluation' => [
                'id' => $ev->id,
                'evaluation_date' => $ev->evaluation_date?->format('Y-m-d'),
                'evaluation_time' => $ev->evaluation_time?->format('H:i'),
                'teacher' => [
                    'id' => $ev->teacher?->id,
                    'name' => $ev->teacher?->full_name ?? $ev->teacher?->user?->name,
                ],
                'class_name' => $ev->class?->name,
                'section_name' => $ev->section?->name,
                'subject_name' => $ev->subject?->name,
                'notes' => $ev->notes,
                'status' => $ev->status,
                'stats' => $ev->getCompletionStats(),
                'records' => $ev->records->map(function($r){
                    // Skip records without an active student
                    if (! $r->student) return null;
                    return [
                        'id' => $r->id,
                        'student_id' => $r->student_id,
                        'status' => $r->status,
                        'status_label' => $r->status_label,
                        'status_color' => $r->status_color,
                        'student' => [
                            'id' => $r->student->id,
                            'roll' => $r->student->roll,
                            'full_name' => $r->student->full_name,
                            'photo_url' => $r->student->photo_url ?? null,
                        ],
                    ];
                })->filter()->values()->toArray(),
            ],
        ];

        return response()->json($data);
    }

    /**
     * Details search endpoint: find evaluation(s) by filters and return records list
     */
    public function details(Request $request)
    {
        $q = LessonEvaluation::with(['records' => function ($q) {
                $q->whereHas('student', fn($s) => $s->where('status', 'active'))->with(['student' => fn($s) => $s->where('status', 'active')]);
            }])->orderByDesc('evaluation_date');
        if ($request->filled('class_id')) $q->where('class_id', $request->get('class_id'));
        if ($request->filled('section_id')) $q->where('section_id', $request->get('section_id'));
        if ($request->filled('subject_id')) $q->where('subject_id', $request->get('subject_id'));
        if ($request->filled('teacher_id')) $q->where('teacher_id', $request->get('teacher_id'));
        if ($request->filled('date')) $q->whereDate('evaluation_date', $request->get('date'));

        $evaluations = $q->get();
        $records = [];
        foreach ($evaluations as $ev) {
            foreach ($ev->records as $r) {
                if (! $r->student) continue;
                $records[] = [
                    'evaluation_id' => $ev->id,
                    'student_id' => $r->student_id,
                    'status' => $r->status,
                    'status_label' => $r->status_label,
                    'status_color' => $r->status_color,
                    'student' => [
                        'id' => $r->student->id,
                        'roll' => $r->student->roll,
                        'full_name' => $r->student->full_name,
                        'photo_url' => $r->student->photo_url ?? null,
                    ],
                ];
            }
        }

        return response()->json(['records' => $records]);
    }

    public function print(School $school, Request $request)
    {
        $lang = $request->get('lang', 'bn');
        $query = LessonEvaluation::with(['teacher', 'class', 'section', 'subject'])
            ->forSchool($school->id)
            ->orderByDesc('evaluation_date');

        // Date range filter
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if ($fromDate) {
            $query->whereDate('evaluation_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('evaluation_date', '<=', $toDate);
        }

        // Additional filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->get('section_id'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->get('subject_id'));
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->get('teacher_id'));
        }

        $evaluations = $query->get();

        return view('principal.lesson-evaluations.print', compact('school', 'evaluations', 'fromDate', 'toDate', 'request', 'lang'));
    }
}
