<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Mark;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('principal.marks.show', compact('school', 'exam'));
    }

    public function entryForm(School $school, Exam $exam, ExamSubject $examSubject)
    {
        $examSubject->load('subject');

        // Get students for this exam's class
        $students = Student::forSchool($school->id)
            ->where('class_id', $exam->class_id)
            ->where('status', 'active')
            ->orderBy('student_id')
            ->get();

        // Get existing marks
        $marks = Mark::forExam($exam->id)
            ->where('exam_subject_id', $examSubject->id)
            ->get()
            ->keyBy('student_id');

        return view('principal.marks.entry-form', compact('school', 'exam', 'examSubject', 'students', 'marks'));
    }

    public function saveMark(Request $request, School $school, Exam $exam, ExamSubject $examSubject)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'creative_marks' => 'nullable|numeric|min:0|max:' . $examSubject->creative_full_mark,
            'mcq_marks' => 'nullable|numeric|min:0|max:' . $examSubject->mcq_full_mark,
            'practical_marks' => 'nullable|numeric|min:0|max:' . $examSubject->practical_full_mark,
            'is_absent' => 'nullable|boolean',
        ]);

        $isAbsent = $request->boolean('is_absent');

        // Calculate total marks
        $totalMarks = 0;
        if (!$isAbsent) {
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

        return response()->json(['success' => true, 'message' => 'নম্বর সফলভাবে সংরক্ষণ করা হয়েছে']);
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

        if (!$isPassed) {
            return [
                'letter_grade' => 'F',
                'grade_point' => 0.00,
                'pass_status' => 'fail',
            ];
        }

        // Calculate grade based on percentage
        $percentage = ($totalMarks / $examSubject->total_full_mark) * 100;

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
        $examSubject->load('subject');

        $students = Student::forSchool($school->id)
            ->where('class_id', $exam->class_id)
            ->where('status', 'active')
            ->orderBy('student_id')
            ->get();

        return view('principal.marks.print-blank', compact('school', 'exam', 'examSubject', 'students'));
    }

    // Print filled mark entry form
    public function printFilled(School $school, Exam $exam, ExamSubject $examSubject)
    {
        $examSubject->load('subject');

        $students = Student::forSchool($school->id)
            ->where('class_id', $exam->class_id)
            ->where('status', 'active')
            ->orderBy('student_id')
            ->get();

        $marks = Mark::forExam($exam->id)
            ->where('exam_subject_id', $examSubject->id)
            ->get()
            ->keyBy('student_id');

        return view('principal.marks.print-filled', compact('school', 'exam', 'examSubject', 'students', 'marks'));
    }

    // Calculate results for all students
    public function calculateResults(School $school, Exam $exam)
    {
        DB::beginTransaction();

        try {
            $students = Student::forSchool($school->id)
                ->where('class_id', $exam->class_id)
                ->where('status', 'active')
                ->get();

            foreach ($students as $student) {
                $this->calculateStudentResult($exam, $student);
            }

            DB::commit();

            return redirect()
                ->route('principal.institute.results.marksheet', ['school' => $school, 'exam_id' => $exam->id])
                ->with('success', 'ফলাফল সফলভাবে হিসাব করা হয়েছে');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'ফলাফল হিসাবে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    private function calculateStudentResult($exam, $student)
    {
        $marks = Mark::forExam($exam->id)
            ->forStudent($student->id)
            ->get();

        $totalMarks = 0;
        $totalPossibleMarks = 0;
        $failedCount = 0;
        $absentCount = 0;
        $gradePoints = [];

        foreach ($marks as $mark) {
            if ($mark->is_absent) {
                $absentCount++;
                continue;
            }

            if ($mark->pass_status === 'fail') {
                $failedCount++;
            }

            $totalMarks += $mark->total_marks ?? 0;
            $totalPossibleMarks += $mark->examSubject->total_full_mark;

            if ($mark->pass_status === 'pass') {
                $gradePoints[] = $mark->grade_point;
            }
        }

        // Calculate GPA
        $gpa = null;
        $letterGrade = null;
        $resultStatus = 'incomplete';

        if ($absentCount > 0) {
            $resultStatus = 'fail';
            $letterGrade = 'F';
            $gpa = 0.00;
        } elseif ($failedCount > 0) {
            $resultStatus = 'fail';
            $letterGrade = 'F';
            $gpa = 0.00;
        } elseif (count($gradePoints) > 0) {
            $gpa = array_sum($gradePoints) / count($gradePoints);
            $gpa = round($gpa, 2);
            $resultStatus = 'pass';

            // Determine letter grade based on GPA
            if ($gpa >= 5.00) {
                $letterGrade = 'A+';
            } elseif ($gpa >= 4.00) {
                $letterGrade = 'A';
            } elseif ($gpa >= 3.50) {
                $letterGrade = 'A-';
            } elseif ($gpa >= 3.00) {
                $letterGrade = 'B';
            } elseif ($gpa >= 2.00) {
                $letterGrade = 'C';
            } else {
                $letterGrade = 'D';
            }
        }

        $percentage = $totalPossibleMarks > 0 ? ($totalMarks / $totalPossibleMarks) * 100 : 0;

        // Save result
        \App\Models\Result::updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ],
            [
                'class_id' => $exam->class_id,
                'section_id' => $student->section_id ?? null,
                'total_marks' => $totalMarks,
                'total_possible_marks' => $totalPossibleMarks,
                'percentage' => round($percentage, 2),
                'gpa' => $gpa,
                'letter_grade' => $letterGrade,
                'result_status' => $resultStatus,
                'failed_subjects_count' => $failedCount,
                'absent_subjects_count' => $absentCount,
            ]
        );
    }
}
