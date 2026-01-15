<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
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
        $teachers = Teacher::forSchool($school->id)->active()->with('user')->get();

        return view('principal.exams.create', compact('school', 'academicYears', 'classes', 'teachers'));
    }

    // Fetch subjects for a class (AJAX endpoint)
    public function fetchSubjects(Request $request, School $school)
    {
        $classId = $request->get('class_id');
        if (!$classId) {
            return response()->json([]);
        }

        // Get subjects for this class via ClassSubject mapping
        $subjects = \App\Models\ClassSubject::where('school_id', $school->id)
            ->where('class_id', $classId)
            ->with('subject')
            ->get()
            ->pluck('subject')
            ->filter()
            ->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'subject_name' => $subject->name,
                    'has_creative' => $subject->has_creative ? 1 : 0,
                    'has_objective' => $subject->has_mcq ? 1 : 0,
                    'has_practical' => $subject->has_practical ? 1 : 0,
                ];
            })
            ->values();

        return response()->json($subjects);
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'exam_type' => 'nullable|in:Half Yearly,Final,Monthly',
            'total_subjects_without_fourth' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'description' => 'nullable|string',
            // Subjects array (optional - can create exam first, then add subjects)
            'subject_id' => 'nullable|array',
            'subject_id.*' => 'exists:subjects,id',
            'teacher_id' => 'nullable|array',
            'teacher_id.*' => 'nullable|exists:users,id',
            'creative_marks' => 'nullable|array',
            'creative_marks.*' => 'integer|min:0',
            'objective_marks' => 'nullable|array',
            'objective_marks.*' => 'integer|min:0',
            'practical_marks' => 'nullable|array',
            'practical_marks.*' => 'integer|min:0',
            'creative_pass' => 'nullable|array',
            'creative_pass.*' => 'integer|min:0',
            'objective_pass' => 'nullable|array',
            'objective_pass.*' => 'integer|min:0',
            'practical_pass' => 'nullable|array',
            'practical_pass.*' => 'integer|min:0',
            'pass_type' => 'nullable|array',
            'pass_type.*' => 'in:total,individual',
            'exam_date' => 'nullable|array',
            'exam_date.*' => 'nullable|date',
            'exam_time' => 'nullable|array',
            'exam_time.*' => 'nullable',
            'mark_entry_deadline' => 'nullable|array',
            'mark_entry_deadline.*' => 'nullable|date',
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

        \DB::beginTransaction();
        try {
            // Create exam
            $exam = Exam::create([
                'school_id' => $validated['school_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'class_id' => $validated['class_id'],
                'name' => $validated['name'],
                'name_bn' => $validated['name_bn'] ?? null,
                'exam_type' => $validated['exam_type'] ?? null,
                'total_subjects_without_fourth' => $validated['total_subjects_without_fourth'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
            ]);

            // Create exam subjects if provided
            if (!empty($validated['subject_id']) && is_array($validated['subject_id'])) {
                foreach ($validated['subject_id'] as $index => $subjectId) {
                    $creativeMarks = (int)($validated['creative_marks'][$index] ?? 0);
                    $objectiveMarks = (int)($validated['objective_marks'][$index] ?? 0);
                    $practicalMarks = (int)($validated['practical_marks'][$index] ?? 0);
                    $totalMarks = $creativeMarks + $objectiveMarks + $practicalMarks;

                    $creativePass = (int)($validated['creative_pass'][$index] ?? 0);
                    $objectivePass = (int)($validated['objective_pass'][$index] ?? 0);
                    $practicalPass = (int)($validated['practical_pass'][$index] ?? 0);
                    $totalPass = $creativePass + $objectivePass + $practicalPass;

                    $passType = $validated['pass_type'][$index] ?? 'total';
                    // Convert 'total'/'individual' to 'combined'/'each' for database
                    $passType = $passType === 'individual' ? 'each' : 'combined';

                    ExamSubject::create([
                        'exam_id' => $exam->id,
                        'subject_id' => $subjectId,
                        'teacher_id' => !empty($validated['teacher_id'][$index]) ? (int)$validated['teacher_id'][$index] : null,
                        'creative_full_mark' => $creativeMarks,
                        'creative_pass_mark' => $creativePass,
                        'mcq_full_mark' => $objectiveMarks,
                        'mcq_pass_mark' => $objectivePass,
                        'practical_full_mark' => $practicalMarks,
                        'practical_pass_mark' => $practicalPass,
                        'total_full_mark' => $totalMarks,
                        'total_pass_mark' => $totalPass,
                        'pass_type' => $passType,
                        'exam_date' => !empty($validated['exam_date'][$index]) ? $validated['exam_date'][$index] : null,
                        'exam_start_time' => !empty($validated['exam_time'][$index]) ? $validated['exam_time'][$index] : null,
                        'mark_entry_deadline' => !empty($validated['mark_entry_deadline'][$index]) ? $validated['mark_entry_deadline'][$index] : null,
                        'display_order' => $index + 1,
                    ]);
                }
            }

            \DB::commit();

            return redirect()
                ->route('principal.institute.exams.show', [$school, $exam])
                ->with('success', 'পরীক্ষা সফলভাবে তৈরি করা হয়েছে');
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'পরীক্ষা তৈরি করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
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

    // Bulk Update View
    public function bulkUpdateView(School $school, Exam $exam)
    {
        $exam->load(['examSubjects.subject', 'examSubjects.teacher']);
        $teachers = Teacher::forSchool($school->id)->active()->with('user')->get();

        return view('principal.exams.bulk-update', compact('school', 'exam', 'teachers'));
    }

    public function update(Request $request, School $school, Exam $exam)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'exam_type' => 'nullable|in:Half Yearly,Final,Monthly',
            'total_subjects_without_fourth' => 'nullable|integer|min:1',
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

    // Bulk Update Exam (like PHP update_exam.php)
    public function bulkUpdate(Request $request, School $school, Exam $exam)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'exam_type' => 'nullable|in:Half Yearly,Final,Monthly',
            'total_subjects_without_fourth' => 'nullable|integer|min:1',
            'exam_subject_id' => 'required|array',
            'exam_subject_id.*' => 'exists:exam_subjects,id',
            'subject_id' => 'required|array',
            'subject_id.*' => 'exists:subjects,id',
            'teacher_id' => 'nullable|array',
            'teacher_id.*' => 'nullable|exists:users,id',
            'creative_marks' => 'required|array',
            'creative_marks.*' => 'integer|min:0',
            'objective_marks' => 'required|array',
            'objective_marks.*' => 'integer|min:0',
            'practical_marks' => 'required|array',
            'practical_marks.*' => 'integer|min:0',
            'creative_pass' => 'required|array',
            'creative_pass.*' => 'integer|min:0',
            'objective_pass' => 'required|array',
            'objective_pass.*' => 'integer|min:0',
            'practical_pass' => 'required|array',
            'practical_pass.*' => 'integer|min:0',
            'pass_type' => 'required|array',
            'pass_type.*' => 'in:total,individual',
            'exam_date' => 'nullable|array',
            'exam_date.*' => 'nullable|date',
            'exam_time' => 'nullable|array',
            'exam_time.*' => 'nullable',
            'mark_entry_deadline' => 'nullable|array',
            'mark_entry_deadline.*' => 'nullable|date',
        ]);

        \DB::beginTransaction();
        try {
            // Update exam basic info
            $exam->update([
                'name' => $validated['name'],
                'exam_type' => $validated['exam_type'] ?? null,
                'total_subjects_without_fourth' => $validated['total_subjects_without_fourth'] ?? null,
            ]);

            // Update each exam subject
            foreach ($validated['exam_subject_id'] as $index => $examSubjectId) {
                $creativeMarks = (int)($validated['creative_marks'][$index] ?? 0);
                $objectiveMarks = (int)($validated['objective_marks'][$index] ?? 0);
                $practicalMarks = (int)($validated['practical_marks'][$index] ?? 0);
                $totalMarks = $creativeMarks + $objectiveMarks + $practicalMarks;

                $creativePass = (int)($validated['creative_pass'][$index] ?? 0);
                $objectivePass = (int)($validated['objective_pass'][$index] ?? 0);
                $practicalPass = (int)($validated['practical_pass'][$index] ?? 0);
                $totalPass = $creativePass + $objectivePass + $practicalPass;

                $passType = $validated['pass_type'][$index] ?? 'total';
                $passType = $passType === 'individual' ? 'each' : 'combined';

                $teacherId = !empty($validated['teacher_id'][$index]) ? (int)$validated['teacher_id'][$index] : null;

                ExamSubject::where('id', $examSubjectId)
                    ->where('exam_id', $exam->id)
                    ->update([
                        'subject_id' => (int)$validated['subject_id'][$index],
                        'teacher_id' => $teacherId,
                        'creative_full_mark' => $creativeMarks,
                        'creative_pass_mark' => $creativePass,
                        'mcq_full_mark' => $objectiveMarks,
                        'mcq_pass_mark' => $objectivePass,
                        'practical_full_mark' => $practicalMarks,
                        'practical_pass_mark' => $practicalPass,
                        'total_full_mark' => $totalMarks,
                        'total_pass_mark' => $totalPass,
                        'pass_type' => $passType,
                        'exam_date' => !empty($validated['exam_date'][$index]) ? $validated['exam_date'][$index] : null,
                        'exam_start_time' => !empty($validated['exam_time'][$index]) ? $validated['exam_time'][$index] : null,
                        'mark_entry_deadline' => !empty($validated['mark_entry_deadline'][$index]) ? $validated['mark_entry_deadline'][$index] : null,
                    ]);
            }

            \DB::commit();

            return redirect()
                ->route('principal.institute.exams.index', $school)
                ->with('success', 'পরীক্ষা সফলভাবে আপডেট করা হয়েছে');
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'পরীক্ষা আপডেট করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
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
