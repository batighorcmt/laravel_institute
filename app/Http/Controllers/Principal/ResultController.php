<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Result;
use App\Models\School;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Mark;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
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
        $academicYears = AcademicYear::forSchool($school->id)->orderBy('start_date', 'desc')->get();
        $sections = collect();
        // initially exams are empty; will be filtered by academic year when requested
        $exams = collect();

        $students = null;
        $exam = null;
        $class = null;
        $examSubjects = null;
        $classSubjects = collect();
        $results = collect();
        $marks = collect();

        if ($request->filled('exam_id') && $request->filled('class_id')) {
            $exam = Exam::with('examSubjects.subject')->find($request->exam_id);
            $class = SchoolClass::find($request->class_id);
            $examSubjects = $exam ? $exam->examSubjects()->orderBy('display_order')->get() : collect();
            $examSubjectIds = $examSubjects->pluck('subject_id')->toArray();
            $classSubjects = ClassSubject::where('class_id', $class->id)
                ->whereIn('subject_id', $examSubjectIds)
                ->orderBy('order_no')
                ->get();

            // Load sections for the selected class (for the section dropdown)
            $sections = Section::forSchool($school->id)->where('class_id', $class->id)->ordered()->get();

            // Results for the selected class/exam, optionally filtered by section
            $resultQuery = Result::with(['student'])
                ->forExam($exam->id)
                ->forClass($class->id)
                ->orderByMerit();

            if ($request->filled('section_id')) {
                $resultQuery->where('section_id', $request->section_id);
            }

            $results = $resultQuery->get();

            // Start with student ids from existing results
            $resultStudentIds = $results->pluck('student_id')->unique()->values()->all();

            // Include students who have marks for this exam (even if no Result row exists)
            $marksForExam = Mark::forExam($exam->id)->get();
            $studentIdsWithMarks = $marksForExam->pluck('student_id')->unique()->values()->all();

            // Include students who are enrolled in this class/section (entry form uses enrollments)
            $enrollmentQuery = \App\Models\StudentEnrollment::where('school_id', $school->id)->where('class_id', $class->id)->where('status','active');
            if ($request->filled('section_id')) {
                $enrollmentQuery->where('section_id', $request->section_id);
            }
            $enrolledStudentIds = $enrollmentQuery->pluck('student_id')->unique()->values()->all();

            // Union all student ids we want to show
            $allStudentIds = collect($resultStudentIds)
                ->merge($studentIdsWithMarks)
                ->merge($enrolledStudentIds)
                ->unique()
                ->values()
                ->all();

            // Fetch marks for all these students (keyed by student and exam_subject)
            $marks = !empty($allStudentIds) ? Mark::forExam($exam->id)->whereIn('student_id', $allStudentIds)->get() : collect();

            // Only include students who are active in students table
            $activeStudentIds = Student::whereIn('id', $allStudentIds)->where('status','active')->pluck('id')->unique()->values()->all();

            // Build a results collection that includes an item for each (active) student we want to display.
            // If a persistent Result exists use it; otherwise create a lightweight placeholder with student relation.
            $resultsCollection = collect();
            foreach ($activeStudentIds as $sid) {
                $existing = Result::forExam($exam->id)->forStudent($sid)->first();
                $stu = Student::with(['currentEnrollment.section','currentEnrollment.group'])->find($sid);
                if ($existing) {
                    // attach enrollment/section/group info for sorting/display
                    $existing->student = $stu;
                    $existing->group_name = optional($stu->currentEnrollment->group)->name ?? null;
                } else {
                    $fake = new Result();
                    $fake->student_id = $sid;
                    $fake->student = $stu;
                    $fake->total_marks = 0;
                    $fake->gpa = 0;
                    $fake->letter_grade = null;
                    $fake->result_status = 'not_computed';
                    $fake->group_name = optional($stu->currentEnrollment->group)->name ?? null;
                    $existing = $fake;
                }

                // determine optional subject code (if any) from student's enrollment subjects
                $optionalCode = null;
                $optionalId = null;
                if ($stu && $stu->currentEnrollment) {
                    $optionalSubject = \App\Models\StudentSubject::where('student_enrollment_id', $stu->currentEnrollment->id)
                        ->where('is_optional', true)
                        ->with(['subject'])
                        ->first();

                    if ($optionalSubject) {
                        $optionalCode = optional($optionalSubject->subject)->code ?? null;
                        $optionalId = optional($optionalSubject->subject)->id ?? null;
                    }
                }

                $existing->fourth_subject_code = $optionalCode;
                $existing->fourth_subject_id = $optionalId;
                $resultsCollection->push($existing);
            }

            // Now compute per-student totals, GPA and grade (excluding optional subject)
            foreach ($resultsCollection as $res) {
                $sid = $res->student_id;
                $studentMarksFor = $marks->where('student_id', $sid)->keyBy('exam_subject_id');

                $sumTotal = 0;
                $sumGpa = 0;
                $countSubjects = 0;
                $hasAny = false;
                $hasAbsent = false;

                foreach ($examSubjects as $exSub) {
                    // skip optional subject if present and matches
                    if (!empty($res->fourth_subject_id) && $exSub->subject_id == $res->fourth_subject_id) {
                        continue;
                    }

                    $mark = $studentMarksFor->get($exSub->id);
                    $gp = 0;
                    if ($mark) {
                        $hasAny = true;
                        if ($mark->is_absent) {
                            $hasAbsent = true;
                        }
                        $sumTotal += ($mark->total_marks ?? 0);
                        $gp = $mark->grade_point ?? 0;
                    }

                    $sumGpa += $gp;
                    $countSubjects++;
                }

                $computedTotal = $sumTotal;
                $computedGpa = $countSubjects > 0 ? round($sumGpa / $countSubjects, 2) : 0.00;

                // Determine letter grade based on GPA
                $computedLetter = null;
                if ($computedGpa <= 0) {
                    $computedLetter = 'F'; // Ensure F is shown instead of just failing the view
                } elseif ($computedGpa >= 5.00) {
                    $computedLetter = 'A+';
                } elseif ($computedGpa >= 4.00) {
                    $computedLetter = 'A';
                } elseif ($computedGpa >= 3.50) {
                    $computedLetter = 'A-';
                } elseif ($computedGpa >= 3.00) {
                    $computedLetter = 'B';
                } elseif ($computedGpa >= 2.00) {
                    $computedLetter = 'C';
                } elseif ($computedGpa >= 1.00) {
                    $computedLetter = 'D';
                } else {
                    $computedLetter = 'F';
                }

                $computedStatus = ($computedGpa <= 0) ? 'অকৃতকার্য' : ($hasAbsent ? 'অনুপস্থিত' : 'উত্তীর্ণ');

                $res->computed_total_marks = $computedTotal;
                $res->computed_gpa = $computedGpa;
                $res->computed_letter = $computedLetter;
                $res->computed_status = $computedStatus;
            }

            // Sort results by section name then by roll_no (via student's currentEnrollment)
            $results = $resultsCollection->sortBy(function($r){
                $section = optional(optional($r->student)->currentEnrollment)->section;
                $sectionName = $section ? $section->name : '';
                $roll = optional(optional($r->student)->currentEnrollment)->roll_no ?? 0;
                return $sectionName.'-'.str_pad($roll, 6, '0', STR_PAD_LEFT);
            })->values();

            // Keep a lightweight students collection for any other display needs
            $students = !empty($allStudentIds) ? Student::whereIn('id', $allStudentIds)->get() : collect();
        } else {
            // If academic_year_id is provided, load exams for that year for initial dropdown population
            if ($request->filled('academic_year_id')) {
                $exams = Exam::forSchool($school->id)->forAcademicYear($request->academic_year_id)->orderBy('created_at','desc')->get();
            }
        }

        return view('principal.results.tabulation', compact('school', 'classes', 'academicYears', 'sections', 'exams', 'students', 'exam', 'class', 'examSubjects', 'classSubjects', 'results', 'marks'));
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

    // AJAX: get exams for an academic year (used by tabulation cascading select)
    public function examsByYear(Request $request, School $school)
    {
        $yearId = $request->get('academic_year_id');
        if (! $yearId) {
            return response()->json([], 200);
        }

        $exams = Exam::forSchool($school->id)->forAcademicYear($yearId)->orderBy('created_at','desc')->get(['id','name']);
        return response()->json($exams);
    }

    // AJAX: get sections for a class (used by tabulation cascading select)
    public function sectionsByClass(Request $request, School $school)
    {
        $classId = $request->get('class_id');
        if (! $classId) {
            return response()->json([], 200);
        }

        $sections = Section::forSchool($school->id)->where('class_id', $classId)->ordered()->get(['id','name']);
        return response()->json($sections);
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
