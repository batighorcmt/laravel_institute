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
        if (!$class) {
            return response()->json([]);
        }
        // Fetch groups assigned specifically to this class
        $groups = Group::where('school_id',$school->id)
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id','name','bangla_name']);

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
        $studentId = $request->query('student_id');
        $status = $request->query('status');
        $qText = trim((string)$request->query('q', ''));

        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearId = (int) $request->query('year_id');
        if (!$yearId && $currentYear) { $yearId = $currentYear->id; }

        $q = StudentEnrollment::select(
            'student_enrollments.student_id as record_id', 'student_enrollments.roll_no',
            'students.photo as photo',
            'students.student_id as student_code',
            'students.student_name_bn','students.student_name_en','students.guardian_phone',
            'students.father_name_bn','students.father_name','students.mother_name_bn','students.mother_name',
            'students.date_of_birth','students.gender','students.blood_group','students.religion',
            'students.present_village','students.present_post_office','students.present_upazilla','students.present_district',
            'students.permanent_village','students.permanent_post_office','students.permanent_upazilla','students.permanent_district',
            'classes.name as class_name','classes.bangla_name as class_name_bn',
            'sections.name as section_name','sections.bangla_name as section_name_bn',
            'academic_years.name as academic_year','academic_years.name_bn as academic_year_bn'
        )
        ->join('students','students.id','=','student_enrollments.student_id')
        ->join('classes','classes.id','=','student_enrollments.class_id')
        ->join('academic_years','academic_years.id','=','student_enrollments.academic_year_id')
        ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
        ->where('student_enrollments.school_id',$school->id)
        ->where('student_enrollments.academic_year_id',$yearId)
        ->when($classId, fn($qq)=>$qq->where('student_enrollments.class_id',(int)$classId))
        ->when($sectionId, fn($qq)=>$qq->where('student_enrollments.section_id',(int)$sectionId))
        ->when($studentId, fn($qq)=>$qq->where('student_enrollments.student_id',(int)$studentId))
        ->when($status, fn($qq)=>$qq->where('student_enrollments.status', $status))
        ->when($qText !== '', function($qq) use ($qText){
            $qq->where(function($sub) use ($qText){
                $sub->where('students.student_name_en','like','%'.$qText.'%')
                    ->orWhere('students.student_name_bn','like','%'.$qText.'%')
                    ->orWhere('students.student_id','like','%'.$qText.'%')
                    ->orWhere('student_enrollments.roll_no','like','%'.$qText.'%');
            });
        })
        ->orderBy('classes.numeric_value')
        ->orderBy('student_enrollments.roll_no')
        ->limit(500);

        $rows = $q->get()->map(function($r){
            return [
                'record_id' => (int)$r->record_id,
                'student_id' => $r->student_code,
                'photo' => $r->photo,
                'name' => $r->student_name_bn ?: $r->student_name_en,
                'student_name_bn' => $r->student_name_bn,
                'student_name_en' => $r->student_name_en,
                'father_name_bn' => $r->father_name_bn,
                'father_name' => $r->father_name,
                'mother_name_bn' => $r->mother_name_bn,
                'mother_name' => $r->mother_name,
                'date_of_birth' => $r->date_of_birth,
                'gender' => $r->gender,
                'blood_group' => $r->blood_group,
                'religion' => $r->religion,
                'phone' => $r->guardian_phone,
                'guardian_phone' => $r->guardian_phone,
                'present_village' => $r->present_village,
                'present_post_office' => $r->present_post_office,
                'present_upazilla' => $r->present_upazilla,
                'present_district' => $r->present_district,
                'permanent_village' => $r->permanent_village,
                'permanent_post_office' => $r->permanent_post_office,
                'permanent_upazilla' => $r->permanent_upazilla,
                'permanent_district' => $r->permanent_district,
                'roll_no' => $r->roll_no,
                'class_name' => $r->class_name,
                'class_name_bn' => $r->class_name_bn,
                'section_name' => $r->section_name,
                'section_name_bn' => $r->section_name_bn,
                'academic_year' => $r->academic_year,
                'academic_year_bn' => $r->academic_year_bn,
            ];
        });

        // Compute photo URLs server-side for reliable display
        $rows = $rows->map(function($item){
            $photo = $item['photo'] ?? null;
            $url = '/images/default-avatar.svg';
            if ($photo) {
                $photoPath = ltrim($photo, '/\\');
                if (str_starts_with($photoPath, 'students/')) {
                    if (file_exists(storage_path('app/public/' . $photoPath))) {
                        $url = '/storage/' . $photoPath;
                    }
                } else {
                    if (file_exists(storage_path('app/public/students/' . $photoPath))) {
                        $url = '/storage/students/' . $photoPath;
                    }
                }
                
                // Fallback for public path
                if ($url === '/images/default-avatar.svg') {
                    if (file_exists(public_path($photoPath))) {
                        $url = '/' . ltrim($photoPath, '/\\');
                    }
                }
            }
            $item['photo_url'] = $url;
            return $item;
        });

        return response()->json($rows);
    }

    public function classes(School $school)
    {
        $this->authorizePrincipal($school);
        $classes = $school->classes()->orderBy('numeric_value')->get(['id','name','bangla_name']);
        return response()->json($classes->map(function($c){
            return ['id'=>$c->id,'text'=>$c->bangla_name ?: $c->name];
        }));
    }

    public function academicYears(School $school)
    {
        $this->authorizePrincipal($school);
        $years = AcademicYear::where('school_id',$school->id)->orderBy('name','desc')->get(['id','name','name_bn']);
        return response()->json($years->map(function($y){
            return ['id'=>$y->id,'text'=>$y->name_bn ?: $y->name];
        }));
    }
}
