<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LessonEvaluation;
use App\Models\LessonEvaluationRecord;
use App\Http\Resources\LessonEvaluationResource;
use Illuminate\Support\Carbon;
use App\Models\RoutineEntry;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

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
            'routine_entry_id' => ['nullable','integer','exists:routine_entries,id'],
            'class_id' => ['required','integer'],
            'section_id' => ['nullable','integer'],
            'subject_id' => ['required','integer'],
            'evaluation_date' => ['required','date'],
            'evaluation_time' => ['nullable','date_format:H:i'],
            'notes' => ['nullable','string'],
            // Per-student evaluation payload
            'student_ids' => ['required','array'],
            'student_ids.*' => ['integer','exists:students,id'],
            'statuses' => ['required','array'],
            'statuses.*' => ['required','string','in:completed,partial,not_done,absent'],
        ]);

        $teacher = $user->teacher;
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }

        // Today-only enforcement
        $today = Carbon::today()->toDateString();
        if ($validated['evaluation_date'] !== $today) {
            return response()->json(['message' => 'শুধু আজকের তারিখে মূল্যায়ন রেকর্ড করা যাবে'], 422);
        }

        try {
            DB::beginTransaction();

            // Find existing evaluation for this teacher/date/routine
            $evaluation = LessonEvaluation::forSchool($schoolId)
                ->forTeacher($teacher->id)
                ->forDate($validated['evaluation_date'])
                ->when(isset($validated['routine_entry_id']), function($q) use ($validated) {
                    $q->where('routine_entry_id', $validated['routine_entry_id']);
                })
                ->first();

            if ($evaluation) {
                $evaluation->update([
                    'evaluation_time' => $validated['evaluation_time'] ? Carbon::parse($validated['evaluation_time']) : now(),
                    'notes' => $validated['notes'] ?? null,
                    'class_id' => $validated['class_id'],
                    'section_id' => $validated['section_id'] ?? null,
                    'subject_id' => $validated['subject_id'],
                    'status' => 'completed',
                ]);
                // Replace existing records
                $evaluation->records()->delete();
            } else {
                $evaluation = LessonEvaluation::create([
                    'school_id' => $schoolId,
                    'teacher_id' => $teacher->id,
                    'class_id' => $validated['class_id'],
                    'section_id' => $validated['section_id'] ?? null,
                    'subject_id' => $validated['subject_id'],
                    'routine_entry_id' => $validated['routine_entry_id'] ?? null,
                    'evaluation_date' => $validated['evaluation_date'],
                    'evaluation_time' => $validated['evaluation_time'] ? Carbon::parse($validated['evaluation_time']) : now(),
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'completed',
                ]);
            }

            // Create records
            foreach ($validated['student_ids'] as $i => $studentId) {
                LessonEvaluationRecord::create([
                    'lesson_evaluation_id' => $evaluation->id,
                    'student_id' => (int)$studentId,
                    'status' => $validated['statuses'][$i] ?? 'not_done',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'লেসন ইভ্যালুয়েশন সংরক্ষণ হয়েছে',
                'evaluation_id' => $evaluation->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'সংরক্ষণে ত্রুটি: '.$e->getMessage()], 422);
        }
    }

    // Returns today's routine entries for the logged-in teacher with evaluated flags
    public function todayRoutine(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক'], 403);
        }

        $teacher = \App\Models\Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }
        $teacherId = $teacher->id;
        $today = Carbon::today();
        $dayName = strtolower($today->format('l'));

        $entries = RoutineEntry::with(['class','section','subject'])
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayName)
            ->orderBy('period_number')
            ->get();

        $evaluated = LessonEvaluation::forSchool($schoolId)
            ->forTeacher($teacherId)
            ->forDate($today)
            ->pluck('routine_entry_id')
            ->toArray();

        $data = $entries->map(function($e) use ($evaluated) {
            return [
                'routine_entry_id' => $e->id,
                'period_number' => $e->period_number,
                'class_name' => $e->class?->name,
                'section_name' => $e->section?->name,
                'subject_name' => $e->subject?->name,
                'evaluated' => in_array($e->id, $evaluated),
                'class_id' => $e->class_id,
                'section_id' => $e->section_id,
                'subject_id' => $e->subject_id,
            ];
        })->values();

        return response()->json([
            'date' => $today->toDateString(),
            'items' => $data,
        ]);
    }

    // Returns student list and existing statuses for a routine entry for today
    public function form(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক'], 403);
        }
        $teacher = \App\Models\Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }
        $teacherId = $teacher->id;
        $routineEntryId = (int)$request->query('routine_entry_id');
        if (! $routineEntryId) return response()->json(['message' => 'রুটিন নির্বাচন করুন'], 422);

        $entry = RoutineEntry::with(['class','section','subject'])
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->findOrFail($routineEntryId);

        $today = Carbon::today()->toDateString();
        $evaluation = LessonEvaluation::forSchool($schoolId)
            ->forTeacher($teacherId)
            ->forDate($today)
            ->where('routine_entry_id', $entry->id)
            ->with('records')
            ->first();
        $existing = $evaluation ? $evaluation->records->pluck('status','student_id') : collect();

        // Build enrollment query (optionally filter by subject assignments when exists)
        $query = StudentEnrollment::with('student')
            ->where('school_id', $schoolId)
            ->where('class_id', $entry->class_id)
            ->where('section_id', $entry->section_id)
            ->where('status', 'active');

        $hasSubjectAssignments = DB::table('student_subjects')
            ->whereIn('student_enrollment_id', function($q) use ($schoolId, $entry) {
                $q->select('id')->from('student_enrollments')
                    ->where('school_id', $schoolId)
                    ->where('class_id', $entry->class_id)
                    ->where('section_id', $entry->section_id);
            })->exists();
        if ($hasSubjectAssignments) {
            $query->whereHas('subjects', function($q) use ($entry) {
                $q->where('subject_id', $entry->subject_id)->where('status','active');
            });
        }
        $students = $query->orderBy('roll_no')->get()->map(function($en) use ($existing) {
            $st = $en->student;
            return [
                'id' => $st?->id,
                'name' => $st?->full_name,
                'roll' => $en->roll_no,
                'status' => $existing[$st?->id] ?? null,
            ];
        })->values();

        return response()->json([
            'date' => $today,
            'routine_entry' => [
                'id' => $entry->id,
                'class_id' => $entry->class_id,
                'section_id' => $entry->section_id,
                'subject_id' => $entry->subject_id,
                'class_name' => $entry->class?->name,
                'section_name' => $entry->section?->name,
                'subject_name' => $entry->subject?->name,
                'period_number' => $entry->period_number,
            ],
            'students' => $students,
            'allowed_statuses' => ['completed','partial','not_done','absent'],
        ]);
    }
}
