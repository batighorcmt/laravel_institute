<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\Exam;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ExamPrintController extends Controller
{
    private function prepareData(Request $request, School $school, Exam $exam)
    {
        $exam->load('class', 'publicExam');
        $className = $exam->class->bangla_name ?? $exam->class->name ?? '';

        // Subjects schedule
        $schedule = DB::table('exam_subjects')
            ->join('subjects', 'subjects.id', '=', 'exam_subjects.subject_id')
            ->where('exam_subjects.exam_id', $exam->id)
            ->select(
                'exam_subjects.subject_id',
                'subjects.name as subject_name',
                'subjects.bangla_name as subject_bangla_name',
                'subjects.code as subject_code',
                'exam_subjects.exam_date',
                'exam_subjects.exam_start_time as exam_time'
            )
            ->orderByRaw('exam_subjects.exam_date IS NULL ASC, exam_subjects.exam_date ASC')
            ->orderByRaw('exam_subjects.exam_start_time IS NULL ASC, exam_subjects.exam_start_time ASC')
            ->orderBy('subjects.code', 'ASC')
            ->orderBy('subjects.name', 'ASC')
            ->get();

        // fetch students via enrollments matching the exact exam criteria
        $academicYearId = $exam->academic_year_id;
        $classId = $exam->class_id;

        $query = Student::where('school_id', $school->id)
            ->where('status', 'active')
            ->with(['publicExams'])
            ->whereHas('enrollments', function($q) use ($academicYearId, $classId) {
                $q->where('academic_year_id', $academicYearId)
                  ->where('class_id', $classId)
                  ->where('status', 'active');
            })
            ->with(['enrollments' => function($q) use ($academicYearId, $classId) {
                $q->where('academic_year_id', $academicYearId)
                  ->where('class_id', $classId)
                  ->where('status', 'active')
                  ->with(['section', 'group']);
            }]);

        if ($request->has('section_id') && $request->section_id > 0) {
            $sectionId = $request->section_id;
            $query->whereHas('enrollments', function($q) use ($academicYearId, $classId, $sectionId) {
                $q->where('academic_year_id', $academicYearId)
                  ->where('class_id', $classId)
                  ->where('section_id', $sectionId)
                  ->where('status', 'active');
            });
        }
        
        if ($request->has('search_roll') && $request->search_roll !== '') {
            $searchRoll = $request->search_roll;
            $query->whereHas('enrollments', function($q) use ($academicYearId, $classId, $searchRoll) {
                $q->where('academic_year_id', $academicYearId)
                  ->where('class_id', $classId)
                  ->where('roll_no', $searchRoll)
                  ->where('status', 'active');
            });
        }

        $students = $query->get()->sortBy(function($student) {
            $enrollment = $student->enrollments->first();
            return $enrollment ? $enrollment->roll_no : 999999;
        })->values();

        // Assigned Subjects
        $assigned_by_student = [];
        if ($students->count() > 0) {
            $enrollments = $students->pluck('enrollments')->flatten();
            $enrollmentIds = $enrollments->pluck('id')->toArray();
            $enrollmentToStudent = $enrollments->pluck('student_id', 'id')->toArray();

            $studentSubjects = DB::table('student_subjects')
                ->whereIn('student_enrollment_id', $enrollmentIds)
                ->get();
            
            foreach ($studentSubjects as $ss) {
                $studentId = $enrollmentToStudent[$ss->student_enrollment_id] ?? null;
                if ($studentId) {
                    if(!isset($assigned_by_student[$studentId])) {
                        $assigned_by_student[$studentId] = [];
                    }
                    $assigned_by_student[$studentId][$ss->subject_id] = true;
                }
            }
        }

        return compact('school', 'exam', 'className', 'schedule', 'students', 'assigned_by_student');
    }

    public function admitV1(Request $request, School $school, Exam $exam)
    {
        return view('principal.exams.print.admit_v1', $this->prepareData($request, $school, $exam));
    }

    public function admitV2(Request $request, School $school, Exam $exam)
    {
        return view('principal.exams.print.admit_v2', $this->prepareData($request, $school, $exam));
    }

    public function admitV3(Request $request, School $school, Exam $exam)
    {
        return view('principal.exams.print.admit_v3', $this->prepareData($request, $school, $exam));
    }

    public function attendanceSheet(Request $request, School $school, Exam $exam)
    {
        return view('principal.exams.print.exam_attendance', $this->prepareData($request, $school, $exam));
    }
}
