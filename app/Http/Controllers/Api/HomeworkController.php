<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Homework;
use App\Http\Resources\HomeworkResource;
use Illuminate\Support\Carbon;

class HomeworkController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Homework::query();

        // Scope by school (if provided via middleware)
        $schoolId = $request->attributes->get('current_school_id');
        if ($schoolId) {
            $query->forSchool($schoolId);
        }

        // Teacher sees only own created by default
        if ($user->isTeacher($schoolId)) {
            $teacher = $user->teacher; // relation
            if ($teacher) {
                $query->where('teacher_id', $teacher->id);
            }
        }

        // Filters
        if ($request->filled('date')) {
            $query->forDate($request->get('date'));
        }
        if ($request->filled('class_id')) {
            $query->forClass($request->get('class_id'), $request->get('section_id'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->get('subject_id'));
        }

        $query->orderByDesc('homework_date');
        $homeworks = $query->paginate(25);
        return HomeworkResource::collection($homeworks)->additional([
            'filters' => $request->only(['date','class_id','section_id','subject_id'])
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধু শিক্ষক হোমওয়ার্ক তৈরি করতে পারবেন'], 403);
        }

        $validated = $request->validate([
            'class_id' => ['required','integer'],
            'section_id' => ['nullable','integer'],
            'subject_id' => ['required','integer'],
            'homework_date' => ['required','date'],
            'submission_date' => ['nullable','date','after_or_equal:homework_date'],
            'title' => ['required','string','max:150'],
            'description' => ['nullable','string'],
            'attachment' => ['nullable','file','max:4096'],
        ]);

        $teacher = $user->teacher;
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('homework', 'public');
        }

        $homework = Homework::create([
            'school_id' => $schoolId,
            'class_id' => $validated['class_id'],
            'section_id' => $validated['section_id'] ?? null,
            'subject_id' => $validated['subject_id'],
            'teacher_id' => $teacher->id,
            'homework_date' => $validated['homework_date'],
            'submission_date' => $validated['submission_date'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'attachment' => $attachmentPath,
        ]);

        return (new HomeworkResource($homework))
            ->additional(['message' => 'হোমওয়ার্ক তৈরি সম্পন্ন']);
    }
}
