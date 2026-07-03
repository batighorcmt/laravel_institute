<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\School;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\ExamResultSyncService;
use Illuminate\Http\Request;

class MarkEntryController extends Controller
{
    public function index(School $school)
    {
        $exams = Exam::forSchool($school->id)
            ->with(['class', 'academicYear'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('principal.marks.index', compact('school', 'exams'));
    }

    public function show(School $school, Exam $exam)
    {
        $exam->load(['examSubjects.subject', 'examSubjects.teacher']);

        // Prepare counts
        $subjectStats = [];
        foreach ($exam->examSubjects as $sub) {
            // Count total students having this subject
            $totalStudents = StudentEnrollment::where('school_id', $school->id)
                ->where('academic_year_id', $exam->academic_year_id)
                ->where('class_id', $exam->class_id)
                ->where('status', 'active')
                ->whereHas('student', function ($query) {
                    $query->where('status', 'active');
                })
                ->whereHas('subjects', function ($query) use ($sub) {
                    $query->where('subject_id', $sub->subject_id);
                })
                ->when(!empty($exam->section_ids), function ($query) use ($exam) {
                    $query->whereIn('section_id', $exam->section_ids);
                })
                ->when(!empty($exam->group_ids), function ($query) use ($exam) {
                    $query->whereIn('group_id', $exam->group_ids);
                })
                ->count();

            // Count entered marks
            $enteredMarks = Mark::where('exam_id', $exam->id)
                ->where('exam_subject_id', $sub->id)
                ->count();

            $subjectStats[$sub->id] = [
                'total' => $totalStudents,
                'entered' => $enteredMarks,
            ];
        }

        return view('principal.marks.show', compact('school', 'exam', 'subjectStats'));
    }

    public function entryForm(School $school, Exam $exam, ExamSubject $examSubject)
    {
        $examSubject->load('subject');

        // Get enrollments for students who have selected this subject
        $enrollments = StudentEnrollment::where('school_id', $school->id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('class_id', $exam->class_id)
            ->where('status', 'active')
            ->whereHas('student', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('subjects', function ($query) use ($examSubject) {
                $query->where('subject_id', $examSubject->subject_id);
            })
            ->with(['student', 'subjects' => function ($query) use ($examSubject) {
                $query->where('subject_id', $examSubject->subject_id);
            }])
            ->when(!empty($exam->section_ids), function ($query) use ($exam) {
                $query->whereIn('section_id', $exam->section_ids);
            })
            ->when(!empty($exam->group_ids), function ($query) use ($exam) {
                $query->whereIn('group_id', $exam->group_ids);
            })
            ->orderBy('roll_no')
            ->get();

        // Get existing marks
        $marks = Mark::forExam($exam->id)
            ->where('exam_subject_id', $examSubject->id)
            ->get()
            ->keyBy('student_id');

        return view('principal.marks.entry-form', compact('school', 'exam', 'examSubject', 'enrollments', 'marks'));
    }

    public function saveMark(Request $request, School $school, Exam $exam, ExamSubject $examSubject)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'creative_marks' => 'nullable|numeric|min:0|max:'.$examSubject->creative_full_mark,
            'mcq_marks' => 'nullable|numeric|min:0|max:'.$examSubject->mcq_full_mark,
            'practical_marks' => 'nullable|numeric|min:0|max:'.$examSubject->practical_full_mark,
            'is_absent' => 'nullable|boolean',
        ]);

        $isAbsent = $request->boolean('is_absent');

        // Calculate total marks
        $totalMarks = 0;
        if (! $isAbsent) {
            $totalMarks = ($validated['creative_marks'] ?? 0) +
                          ($validated['mcq_marks'] ?? 0) +
                          ($validated['practical_marks'] ?? 0);
        }

        // Calculate grade and pass status
        $gradeInfo = $this->calculateGrade($totalMarks, $examSubject, $validated, $isAbsent);

        // Save or update mark
        Mark::updateOrCreate(
            [
                'exam_id' => $exam->id,
                'exam_subject_id' => $examSubject->id,
                'student_id' => $validated['student_id'],
                'subject_id' => $examSubject->subject_id,
            ],
            [
                'creative_marks' => $isAbsent ? null : ($validated['creative_marks'] ?? null),
                'mcq_marks' => $isAbsent ? null : ($validated['mcq_marks'] ?? null),
                'practical_marks' => $isAbsent ? null : ($validated['practical_marks'] ?? null),
                'total_marks' => $isAbsent ? null : $totalMarks,
                'letter_grade' => $gradeInfo['letter_grade'],
                'grade_point' => $gradeInfo['grade_point'],
                'pass_status' => $gradeInfo['pass_status'],
                'is_absent' => $isAbsent,
                'entered_by' => auth()->id(),
                'entered_at' => now(),
            ]
        );

        app(ExamResultSyncService::class)->syncAfterMarkSaved($school, $exam);

        return response()->json([
            'success' => true,
            'message' => 'নম্বর সফলভাবে সংরক্ষণ করা হয়েছে',
            'total_marks' => $isAbsent ? null : number_format($totalMarks, Setting::getDecimalPosition($school->id), '.', ''),
            'letter_grade' => $gradeInfo['letter_grade'],
        ]);
    }

    private function calculateGrade($totalMarks, $examSubject, $marks, $isAbsent)
    {
        if ($isAbsent) {
            return [
                'letter_grade' => 'F',
                'grade_point' => 0.00,
                'pass_status' => 'absent',
            ];
        }

        // Check pass status based on pass_type
        $isPassed = false;

        if ($examSubject->pass_type === 'each') {
            // Must pass in each part
            $creativePass = ($marks['creative_marks'] ?? 0) >= $examSubject->creative_pass_mark;
            $mcqPass = ($marks['mcq_marks'] ?? 0) >= $examSubject->mcq_pass_mark;
            $practicalPass = ($marks['practical_marks'] ?? 0) >= $examSubject->practical_pass_mark;

            $isPassed = $creativePass && $mcqPass && $practicalPass;
        } else {
            // Combined pass
            $isPassed = $totalMarks >= $examSubject->total_pass_mark;
        }

        if (! $isPassed) {
            return [
                'letter_grade' => 'F',
                'grade_point' => 0.00,
                'pass_status' => 'fail',
            ];
        }

        // Calculate grade based on percentage
        $percentage = ($examSubject->total_full_mark > 0) ? ($totalMarks / $examSubject->total_full_mark) * 100 : 0;

        if ($percentage >= 80) {
            return ['letter_grade' => 'A+', 'grade_point' => 5.00, 'pass_status' => 'pass'];
        } elseif ($percentage >= 70) {
            return ['letter_grade' => 'A', 'grade_point' => 4.00, 'pass_status' => 'pass'];
        } elseif ($percentage >= 60) {
            return ['letter_grade' => 'A-', 'grade_point' => 3.50, 'pass_status' => 'pass'];
        } elseif ($percentage >= 50) {
            return ['letter_grade' => 'B', 'grade_point' => 3.00, 'pass_status' => 'pass'];
        } elseif ($percentage >= 40) {
            return ['letter_grade' => 'C', 'grade_point' => 2.00, 'pass_status' => 'pass'];
        } elseif ($percentage >= 33) {
            return ['letter_grade' => 'D', 'grade_point' => 1.00, 'pass_status' => 'pass'];
        } else {
            return ['letter_grade' => 'F', 'grade_point' => 0.00, 'pass_status' => 'fail'];
        }
    }

    // Print blank mark entry form
    public function printBlank(School $school, Exam $exam, ExamSubject $examSubject)
    {
        $examSubject->load(['subject', 'teacher.teacher']);
        $exam->load('class');

        // Get enrollments for students who have selected this subject
        $enrollments = StudentEnrollment::where('school_id', $school->id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('class_id', $exam->class_id)
            ->where('status', 'active')
            ->whereHas('student', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('subjects', function ($query) use ($examSubject) {
                $query->where('subject_id', $examSubject->subject_id);
            })
            ->with(['student', 'subjects' => function ($query) use ($examSubject) {
                $query->where('subject_id', $examSubject->subject_id);
            }])
            ->when(!empty($exam->section_ids), function ($query) use ($exam) {
                $query->whereIn('section_id', $exam->section_ids);
            })
            ->when(!empty($exam->group_ids), function ($query) use ($exam) {
                $query->whereIn('group_id', $exam->group_ids);
            })
            ->orderBy('roll_no')
            ->get();

        return view('principal.marks.print-blank', compact('school', 'exam', 'examSubject', 'enrollments'));
    }

    // Print filled mark entry form
    public function printFilled(School $school, Exam $exam, ExamSubject $examSubject)
    {
        $examSubject->load(['subject', 'teacher.teacher']);
        $exam->load('class');

        // Get students who have marks entered for this subject
        $studentIdsWithMarks = Mark::forExam($exam->id)
            ->where('exam_subject_id', $examSubject->id)
            ->pluck('student_id')
            ->unique();

        $students = Student::forSchool($school->id)
            ->join('student_enrollments', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.academic_year_id', $exam->academic_year_id)
            ->where('student_enrollments.class_id', $exam->class_id)
            ->whereIn('students.id', $studentIdsWithMarks)
            ->where('students.status', 'active')
            ->when(!empty($exam->section_ids), function ($query) use ($exam) {
                $query->whereIn('student_enrollments.section_id', $exam->section_ids);
            })
            ->when(!empty($exam->group_ids), function ($query) use ($exam) {
                $query->whereIn('student_enrollments.group_id', $exam->group_ids);
            })
            ->orderBy('student_enrollments.roll_no')
            ->select('students.*')
            ->with(['enrollments' => function ($query) use ($exam) {
                $query->where('academic_year_id', $exam->academic_year_id)
                    ->where('class_id', $exam->class_id);
            }])
            ->get();

        $marks = Mark::forExam($exam->id)
            ->where('exam_subject_id', $examSubject->id)
            ->get()
            ->keyBy('student_id');

        return view('principal.marks.print-filled', compact('school', 'exam', 'examSubject', 'students', 'marks'));
    }

    /**
     * Portable print method for mobile apps (Internal signatures)
     */
    public function printPortable(Request $request, Exam $exam, ExamSubject $examSubject, $type)
    {
        $school = School::findOrFail($exam->school_id);

        if ($type === 'print-blank') {
            return $this->printBlank($school, $exam, $examSubject);
        } elseif ($type === 'print-filled') {
            return $this->printFilled($school, $exam, $examSubject);
        }

        abort(404);
    }
}
