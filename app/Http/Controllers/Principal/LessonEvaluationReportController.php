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
        $query = LessonEvaluation::with(['teacher', 'class', 'section', 'subject'])
            ->forSchool($school->id)
            ->orderByDesc('evaluation_date');

        // Date filter (YYYY-MM-DD)
        $filterDate = $request->get('date');
        if ($filterDate) {
            $query->whereDate('evaluation_date', $filterDate);
        }

        // Per-page control (default 10)
        $perPage = (int) $request->get('per_page', 10);
        if (!in_array($perPage, [10,25,50,100])) {
            $perPage = 10;
        }

        $evaluations = $query->paginate($perPage)->withQueryString();

        // Available dates for filter (distinct)
        $dates = LessonEvaluation::forSchool($school->id)
            ->orderByDesc('evaluation_date')
            ->pluck('evaluation_date')
            ->map(fn($d) => optional($d)->format('Y-m-d'))
            ->unique()
            ->values();

        return view('principal.lesson-evaluations.index', compact('school', 'evaluations', 'dates', 'filterDate', 'perPage'));
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
        $q = LessonEvaluation::with(['records' => function($q){ $q->whereHas('student', fn($s)=>$s->where('status','active'))->with(['student' => fn($s)=>$s->where('status','active')]); }])->orderByDesc('evaluation_date');
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
}
