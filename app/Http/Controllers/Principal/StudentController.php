<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Group;
use App\Models\Team;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    protected function authorizePrincipal(School $school): void
    {
        /** @var \App\Models\User $u */ $u = Auth::user();
        abort_unless($u && ($u->isSuperAdmin() || $u->isPrincipal($school->id)), 403);
    }

    public function index(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $q = $request->get('q');
        $currentYear = \App\Models\AcademicYear::forSchool($school->id)->current()->first();
        $cyValue = null;
        if ($currentYear) {
            $cyValue = is_numeric($currentYear->name) ? (int)$currentYear->name : (int)optional($currentYear->start_date)->format('Y');
        }

        $students = Student::forSchool($school->id)
            ->when($q, function($x) use ($q){
                $x->where(function($inner) use ($q){
                    $inner->where('student_name_en','like',"%$q%")
                          ->orWhere('student_name_bn','like',"%$q%")
                          ->orWhere('student_id','like',"%$q%");
                });
            })
            // only students with enrollment in current academic year
            ->whereHas('enrollments', function($en) use ($cyValue){
                if ($cyValue) { $en->where('academic_year', $cyValue); }
                else { $en->whereRaw('1=0'); }
            })
            ->with(['enrollments' => function($en) use ($cyValue){
                if ($cyValue) {
                    $en->where('academic_year', $cyValue);
                }
                $en->with(['class','section','group','subjects.subject']);
            }])
            ->orderBy('id','desc')->paginate(20)->withQueryString();

        return view('principal.institute.students.index',[
            'school'=>$school,
            'students'=>$students,
            'q'=>$q,
            'currentYear'=>$currentYear,
            'cyValue'=>$cyValue,
        ]);
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
    $years = AcademicYear::forSchool($school->id)->orderByDesc('start_date')->get();
    $currentYear = AcademicYear::forSchool($school->id)->current()->first();
    return view('principal.institute.students.create',compact('school','years','currentYear'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
    $data = $request->validate([
            'student_name_en'=>['nullable','string','max:150'],
            'student_name_bn'=>['required','string','max:150'],
            'date_of_birth'=>['required','date'],
            'gender'=>['required','in:male,female'],
            'father_name'=>['required','string','max:120'],
            'mother_name'=>['required','string','max:120'],
            'father_name_bn'=>['required','string','max:150'],
            'mother_name_bn'=>['required','string','max:150'],
            'guardian_phone'=>['required','string','max:20'],
            'address'=>['required','string'],
            'blood_group'=>['nullable','in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'admission_date'=>['required','date'],
            'status'=>['required','in:active,inactive,graduated,transferred'],
        ]);
        $data['school_id']=$school->id;
        // keep class_id null; enrollments drive class/year history
        $data['class_id']=null;
        $student = Student::create($data);

        // Inline enrollment (optional)
        $enrollData = $request->validate([
            'enroll_academic_year'=>['nullable','integer','min:2000','max:2100'],
            'enroll_class_id'=>['nullable', Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_section_id'=>['nullable', Rule::exists('sections','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_group_id'=>['nullable', Rule::exists('groups','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_roll_no'=>['nullable','integer','min:1']
        ]);

        $createdEnrollment = null;
        if (!empty($enrollData['enroll_academic_year']) && !empty($enrollData['enroll_class_id']) && !empty($enrollData['enroll_roll_no'])) {
            $class = SchoolClass::find($enrollData['enroll_class_id']);
            if ($class && !$class->usesGroups()) {
                $enrollData['enroll_group_id'] = null;
            }
            try {
                $createdEnrollment = StudentEnrollment::create([
                    'student_id'=>$student->id,
                    'school_id'=>$school->id,
                    'academic_year'=>$enrollData['enroll_academic_year'],
                    'class_id'=>$enrollData['enroll_class_id'],
                    'section_id'=>$enrollData['enroll_section_id'] ?? null,
                    'group_id'=>$enrollData['enroll_group_id'] ?? null,
                    'roll_no'=>$enrollData['enroll_roll_no'],
                    'status'=>'active'
                ]);
            } catch (\Throwable $e) {
                // If roll duplicate, just fall back to profile with error
                return redirect()->route('principal.institute.students.show',[$school,$student])
                    ->with('error','Enrollment তৈরি হয়নি: এই বছর/ক্লাস/সেকশনে রোলটি আগে থেকেই আছে');
            }
        }

        if ($createdEnrollment) {
            return redirect()->route('principal.institute.enrollments.subjects.edit',[$school,$createdEnrollment])
                ->with('success','শিক্ষার্থী যুক্ত হয়েছে — এখন বিষয় নির্বাচন করুন');
        }

        return redirect()->route('principal.institute.students.show',[$school,$student])->with('success','শিক্ষার্থী যুক্ত হয়েছে');
    }

    public function show(School $school, Student $student)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
    $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $enrollments = $student->enrollments()
            ->with(['class','section','group','subjects.subject'])
            ->orderByDesc('academic_year')->get();
        // Student's own team memberships (not all teams)
    $memberships = $student->teams()->withPivot('joined_at','status','created_at')->orderBy('name')->get();
    $allTeams = Team::forSchool($school->id)->orderBy('name')->get();

        // Metrics
    $totalYears = $enrollments->count();
    $currentYearAcademic = $currentYear && is_numeric($currentYear->name) ? (int)$currentYear->name : null;
    $activeEnrollment = $currentYearAcademic ? $enrollments->firstWhere('academic_year', $currentYearAcademic) : null;
        $currentSubjects = $activeEnrollment ? $activeEnrollment->subjects->map(function($ss){
            return [
                'code'=>optional($ss->subject)->code,
                'name'=>optional($ss->subject)->name,
                'optional'=>$ss->is_optional,
            ];
        }) : collect();

        // Timeline events derived from admissions, enrollments, team joins
        $timeline = collect();
        if ($student->admission_date) {
            $adm = Carbon::parse($student->admission_date);
            $timeline->push([
                'date'=>$adm->format('Y-m-d'),
                'type'=>'admission',
                'label'=>'ভর্তি',
                'detail'=>'ভর্তি তারিখ: '.$adm->format('d-m-Y')
            ]);
        }
        foreach ($enrollments as $en) {
            $enDate = $en->created_at ?: Carbon::create($en->academic_year,1,1);
            $timeline->push([
                'date'=>$enDate->format('Y-m-d'),
                'type'=>'enrollment',
                'label'=>'ভর্তি ('.$en->academic_year.')',
                'detail'=>'ক্লাস: '.($en->class?->name).' রোল: '.$en->roll_no
            ]);
        }
        foreach ($memberships as $tm) {
            $rawDate = $tm->pivot->joined_at ?: ($tm->pivot->created_at?->format('Y-m-d'));
            if ($rawDate) {
                $timeline->push([
                    'date'=>$rawDate,
                    'type'=>'team',
                    'label'=>'দলে যুক্ত: '.$tm->name,
                    'detail'=>'স্ট্যাটাস: '.$tm->pivot->status
                ]);
            }
        }
        $timeline = $timeline->filter(fn($e)=>!empty($e['date']))->sortByDesc('date')->values();

        return view('principal.institute.students.show',compact(
            'school','student','enrollments','memberships','allTeams','currentYear','activeEnrollment','currentSubjects','totalYears','timeline'
        ));
    }

    public function edit(School $school, Student $student)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
        return view('principal.institute.students.edit',compact('school','student'));
    }

    public function update(School $school, Student $student, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
        $data = $request->validate([
            'student_name_en'=>['nullable','string','max:150'],
            'student_name_bn'=>['required','string','max:150'],
            'date_of_birth'=>['required','date'],
            'gender'=>['required','in:male,female'],
            'father_name'=>['required','string','max:120'],
            'mother_name'=>['required','string','max:120'],
            'father_name_bn'=>['required','string','max:150'],
            'mother_name_bn'=>['required','string','max:150'],
            'guardian_phone'=>['required','string','max:20'],
            'address'=>['required','string'],
            'blood_group'=>['nullable','in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'admission_date'=>['required','date'],
            'status'=>['required','in:active,inactive,graduated,transferred'],
        ]);
    $student->update($data);
        return redirect()->route('principal.institute.students.show',[$school,$student])->with('success','শিক্ষার্থী আপডেট হয়েছে');
    }

    // Enrollment operations
    public function addEnrollment(School $school, Student $student, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
        $data = $request->validate([
            'academic_year'=>['required','integer','min:2000','max:2100'],
            'class_id'=>['required', Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'section_id'=>['nullable', Rule::exists('sections','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'group_id'=>['nullable', Rule::exists('groups','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'roll_no'=>['required','integer','min:1']
        ]);
        // validate group usage only if class uses groups
        $class = SchoolClass::find($data['class_id']);
        if ($class && !$class->usesGroups()) {
            $data['group_id'] = null;
        }
        $data['student_id']=$student->id; $data['school_id']=$school->id; $data['status']='active';
        // unique roll check handled by DB unique, but provide friendly error
        try {
            StudentEnrollment::create($data);
        } catch (\Throwable $e) {
            return back()->with('error','এই বছর/ক্লাস/সেকশনে এই রোলটি আগে থেকেই আছে');
        }
        return back()->with('success','Enrollment সংযুক্ত হয়েছে');
    }

    public function removeEnrollment(School $school, Student $student, StudentEnrollment $enrollment)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id && $enrollment->student_id===$student->id,404);
        $enrollment->delete();
        return back()->with('success','Enrollment মুছে ফেলা হয়েছে');
    }

    // Team membership
    public function attachTeam(School $school, Student $student, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
        $data = $request->validate([
            'team_id'=>['required', Rule::exists('teams','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'joined_at'=>['nullable','date']
        ]);
        $student->teams()->syncWithoutDetaching([$data['team_id']=>['joined_at'=>$data['joined_at'],'status'=>'active']]);
        return back()->with('success','দলে যুক্ত করা হয়েছে');
    }

    public function detachTeam(School $school, Student $student, Team $team)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id && $team->school_id===$school->id,404);
        $student->teams()->detach($team->id);
        return back()->with('success','দল থেকে অপসারিত');
    }

    // Toggle active/inactive status
    public function toggleStatus(School $school, Student $student)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
        $student->status = $student->status === 'active' ? 'inactive' : 'active';
        $student->save();
        return back()->with('success', $student->status === 'active' ? 'শিক্ষার্থী সক্রিয় করা হয়েছে' : 'শিক্ষার্থী নিষ্ক্রিয় করা হয়েছে');
    }
}
