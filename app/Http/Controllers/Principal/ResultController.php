<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Result;
use App\Models\School;
use App\Models\Student;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    // Marksheet
    public function marksheet(Request $request, School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $exams = Exam::forSchool($school->id)->orderBy('created_at', 'desc')->get();

        $results = null;
        $exam = null;
        $class = null;

        if ($request->has('exam_id') && $request->has('class_id')) {
            $exam = Exam::find($request->exam_id);
            $class = SchoolClass::find($request->class_id);

            $results = Result::with(['student', 'exam'])
                ->forExam($request->exam_id)
                ->forClass($request->class_id)
                ->orderByMerit()
                ->paginate(50);
        }

        return view('principal.results.marksheet', compact('school', 'classes', 'exams', 'results', 'exam', 'class'));
    }

    // Print individual marksheet
    public function printMarksheet(School $school, Exam $exam, Student $student)
    {
        $result = Result::with(['student', 'exam'])
            ->forExam($exam->id)
            ->forStudent($student->id)
            ->first();

        $marks = \App\Models\Mark::with(['examSubject.subject'])
            ->forExam($exam->id)
            ->forStudent($student->id)
            ->get();

        return view('principal.results.print-marksheet', compact('school', 'exam', 'student', 'result', 'marks'));
    }

    // Merit List
    public function meritList(Request $request, School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $exams = Exam::forSchool($school->id)->orderBy('created_at', 'desc')->get();

        $results = null;
        $exam = null;
        $class = null;

        if ($request->has('exam_id') && $request->has('class_id')) {
            $exam = Exam::find($request->exam_id);
            $class = SchoolClass::find($request->class_id);

            $results = Result::with(['student'])
                ->forExam($request->exam_id)
                ->forClass($request->class_id)
                ->passed()
                ->orderByMerit()
                ->get();

            // Update merit positions
            $position = 1;
            foreach ($results as $result) {
                $result->update(['merit_position' => $position++]);
            }
        }

        return view('principal.results.merit-list', compact('school', 'classes', 'exams', 'results', 'exam', 'class'));
    }

    // Print Merit List
    public function printMeritList(School $school, Exam $exam, $classId)
    {
        $class = SchoolClass::find($classId);

        $results = Result::with(['student'])
            ->forExam($exam->id)
            ->forClass($classId)
            ->passed()
            ->orderByMerit()
            ->get();

        return view('principal.results.print-merit-list', compact('school', 'exam', 'class', 'results'));
    }

    // Tabulation Sheet
    public function tabulation(Request $request, School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $exams = Exam::forSchool($school->id)->orderBy('created_at', 'desc')->get();

        $students = null;
        $exam = null;
        $class = null;
        $examSubjects = null;

        if ($request->has('exam_id') && $request->has('class_id')) {
            $exam = Exam::with('examSubjects.subject')->find($request->exam_id);
            $class = SchoolClass::find($request->class_id);
            $examSubjects = $exam->examSubjects()->orderBy('display_order')->get();

            $students = Student::forSchool($school->id)
                ->where('class_id', $class->id)
                ->where('status', 'active')
                ->orderBy('student_id')
                ->get();

            // Load marks for each student
            foreach ($students as $student) {
                $student->marks = \App\Models\Mark::forExam($exam->id)
                    ->forStudent($student->id)
                    ->get()
                    ->keyBy('subject_id');

                $student->result = Result::forExam($exam->id)
                    ->forStudent($student->id)
                    ->first();
            }
        }

        return view('principal.results.tabulation', compact('school', 'classes', 'exams', 'students', 'exam', 'class', 'examSubjects'));
    }

    // Print Tabulation Sheet
    public function printTabulation(School $school, Exam $exam, $classId)
    {
        $class = SchoolClass::find($classId);
        $exam->load('examSubjects.subject');
        $examSubjects = $exam->examSubjects()->orderBy('display_order')->get();

        $students = Student::forSchool($school->id)
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->orderBy('student_id')
            ->get();

        foreach ($students as $student) {
            $student->marks = \App\Models\Mark::forExam($exam->id)
                ->forStudent($student->id)
                ->get()
                ->keyBy('subject_id');

            $student->result = Result::forExam($exam->id)
                ->forStudent($student->id)
                ->first();
        }

        return view('principal.results.print-tabulation', compact('school', 'exam', 'class', 'students', 'examSubjects'));
    }

    // Statistics
    public function statistics(Request $request, School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $exams = Exam::forSchool($school->id)->orderBy('created_at', 'desc')->get();

        $stats = null;
        $exam = null;
        $class = null;

        if ($request->has('exam_id') && $request->has('class_id')) {
            $exam = Exam::find($request->exam_id);
            $class = SchoolClass::find($request->class_id);

            $stats = $this->calculateStatistics($exam->id, $class->id);
        }

        return view('principal.results.statistics', compact('school', 'classes', 'exams', 'stats', 'exam', 'class'));
    }

    private function calculateStatistics($examId, $classId)
    {
        $results = Result::forExam($examId)->forClass($classId)->get();

        $totalStudents = $results->count();
        $passedStudents = $results->where('result_status', 'pass')->count();
        $failedStudents = $results->where('result_status', 'fail')->count();

        $passRate = $totalStudents > 0 ? round(($passedStudents / $totalStudents) * 100, 2) : 0;

        $gradeDistribution = [
            'A+' => $results->where('letter_grade', 'A+')->count(),
            'A' => $results->where('letter_grade', 'A')->count(),
            'A-' => $results->where('letter_grade', 'A-')->count(),
            'B' => $results->where('letter_grade', 'B')->count(),
            'C' => $results->where('letter_grade', 'C')->count(),
            'D' => $results->where('letter_grade', 'D')->count(),
            'F' => $results->where('letter_grade', 'F')->count(),
        ];

        $gpaStats = [
            'highest' => $results->max('gpa'),
            'lowest' => $results->where('result_status', 'pass')->min('gpa'),
            'average' => round($results->where('result_status', 'pass')->avg('gpa'), 2),
        ];

        return [
            'total_students' => $totalStudents,
            'passed_students' => $passedStudents,
            'failed_students' => $failedStudents,
            'pass_rate' => $passRate,
            'grade_distribution' => $gradeDistribution,
            'gpa_stats' => $gpaStats,
        ];
    }

    // Publish Results
    public function publishResults(School $school, Exam $exam)
    {
        Result::forExam($exam->id)->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return back()->with('success', 'ফলাফল সফলভাবে প্রকাশ করা হয়েছে');
    }

    // Unpublish Results
    public function unpublishResults(School $school, Exam $exam)
    {
        Result::forExam($exam->id)->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return back()->with('success', 'ফলাফল সফলভাবে আনপাবলিশ করা হয়েছে');
    }
}
