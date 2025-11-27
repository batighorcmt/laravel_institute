<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LessonEvaluation;
use App\Http\Resources\LessonEvaluationResource;
use Illuminate\Support\Carbon;

class LessonEvaluationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LessonEvaluation::query();
        $schoolId = $request->attributes->get('current_school_id');
        if ($schoolId) {
            $query->forSchool($schoolId);
        }
        // Teacher scope
        if ($user->isTeacher($schoolId) && $user->teacher) {
            $query->forTeacher($user->teacher->id);
        }
        // Filters
        if ($request->filled('date')) {
            $query->forDate($request->get('date'));
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
        $query->orderByDesc('evaluation_date');
        $items = $query->paginate(25);
        return LessonEvaluationResource::collection($items)->additional([
            'filters' => $request->only(['date','class_id','section_id','subject_id'])
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধু শিক্ষক লেসন ইভ্যালুয়েশন তৈরি করতে পারবেন'], 403);
        }
        $validated = $request->validate([
            'class_id' => ['required','integer'],
            'section_id' => ['nullable','integer'],
            'subject_id' => ['required','integer'],
            'evaluation_date' => ['required','date'],
            'evaluation_time' => ['nullable','date_format:H:i'],
            'notes' => ['nullable','string'],
            'status' => ['nullable','string','in:draft,final'],
        ]);
        $teacher = $user->teacher;
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }
        $model = LessonEvaluation::create([
            'school_id' => $schoolId,
            'teacher_id' => $teacher->id,
            'class_id' => $validated['class_id'],
            'section_id' => $validated['section_id'] ?? null,
            'subject_id' => $validated['subject_id'],
            'evaluation_date' => $validated['evaluation_date'],
            'evaluation_time' => $validated['evaluation_time'] ? Carbon::parse($validated['evaluation_time']) : null,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'draft',
        ]);
        return (new LessonEvaluationResource($model))->additional(['message' => 'লেসন ইভ্যালুয়েশন সংরক্ষণ হয়েছে']);
    }
}
