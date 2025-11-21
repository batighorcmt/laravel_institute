<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Group;
use App\Models\StudentEnrollment;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetaController extends Controller
{
    protected function authorizePrincipal(School $school): void
    {
        /** @var \App\Models\User $u */ $u = Auth::user();
        abort_unless($u && ($u->isSuperAdmin() || $u->isPrincipal($school->id)), 403);
    }

    public function sections(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $classId = (int) $request->query('class_id');
        $sections = Section::where('school_id',$school->id)
            ->where('class_id',$classId)
            ->orderBy('name')
            ->get(['id','name']);
        return response()->json($sections);
    }

    public function groups(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $classId = (int) $request->query('class_id');
        $class = SchoolClass::find($classId);
        if (!$class || !$class->usesGroups()) {
            return response()->json([]);
        }
        $groups = Group::where('school_id',$school->id)->orderBy('name')->get(['id','name']);
        return response()->json($groups);
    }

    public function nextRoll(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $yearId = (int) $request->query('year_id');
        // backward compatibility: if numeric 'year' provided, map to AcademicYear
        if (!$yearId && $request->has('year')) {
            $yearNumber = (int)$request->query('year');
            if ($yearNumber) {
                $yearModel = AcademicYear::firstOrCreate([
                    'school_id'=>$school->id,
                    'name'=>(string)$yearNumber,
                ],[
                    'start_date'=>now()->setDate($yearNumber,1,1),
                    'end_date'=>now()->setDate($yearNumber,12,31),
                    'is_current'=>false,
                ]);
                $yearId = $yearModel->id;
            }
        }
        $classId = (int) $request->query('class_id');
        $sectionId = $request->query('section_id');
        $groupId = $request->query('group_id');

        $q = StudentEnrollment::where('school_id',$school->id)
            ->where('academic_year_id',$yearId)
            ->where('class_id',$classId);
        if (!empty($sectionId)) $q->where('section_id',$sectionId);
        if (!empty($groupId)) $q->where('group_id',$groupId);
        $max = (int) $q->max('roll_no');
        return response()->json(['next' => $max + 1]);
    }

    public function students(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $qText = trim((string)$request->query('q', ''));

        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearId = (int) $request->query('year_id');
        if (!$yearId && $currentYear) { $yearId = $currentYear->id; }

        $q = StudentEnrollment::select(
            'student_enrollments.student_id', 'student_enrollments.roll_no',
            'students.student_name_bn','students.student_name_en','students.guardian_phone',
            'classes.name as class_name','sections.name as section_name'
        )
        ->join('students','students.id','=','student_enrollments.student_id')
        ->join('classes','classes.id','=','student_enrollments.class_id')
        ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
        ->where('student_enrollments.school_id',$school->id)
        ->where('student_enrollments.academic_year_id',$yearId)
        ->when($classId, fn($qq)=>$qq->where('student_enrollments.class_id',(int)$classId))
        ->when($sectionId, fn($qq)=>$qq->where('student_enrollments.section_id',(int)$sectionId))
        ->when($qText !== '', function($qq) use ($qText){
            $qq->where(function($sub) use ($qText){
                $sub->where('students.student_name_en','like','%'.$qText.'%')
                    ->orWhere('students.student_name_bn','like','%'.$qText.'%')
                    ->orWhere('student_enrollments.roll_no','like','%'.$qText.'%');
            });
        })
        ->orderBy('classes.numeric_value')
        ->orderBy('student_enrollments.roll_no')
        ->limit(500);

        $rows = $q->get()->map(function($r){
            return [
                'student_id' => (int)$r->student_id,
                'name' => $r->student_name_bn ?: $r->student_name_en,
                'phone' => $r->guardian_phone,
                'roll_no' => $r->roll_no,
                'class_name' => $r->class_name,
                'section_name' => $r->section_name,
            ];
        });

        return response()->json($rows);
    }
}
