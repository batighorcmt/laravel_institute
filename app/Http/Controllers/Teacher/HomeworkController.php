<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Homework;
use App\Models\RoutineEntry;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class HomeworkController extends Controller
{
    /**
     * Display list of homeworks with search
     */
    public function index(School $school, Request $request)
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $subjectId = $request->query('subject_id');

        // Get teacher's classes/sections from routine
        $teacherClasses = RoutineEntry::where('teacher_id', $teacher->id)
            ->where('school_id', $school->id)
            ->select('class_id', 'section_id', 'subject_id')
            ->distinct()
            ->get();

        // Build query
        $query = Homework::with(['schoolClass', 'section', 'subject'])
            ->where('school_id', $school->id)
            ->where('teacher_id', $teacher->id)
            ->whereDate('homework_date', $date);

        if ($classId) {
            $query->where('class_id', $classId);
        }
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        $homeworks = $query->orderBy('created_at', 'desc')->get();

        return view('teacher.homework.index', compact('school', 'teacher', 'homeworks', 'date', 'teacherClasses'));
    }

    /**
     * Show form to create homework
     */
    public function create(School $school, Request $request)
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        $routineEntryId = $request->query('routine_entry');
        $routineEntry = null;

        if ($routineEntryId) {
            $routineEntry = RoutineEntry::with(['schoolClass', 'section', 'subject'])
                ->where('id', $routineEntryId)
                ->where('teacher_id', $teacher->id)
                ->where('school_id', $school->id)
                ->firstOrFail();
        }

        // Get all routine entries for this teacher
        $routineEntries = RoutineEntry::with(['schoolClass', 'section', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->where('school_id', $school->id)
            ->get()
            ->unique(function ($item) {
                return $item->class_id . '-' . $item->section_id . '-' . $item->subject_id;
            });

        return view('teacher.homework.create', compact('school', 'teacher', 'routineEntry', 'routineEntries'));
    }

    /**
     * Store homework
     */
    public function store(School $school, Request $request)
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'homework_date' => 'required|date',
            'submission_date' => 'nullable|date|after_or_equal:homework_date',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $data = $request->only(['class_id', 'section_id', 'subject_id', 'homework_date', 'submission_date', 'title', 'description']);
        $data['school_id'] = $school->id;
        $data['teacher_id'] = $teacher->id;

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('homeworks', $filename, 'public');
            $data['attachment'] = $path;
        }

        Homework::create($data);

        return redirect()->route('teacher.institute.homework.index', $school)
            ->with('success', 'হোমওয়ার্ক সফলভাবে যুক্ত করা হয়েছে।');
    }

    /**
     * Show homework details
     */
    public function show(School $school, Homework $homework)
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        // Verify this homework belongs to this teacher
        if ($homework->teacher_id !== $teacher->id) {
            abort(403, 'Unauthorized');
        }

        $homework->load(['schoolClass', 'section', 'subject']);

        return view('teacher.homework.show', compact('school', 'teacher', 'homework'));
    }

    /**
     * Delete homework
     */
    public function destroy(School $school, Homework $homework)
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        // Verify this homework belongs to this teacher
        if ($homework->teacher_id !== $teacher->id) {
            abort(403, 'Unauthorized');
        }

        // Delete attachment if exists
        if ($homework->attachment && Storage::disk('public')->exists($homework->attachment)) {
            Storage::disk('public')->delete($homework->attachment);
        }

        $homework->delete();

        return redirect()->route('teacher.institute.homework.index', $school)
            ->with('success', 'হোমওয়ার্ক মুছে ফেলা হয়েছে।');
    }
}
