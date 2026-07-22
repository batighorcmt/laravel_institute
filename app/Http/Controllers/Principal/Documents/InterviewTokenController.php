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
            'section_ids'      => 'nullable|string',
        ]);

        $academicYearId = $request->academic_year_id;
        $classId        = $request->class_id;
        $sectionIds     = $request->section_ids ? array_values(array_filter(explode(',', $request->section_ids))) : [];

        $query = StudentEnrollment::select(
            'student_enrollments.student_id',
            'student_enrollments.section_id',
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

        if (!empty($sectionIds)) {
            $query->whereIn('student_enrollments.section_id', $sectionIds);
            // Keep results grouped section-by-section in the exact order sections were selected,
            // so the first section's students are fully listed before the next section begins.
            $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
            $query->orderByRaw("FIELD(student_enrollments.section_id, {$placeholders})", $sectionIds);
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
            ->get();

        // Preserve the order the student IDs arrived in (section-grouped, roll-ordered from the
        // list page) instead of re-sorting by roll_no alone, which would interleave sections
        // whose roll numbers overlap (e.g. both section ক and খ having roll 1, 2, 3...).
        $orderIndex = array_flip($studentIds);
        $enrollments = $enrollments->sortBy(function ($enrollment) use ($orderIndex) {
            return $orderIndex[(string) $enrollment->student_id] ?? PHP_INT_MAX;
        })->values();

        return view('principal.documents.interview_token.print', compact('school', 'academicYear', 'class', 'enrollments'));
    }
}
