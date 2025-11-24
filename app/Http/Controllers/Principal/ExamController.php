<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(School $school)
    {
        $exams = Exam::with(['academicYear', 'class'])
            ->forSchool($school->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('principal.exams.index', compact('school', 'exams'));
    }

    public function create(School $school)
    {
        $academicYears = AcademicYear::forSchool($school->id)->get();
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();

        return view('principal.exams.create', compact('school', 'academicYears', 'classes'));
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'description' => 'nullable|string',
        ]);

        $validated['school_id'] = $school->id;

        // Check for duplicate exam
        $existingExam = Exam::where('school_id', $school->id)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('class_id', $validated['class_id'])
            ->where('name', $validated['name'])
            ->first();

        if ($existingExam) {
            return redirect()
                ->route('principal.institute.exams.show', [$school, $existingExam])
                ->with('info', 'এই পরীক্ষা ইতিমধ্যে বিদ্যমান আছে');
        }

        $exam = Exam::create($validated);

        return redirect()
            ->route('principal.institute.exams.show', [$school, $exam])
            ->with('success', 'পরীক্ষা সফলভাবে তৈরি করা হয়েছে');
    }

    public function show(School $school, Exam $exam)
    {
        $exam->load(['academicYear', 'class', 'examSubjects.subject', 'examSubjects.teacher']);

        $subjects = Subject::forSchool($school->id)->get();
        $teachers = User::whereHas('schoolRoles', function ($query) use ($school) {
            $query->where('school_id', $school->id);
        })->get();

        return view('principal.exams.show', compact('school', 'exam', 'subjects', 'teachers'));
    }

    public function edit(School $school, Exam $exam)
    {
        $academicYears = AcademicYear::forSchool($school->id)->get();
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();

        return view('principal.exams.edit', compact('school', 'exam', 'academicYears', 'classes'));
    }

    public function update(Request $request, School $school, Exam $exam)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'description' => 'nullable|string',
        ]);

        $exam->update($validated);

        return redirect()
            ->route('principal.institute.exams.show', [$school, $exam])
            ->with('success', 'পরীক্ষা সফলভাবে আপডেট করা হয়েছে');
    }

    public function destroy(School $school, Exam $exam)
    {
        $exam->delete();

        return redirect()
            ->route('principal.institute.exams.index', $school)
            ->with('success', 'পরীক্ষা সফলভাবে মুছে ফেলা হয়েছে');
    }

    // Add Subject to Exam
    public function addSubject(Request $request, School $school, Exam $exam)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'creative_full_mark' => 'required|integer|min:0',
            'creative_pass_mark' => 'required|integer|min:0',
            'mcq_full_mark' => 'required|integer|min:0',
            'mcq_pass_mark' => 'required|integer|min:0',
            'practical_full_mark' => 'required|integer|min:0',
            'practical_pass_mark' => 'required|integer|min:0',
            'pass_type' => 'required|in:each,combined',
            'exam_date' => 'nullable|date',
            'exam_start_time' => 'nullable',
            'exam_end_time' => 'nullable',
            'mark_entry_deadline' => 'nullable|date',
            'display_order' => 'nullable|integer',
        ]);

        $validated['exam_id'] = $exam->id;
        $validated['total_full_mark'] = $validated['creative_full_mark'] + $validated['mcq_full_mark'] + $validated['practical_full_mark'];
        $validated['total_pass_mark'] = $validated['creative_pass_mark'] + $validated['mcq_pass_mark'] + $validated['practical_pass_mark'];

        ExamSubject::create($validated);

        return redirect()
            ->route('principal.institute.exams.show', [$school, $exam])
            ->with('success', 'বিষয় সফলভাবে যুক্ত করা হয়েছে');
    }

    // Update Subject
    public function updateSubject(Request $request, School $school, Exam $exam, ExamSubject $examSubject)
    {
        $validated = $request->validate([
            'teacher_id' => 'nullable|exists:users,id',
            'creative_full_mark' => 'required|integer|min:0',
            'creative_pass_mark' => 'required|integer|min:0',
            'mcq_full_mark' => 'required|integer|min:0',
            'mcq_pass_mark' => 'required|integer|min:0',
            'practical_full_mark' => 'required|integer|min:0',
            'practical_pass_mark' => 'required|integer|min:0',
            'pass_type' => 'required|in:each,combined',
            'exam_date' => 'nullable|date',
            'exam_start_time' => 'nullable',
            'exam_end_time' => 'nullable',
            'mark_entry_deadline' => 'nullable|date',
            'display_order' => 'nullable|integer',
        ]);

        $validated['total_full_mark'] = $validated['creative_full_mark'] + $validated['mcq_full_mark'] + $validated['practical_full_mark'];
        $validated['total_pass_mark'] = $validated['creative_pass_mark'] + $validated['mcq_pass_mark'] + $validated['practical_pass_mark'];

        $examSubject->update($validated);

        return redirect()
            ->route('principal.institute.exams.show', [$school, $exam])
            ->with('success', 'বিষয় সফলভাবে আপডেট করা হয়েছে');
    }

    // Remove Subject
    public function removeSubject(School $school, Exam $exam, ExamSubject $examSubject)
    {
        $examSubject->delete();

        return redirect()
            ->route('principal.institute.exams.show', [$school, $exam])
            ->with('success', 'বিষয় সফলভাবে মুছে ফেলা হয়েছে');
    }
}
