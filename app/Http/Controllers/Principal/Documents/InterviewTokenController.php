<?php

namespace App\Http\Controllers\Principal\Documents;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InterviewTokenController extends Controller
{
    protected function authorizePrincipal(School $school): void
    {
        /** @var \App\Models\User $u */ $u = Auth::user();
        abort_unless($u && ($u->isSuperAdmin() || $u->isPrincipal($school->id)), 403);
    }

    public function index(Request $request, School $school)
    {
        $this->authorizePrincipal($school);

        $academicYears = AcademicYear::forSchool($school->id)->orderBy('name', 'desc')->get();
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();

        return view('principal.documents.interview_token.index', compact('school', 'academicYears', 'classes'));
    }

    /**
     * AJAX endpoint to load active students for a class and year
     */
    public function loadStudents(Request $request, School $school)
    {
        $this->authorizePrincipal($school);

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id'         => 'required|exists:classes,id',
            'section_id'       => 'nullable',
        ]);

        $academicYearId = $request->academic_year_id;
        $classId        = $request->class_id;
        $sectionId      = $request->section_id;

        $query = StudentEnrollment::select(
            'student_enrollments.student_id',
            'student_enrollments.roll_no',
            'students.student_name_bn',
            'students.student_name_en',
            'students.student_id as student_code'
        )
        ->join('students', 'students.id', '=', 'student_enrollments.student_id')
        ->where('student_enrollments.school_id', $school->id)
        ->where('student_enrollments.academic_year_id', $academicYearId)
        ->where('student_enrollments.class_id', $classId)
        ->where('students.status', 'active');

        if ($sectionId) {
            $query->where('student_enrollments.section_id', $sectionId);
        }

        $enrollments = $query->orderBy('student_enrollments.roll_no')->get();

        $students = $enrollments->map(function ($enrollment) {
            return [
                'id' => $enrollment->student_id,
                'student_id' => $enrollment->student_code,
                'name' => $enrollment->student_name_bn ?: $enrollment->student_name_en,
                'roll_no' => $enrollment->roll_no,
            ];
        });

        return response()->json(['students' => $students]);
    }

    public function print(Request $request, School $school)
    {
        $this->authorizePrincipal($school);

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id'         => 'required|exists:classes,id',
            'student_ids'      => 'required|string', // Comma separated IDs
            'interview_date'   => 'nullable|date',
            'header_color'     => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'start_time'       => 'nullable|date_format:H:i',
            'interval_minutes' => 'nullable|integer|min:1',
        ]);

        $academicYearId = $request->academic_year_id;
        $classId        = $request->class_id;
        $studentIds     = explode(',', $request->student_ids);

        $academicYear = AcademicYear::findOrFail($academicYearId);
        $class        = SchoolClass::findOrFail($classId);

        $enrollments = StudentEnrollment::where('school_id', $school->id)
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->whereIn('student_id', $studentIds)
            ->with(['student', 'class', 'section'])
            ->get()
            ->sortBy('roll_no')
            ->values();

        return view('principal.documents.interview_token.print', compact('school', 'academicYear', 'class', 'enrollments'));
    }
}
