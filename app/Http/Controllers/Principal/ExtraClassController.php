<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\ExtraClass;
use App\Models\ExtraClassEnrollment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExtraClassController extends Controller
{
    public function index(School $school)
    {
        $extraClasses = ExtraClass::where('school_id', $school->id)
            ->with(['schoolClass', 'section', 'subject', 'teacher', 'academicYear'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('principal.institute.extra-classes.index', compact('school', 'extraClasses'));
    }

    public function create(School $school)
    {
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();
        $sections = Section::where('school_id', $school->id)->orderBy('name')->get();
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
        $teachers = User::whereHas('schoolRoles', function($q) use ($school) {
            $q->where('school_id', $school->id)
              ->whereHas('role', function($q2) {
                  $q2->where('name', 'Teacher');
              });
        })->orderBy('name')->get();

        return view('principal.institute.extra-classes.create', compact('school', 'currentYear', 'classes', 'sections', 'subjects', 'teachers'));
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['school_id'] = $school->id;
        ExtraClass::create($validated);

        return redirect()->route('principal.institute.extra-classes.index', $school)
            ->with('success', 'Extra Class created successfully!');
    }

    public function edit(School $school, ExtraClass $extraClass)
    {
        if ($extraClass->school_id !== $school->id) abort(404);

        $academicYears = AcademicYear::forSchool($school->id)->orderBy('start_date', 'desc')->get();
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('numeric_value')->get();
        $sections = Section::where('school_id', $school->id)->orderBy('name')->get();
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
        $teachers = User::whereHas('schoolRoles', function($q) use ($school) {
            $q->where('school_id', $school->id)
              ->whereHas('role', function($q2) {
                  $q2->where('name', 'Teacher');
              });
        })->orderBy('name')->get();

        return view('principal.institute.extra-classes.edit', compact('school', 'extraClass', 'academicYears', 'classes', 'sections', 'subjects', 'teachers'));
    }

    public function update(Request $request, School $school, ExtraClass $extraClass)
    {
        if ($extraClass->school_id !== $school->id) abort(404);

        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $extraClass->update($validated);

        return redirect()->route('principal.institute.extra-classes.index', $school)
            ->with('success', 'Extra Class updated successfully!');
    }

    public function destroy(School $school, ExtraClass $extraClass)
    {
        if ($extraClass->school_id !== $school->id) abort(404);
        $extraClass->delete();

        return redirect()->route('principal.institute.extra-classes.index', $school)
            ->with('success', 'Extra Class deleted successfully!');
    }

    public function manageStudents(School $school, ExtraClass $extraClass)
    {
        if ($extraClass->school_id !== $school->id) abort(404);

        // Fetch students from active enrollments for this class/academic year
        $students = Student::query()
            ->select('students.*')
            ->join('student_enrollments', 'students.id', '=', 'student_enrollments.student_id')
            ->where('students.school_id', $school->id)
            ->where('students.status', 'active')
            ->where('student_enrollments.status', 'active')
            ->where('student_enrollments.class_id', $extraClass->class_id)
            ->when($extraClass->academic_year_id, function($q) use ($extraClass) {
                $q->where('student_enrollments.academic_year_id', $extraClass->academic_year_id);
            })
            ->with(['currentEnrollment.section'])
            ->orderBy('student_enrollments.roll_no')
            ->get();

        $enrollments = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)
            ->with('assignedSection')
            ->get()
            ->keyBy('student_id');

        $sections = Section::where('school_id', $school->id)
            ->where('class_id', $extraClass->class_id)
            ->orderBy('name')
            ->get();

        return view('principal.institute.extra-classes.manage-students', compact('school', 'extraClass', 'students', 'enrollments', 'sections'));
    }

    public function storeStudents(Request $request, School $school, ExtraClass $extraClass)
    {
        if ($extraClass->school_id !== $school->id) abort(404);

        $validated = $request->validate([
            'enrollments' => 'nullable|array',
            'enrollments.*.student_id' => 'required|exists:students,id',
            'enrollments.*.assigned_section_id' => 'required|exists:sections,id',
        ]);

        DB::beginTransaction();
        try {
            ExtraClassEnrollment::where('extra_class_id', $extraClass->id)->delete();

            $rows = $validated['enrollments'] ?? [];
            foreach ($rows as $enrollment) {
                ExtraClassEnrollment::create([
                    'extra_class_id' => $extraClass->id,
                    'student_id' => $enrollment['student_id'],
                    'assigned_section_id' => $enrollment['assigned_section_id'],
                    'enrolled_date' => now(),
                    'status' => 'active',
                ]);
            }

            DB::commit();
            return redirect()->route('principal.institute.extra-classes.index', $school)
                ->with('success', 'Student enrollments updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update enrollments: ' . $e->getMessage());
        }
    }
}
