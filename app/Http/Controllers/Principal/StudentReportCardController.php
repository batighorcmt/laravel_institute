<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\LessonEvaluationRecord;
use App\Models\Exam;
use App\Models\Result;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentReportCardController extends Controller
{
    public function index($school)
    {
        return view('principal.students.report-card-index', ['school' => $school]);
    }

    public function show(Request $request, $school, Student $student)
    {
        $schoolId = is_object($school) ? $school->id : $school;
        $student->load(['currentEnrollment.class', 'currentEnrollment.section', 'currentEnrollment.group']);
        
        $currentYear = AcademicYear::forSchool($schoolId)->where('is_current', true)->first() 
            ?? AcademicYear::forSchool($schoolId)->orderBy('start_date', 'desc')->first();

        if (!$currentYear) {
            return view('principal.students.report-card', [
                'school' => $school,
                'student' => $student,
                'error' => 'No active academic year found.'
            ]);
        }

        // Date Range Logic
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

        // 1. Attendance Summary
        $attendanceQuery = Attendance::where('student_id', $student->id);
        if ($startDate && $endDate) {
            $attendanceQuery->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
        }
        $attendanceData = $attendanceQuery->get();

        $attendanceSummary = [
            'total_working_days' => $attendanceData->count(),
            'present' => $attendanceData->whereIn('status', ['present', 'late', 'P', 'L'])->count(),
            'absent' => $attendanceData->whereIn('status', ['absent', 'A'])->count(),
            'late' => $attendanceData->whereIn('status', ['late', 'L'])->count(),
        ];
        
        // Month-wise attendance
        $monthlyAttendance = $attendanceData->groupBy(function($date) {
            return Carbon::parse($date->date)->format('F Y');
        })->map(function($month) {
            return [
                'total' => $month->count(),
                'present' => $month->whereIn('status', ['present', 'late', 'P', 'L'])->count(),
                'absent' => $month->whereIn('status', ['absent', 'A'])->count(),
            ];
        });

        // 2. Lesson Evaluation Summary
        $lessonRecordsQuery = LessonEvaluationRecord::where('student_id', $student->id)
            ->whereHas('lessonEvaluation', function($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereBetween('evaluation_date', [
                        $startDate->toDateString(),
                        $endDate->toDateString()
                    ]);
                }
            })
            ->with('lessonEvaluation.subject');
            
        $lessonRecords = $lessonRecordsQuery->get();

        $lessonSummary = $lessonRecords->groupBy('status')->map->count();
        $subjectWiseEvaluation = $lessonRecords->groupBy(function($record) {
            return $record->lessonEvaluation->subject->name ?? 'Unknown';
        })->map(function($group) {
            return [
                'completed' => $group->where('status', 'completed')->count(),
                'partial' => $group->where('status', 'partial')->count(),
                'not_done' => $group->where('status', 'not_done')->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'total' => $group->count(),
            ];
        });

        // 3. Completed Exams for the class
        $exams = Exam::forSchool($schoolId)
            ->where('class_id', $student->currentEnrollment?->class_id)
            ->where('academic_year_id', $currentYear->id)
            ->whereIn('status', ['completed', 'published'])
            ->orderBy('start_date', 'desc')
            ->get();

        return view('principal.students.report-card', [
            'school' => $school,
            'student' => $student,
            'currentYear' => $currentYear,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'attendanceSummary' => $attendanceSummary,
            'monthlyAttendance' => $monthlyAttendance,
            'lessonSummary' => $lessonSummary,
            'subjectWiseEvaluation' => $subjectWiseEvaluation,
            'exams' => $exams,
        ]);
    }

    public function data($school, Student $student)
    {
        // Minimal aggregated payload for the Vue report card component.
        $student->load(['currentEnrollment.class', 'currentEnrollment.section', 'marks.exam', 'marks.subject']);

        $marks = $student->marks->groupBy('exam_id')->map(function ($group) {
            return $group->map(function ($m) {
                return [
                    'subject' => $m->subject?->name,
                    'creative_marks' => (float) $m->creative_marks,
                    'mcq_marks' => (float) $m->mcq_marks,
                    'practical_marks' => (float) $m->practical_marks,
                    'total_marks' => (float) $m->total_marks,
                    'letter_grade' => $m->letter_grade,
                    'grade_point' => (float) $m->grade_point,
                    'remarks' => $m->remarks,
                ];
            });
        });

        $payload = [
            'student' => [
                'id' => $student->id,
                'name' => $student->student_name_bn ?? $student->student_name_en,
                'student_id' => $student->student_id,
            ],
            'enrollment' => [
                'class' => $student->currentEnrollment?->class?->name,
                'section' => $student->currentEnrollment?->section?->name,
                'roll_no' => $student->currentEnrollment?->roll_no,
            ],
            'marks' => $marks,
        ];

        return response()->json($payload);
    }
}
