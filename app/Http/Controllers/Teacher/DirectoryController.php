<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Group;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function students(School $school, Request $request)
    {
        $this->authorizeTeacherForSchool($school);

        $classes = SchoolClass::forSchool($school->id)->active()->orderBy('numeric_value')->get();
        $sections = Section::forSchool($school->id)->where('status','active')
            ->when($request->class_id, fn($q)=>$q->where('class_id', $request->class_id))->get();
        $groups = Group::forSchool($school->id)->where('status','active')->get();

        $query = StudentEnrollment::select(
                'student_enrollments.*',
                'students.student_name_bn','students.student_name_en',
                'students.gender','students.religion','students.guardian_phone','students.photo',
                'students.student_id as student_code','students.id as student_pk',
                'classes.name as class_name','sections.name as section_name','groups.name as group_name'
            )
            ->join('students','students.id','=','student_enrollments.student_id')
            ->join('classes','classes.id','=','student_enrollments.class_id')
            ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
            ->leftJoin('groups','groups.id','=','student_enrollments.group_id')
            ->where('student_enrollments.school_id', $school->id)
            ->where('student_enrollments.status','active');

        if ($request->filled('class_id')) $query->where('student_enrollments.class_id', $request->class_id);
        if ($request->filled('section_id')) $query->where('student_enrollments.section_id', $request->section_id);
        if ($request->filled('group_id')) $query->where('student_enrollments.group_id', $request->group_id);
        if ($request->filled('roll_no')) $query->where('student_enrollments.roll_no', $request->roll_no);
        // Filter by institutional student_id (not internal PK)
        if ($request->filled('student_id')) $query->where('students.student_id', $request->student_id);
        if ($request->filled('gender')) $query->where('students.gender', $request->gender);
        if ($request->filled('religion')) $query->where('students.religion', $request->religion);
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function($w) use ($q){
                $w->where('students.student_name_bn','like',"%$q%")
                  ->orWhere('students.student_name_en','like',"%$q%")
                  ->orWhere('student_enrollments.roll_no','like',"%$q%")
                  ->orWhere('students.student_id','like',"%$q%");
            });
        }

        $students = $query->orderBy('classes.numeric_value')
            ->orderBy('student_enrollments.section_id')
            ->orderBy('student_enrollments.roll_no')
            ->paginate(20)->withQueryString();

        // AJAX response for live filtering
        if ($request->ajax()) {
            return response()->json([
                'rows' => view('teacher.directory.partials.student-rows', [
                    'students' => $students,
                    'school' => $school,
                ])->render(),
                'pagination' => view('teacher.directory.partials.student-pagination', [
                    'students' => $students,
                ])->render(),
                'total' => $students->total(),
            ]);
        }

        return view('teacher.directory.students', compact('school','classes','sections','groups','students'));
    }

    public function studentShow(School $school, Student $student)
    {
        $this->authorizeTeacherForSchool($school);
        if ((int)$student->school_id !== (int)$school->id) abort(404);
        $enroll = StudentEnrollment::where('school_id',$school->id)
            ->where('student_id',$student->id)
            ->where('status','active')
            ->with(['class','section','group'])
            ->first();
        return view('teacher.directory.student-show', compact('school','student','enroll'));
    }

    public function teachers(School $school, Request $request)
    {
        $this->authorizeTeacherForSchool($school);
        $tq = Teacher::forSchool($school->id)->active();
        if ($request->filled('q')) {
            $q = trim($request->q);
            $tq->where(function($w) use ($q){
                $w->where('first_name','like',"%$q%")
                  ->orWhere('last_name','like',"%$q%")
                  ->orWhere('first_name_bn','like',"%$q%")
                  ->orWhere('last_name_bn','like',"%$q%")
                  ->orWhere('designation','like',"%$q%")
                  ->orWhere('phone','like',"%$q%")
                  ->orWhere('email','like',"%$q%" );
            });
        }
        $teachers = $tq->orderBy('serial_number')->paginate(24)->withQueryString();
        return view('teacher.directory.teachers', compact('school','teachers'));
    }

    private function authorizeTeacherForSchool(School $school): void
    {
        $u = auth()->user();
        if (!$u || !$u->isTeacher()) abort(403);
        // Teachers can access only their primary school context
        if (method_exists($u,'primarySchool') && $u->primarySchool() && $u->primarySchool()->id !== $school->id) {
            abort(403);
        }
    }
}
