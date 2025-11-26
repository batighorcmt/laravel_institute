<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LessonEvaluation;
use App\Models\LessonEvaluationRecord;
use App\Models\RoutineEntry;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\Teacher as TeacherModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LessonEvaluationController extends Controller
{
    public function index(School $school)
    {
        $user = Auth::user();
        $teacher = TeacherModel::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        $today = Carbon::today();
        $dayName = strtolower($today->format('l')); // monday, tuesday, etc.

        // Get today's routine entries for this teacher
        $routineEntries = RoutineEntry::with(['class', 'section', 'subject'])
            ->where('school_id', $school->id)
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $dayName)
            ->orderBy('period_number')
            ->get();

        // Check which evaluations already exist
        $evaluatedIds = LessonEvaluation::forSchool($school->id)
            ->forTeacher($teacher->id)
            ->forDate($today)
            ->pluck('routine_entry_id')
            ->toArray();

        return view('teacher.lesson-evaluation.index', compact('school', 'teacher', 'routineEntries', 'evaluatedIds', 'today'));
    }

    public function create(School $school, Request $request)
    {
        $user = Auth::user();
        $teacher = TeacherModel::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        $routineEntryId = $request->get('routine_entry');
        $routineEntry = null;

        if ($routineEntryId) {
            $routineEntry = RoutineEntry::with(['class', 'section', 'subject'])
                ->where('id', $routineEntryId)
                ->where('school_id', $school->id)
                ->where('teacher_id', $teacher->id)
                ->firstOrFail();
        }

        // Check if already evaluated today - if yes, load for editing
        $today = Carbon::today();
        $lessonEvaluation = null;
        $existingRecords = collect();
        
        if ($routineEntry) {
            $lessonEvaluation = LessonEvaluation::with(['records'])
                ->forSchool($school->id)
                ->forTeacher($teacher->id)
                ->forDate($today)
                ->where('routine_entry_id', $routineEntry->id)
                ->first();
            
            if ($lessonEvaluation) {
                // Load existing records for editing
                $existingRecords = $lessonEvaluation->records->keyBy('student_id');
            }
        }

        // Get students enrolled in this class and section
        $students = collect();
        if ($routineEntry) {
            // Get students who have this subject assigned (or all if no subject assignment system)
            $query = StudentEnrollment::with('student')
                ->where('school_id', $school->id)
                ->where('class_id', $routineEntry->class_id)
                ->where('section_id', $routineEntry->section_id)
                ->where('status', 'active');
            
            // Only filter by subject if there are any subject assignments
            $hasSubjectAssignments = \DB::table('student_subjects')
                ->whereIn('student_enrollment_id', function($q) use ($school, $routineEntry) {
                    $q->select('id')
                      ->from('student_enrollments')
                      ->where('school_id', $school->id)
                      ->where('class_id', $routineEntry->class_id)
                      ->where('section_id', $routineEntry->section_id);
                })
                ->exists();
            
            if ($hasSubjectAssignments) {
                $query->whereHas('subjects', function($q) use ($routineEntry) {
                    $q->where('subject_id', $routineEntry->subject_id)
                      ->where('status', 'active');
                });
            }
            
            $students = $query->orderBy('roll_no')->get();
        }

        return view('teacher.lesson-evaluation.create', compact('school', 'teacher', 'routineEntry', 'students', 'lessonEvaluation', 'existingRecords'));
    }

    public function store(School $school, Request $request)
    {
        $user = Auth::user();
        $teacher = TeacherModel::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        $validated = $request->validate([
            'routine_entry_id' => 'nullable|exists:routine_entries,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'evaluation_date' => 'required|date',
            'evaluation_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'statuses' => 'required|array',
            'statuses.*' => 'required|in:completed,partial,not_done,absent',
        ]);

        try {
            DB::beginTransaction();

            // Check if evaluation already exists for today
            $evaluation = LessonEvaluation::forSchool($school->id)
                ->forTeacher($teacher->id)
                ->forDate($validated['evaluation_date'])
                ->where('routine_entry_id', $validated['routine_entry_id'])
                ->first();

            if ($evaluation) {
                // Update existing evaluation
                $evaluation->update([
                    'evaluation_time' => $validated['evaluation_time'] ?? now()->format('H:i'),
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'completed',
                ]);

                // Delete old records and create new ones
                $evaluation->records()->delete();
            } else {
                // Create new lesson evaluation
                $evaluation = LessonEvaluation::create([
                    'school_id' => $school->id,
                    'teacher_id' => $teacher->id,
                    'class_id' => $validated['class_id'],
                    'section_id' => $validated['section_id'],
                    'subject_id' => $validated['subject_id'],
                    'routine_entry_id' => $validated['routine_entry_id'] ?? null,
                    'evaluation_date' => $validated['evaluation_date'],
                    'evaluation_time' => $validated['evaluation_time'] ?? now()->format('H:i'),
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'completed',
                ]);
            }

            // Create evaluation records for each student
            foreach ($validated['student_ids'] as $index => $studentId) {
                LessonEvaluationRecord::create([
                    'lesson_evaluation_id' => $evaluation->id,
                    'student_id' => $studentId,
                    'status' => $validated['statuses'][$index],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('teacher.institute.lesson-evaluation.index', $school)
                ->with('success', $evaluation->wasRecentlyCreated ? 'লেসন মূল্যায়ন সফলভাবে সংরক্ষণ করা হয়েছে' : 'লেসন মূল্যায়ন সফলভাবে আপডেট করা হয়েছে');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'মূল্যায়ন সংরক্ষণে ত্রুটি হয়েছে: ' . $e->getMessage());
        }
    }

    public function show(School $school, LessonEvaluation $lessonEvaluation)
    {
        $user = Auth::user();
        $teacher = TeacherModel::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        // Ensure this evaluation belongs to this teacher
        if ($lessonEvaluation->teacher_id !== $teacher->id) {
            abort(403, 'Unauthorized access');
        }

        $lessonEvaluation->load([
            'class',
            'section',
            'subject',
            'routineEntry',
            'records.student'
        ]);

        $stats = $lessonEvaluation->getCompletionStats();

        return view('teacher.lesson-evaluation.show', compact('school', 'teacher', 'lessonEvaluation', 'stats'));
    }
}
