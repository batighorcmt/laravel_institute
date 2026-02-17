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
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\WeeklyHoliday;
use App\Models\LessonEvaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\ProcessStudentBulkImport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentBulkTemplateExport;

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
        $years = AcademicYear::forSchool($school->id)->orderByDesc('start_date')->get();
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $selectedYearId = (int)($request->query('year_id') ?: ($currentYear->id ?? 0));
        $selectedYear = $years->firstWhere('id', $selectedYearId);

        // Load school relationships for filter options
        $school->load(['classes', 'sections', 'groups']);
        // Get filter parameters
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $groupId = $request->get('group_id');
        $status = $request->get('status');
        $gender = $request->get('gender');
        $religion = $request->get('religion');
        $village = $request->get('village');
        $rollNo = $request->get('roll_no');

        // Also fetch lists explicitly to avoid reliance on eager loading in views
        $classes = SchoolClass::forSchool($school->id)->ordered()->get();
        $sections = $classId 
            ? Section::where('sections.school_id', $school->id)->where('sections.class_id', $classId)->ordered()->get()
            : collect();
        $groups = ($classId && ($cls = SchoolClass::find($classId)) && $cls->usesGroups())
            ? Group::where('school_id', $school->id)->orderBy('name')->get()
            : collect();

        $students = Student::select('students.*')
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->join('classes', 'classes.id', '=', 'student_enrollments.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'student_enrollments.section_id')
            ->where('students.school_id', $school->id)
            ->where('student_enrollments.academic_year_id', $selectedYearId)
            ->when($q, function($x) use ($q){
                $x->where(function($inner) use ($q){
                    $inner->where('students.student_name_en','like',"%$q%")
                          ->orWhere('students.student_name_bn','like',"%$q%")
                          ->orWhere('students.student_id','like',"%$q%");
                });
            })
            ->when($status, function($x) use ($status){
                $x->where('students.status', $status);
            })
            ->when($gender, function($x) use ($gender){
                $x->where('students.gender', $gender);
            })
            ->when($religion, function($x) use ($religion){
                $x->where('students.religion', $religion);
            })
            ->when($village, function($x) use ($village){
                $x->where('students.present_village', $village);
            })
            ->when($classId, function($x) use ($classId){
                $x->where('student_enrollments.class_id', $classId);
            })
            ->when($sectionId, function($x) use ($sectionId){
                $x->where('student_enrollments.section_id', $sectionId);
            })
            ->when($groupId, function($x) use ($groupId){
                $x->where('student_enrollments.group_id', $groupId);
            })
            ->when($rollNo, function($x) use ($rollNo){
                $x->where('student_enrollments.roll_no', $rollNo);
            })
            ->with(['enrollments' => function($en) use ($selectedYearId){
                if ($selectedYearId) { $en->where('academic_year_id', $selectedYearId); }
                $en->with(['class','section','group','subjects.subject','academicYear']);
            }])
            ->orderBy('classes.numeric_value')
            ->orderBy('sections.name')
            ->orderBy('student_enrollments.roll_no')
            ->paginate($request->get('per_page', 10))->withQueryString();

        return view('principal.institute.students.index',[
            'school'=>$school,
            'students'=>$students,
            'q'=>$q,
            'years'=>$years,
            'currentYear'=>$currentYear,
            'selectedYear'=>$selectedYear,
            'selectedYearId'=>$selectedYearId,
            // filter source lists
            'classes' => $classes,
            'sections' => $sections,
            'groups' => $groups,
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
            'student_name_bn'=>['nullable','string','max:150'],
            'date_of_birth'=>['required','date'],
            'gender'=>['required','in:male,female'],
            'father_name'=>['required','string','max:120'],
            'mother_name'=>['required','string','max:120'],
            'father_name_bn'=>['required','string','max:150'],
            'mother_name_bn'=>['required','string','max:150'],
            'guardian_phone'=>['required','string','max:20'],
            'guardian_relation'=>['nullable','in:father,mother,other'],
            'guardian_name_en'=>['nullable','string','max:120'],
            'guardian_name_bn'=>['nullable','string','max:150'],
            // Address component fields
            'present_village'=>['nullable','string','max:120'],
            'present_para_moholla'=>['nullable','string','max:120'],
            'present_post_office'=>['nullable','string','max:120'],
            'present_upazilla'=>['nullable','string','max:120'],
            'present_district'=>['nullable','string','max:120'],
            'permanent_village'=>['nullable','string','max:120'],
            'permanent_para_moholla'=>['nullable','string','max:120'],
            'permanent_post_office'=>['nullable','string','max:120'],
            'permanent_upazilla'=>['nullable','string','max:120'],
            'permanent_district'=>['nullable','string','max:120'],
            'blood_group'=>['nullable','in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'religion'=>['nullable','in:Islam,Hindu,Buddhist,Christian,Other,islam,hindu,buddhist,christian,other'],
            'previous_school'=>['nullable','string','max:200'],
            'pass_year'=>['nullable','string','max:10'],
            'previous_result'=>['nullable','string','max:50'],
            'previous_remarks'=>['nullable','string','max:500'],
            'admission_date'=>['required','date'],
            'status'=>['required','in:active,inactive,graduated,transferred'],
            'photo'=>['nullable','image','max:1024'],
        ]);
        $data['school_id']=$school->id;
        // keep class_id null; enrollments drive class/year history
        $data['class_id']=null;
        // Handle photo upload (store on public disk under students/)
        if ($request->hasFile('photo')) {
            try {
                $photoPath = $request->file('photo')->store('students','public');
                $data['photo'] = $photoPath;
            } catch (\Throwable $e) {
                Log::warning('Photo upload failed: '.$e->getMessage());
            }
        }

        // Generate student_id with class info from enrollment
        $enrollClassId = $request->input('enroll_class_id');
        $enrollClass = $enrollClassId ? SchoolClass::find($enrollClassId) : null;
        $classNumeric = $enrollClass ? $enrollClass->numeric_value : 1;
        
        // Try to create student with retry mechanism for unique student_id
        $maxRetries = 5;
        $student = null;
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $data['student_id'] = Student::generateStudentId($school->id, $classNumeric);
                $student = Student::create($data);
                break; // Success, exit loop
            } catch (\Illuminate\Database\QueryException $e) {
                // Check if it's a duplicate key error
                if ($e->errorInfo[1] == 1062 && $i < $maxRetries - 1) {
                    // Duplicate entry, retry with a small delay
                    usleep(100000); // 100ms delay
                    continue;
                }
                // If not duplicate or max retries reached, throw error
                throw $e;
            }
        }
        
        if (!$student) {
            return back()->withInput()->withErrors(['error' => 'শিক্ষার্থী তৈরি করতে ব্যর্থ। আবার চেষ্টা করুন।']);
        }

        // Inline enrollment (optional but if provided, validate properly)
        $enrollData = $request->validate([
            'enroll_academic_year_id'=>['nullable', Rule::exists('academic_years','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_class_id'=>['nullable', Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_section_id'=>['required_with:enroll_class_id', Rule::exists('sections','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_group_id'=>['nullable', Rule::exists('groups','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_roll_no'=>['nullable','integer','min:1']
        ]);
        
        // Validate group for class 9 and 10
        if (!empty($enrollData['enroll_class_id'])) {
            $class = SchoolClass::find($enrollData['enroll_class_id']);
            if ($class && $class->usesGroups() && empty($enrollData['enroll_group_id'])) {
                return back()->withInput()->withErrors(['enroll_group_id' => 'ক্লাস ৯ম ও ১০ম এর জন্য গ্রুপ নির্বাচন বাধ্যতামূলক']);
            }
        }

        $createdEnrollment = null;
        if (!empty($enrollData['enroll_academic_year_id']) && !empty($enrollData['enroll_class_id']) && !empty($enrollData['enroll_roll_no'])) {
            $class = SchoolClass::find($enrollData['enroll_class_id']);
            if ($class && !$class->usesGroups()) {
                $enrollData['enroll_group_id'] = null;
            }
            try {
                $createdEnrollment = StudentEnrollment::create([
                    'student_id'=>$student->id,
                    'school_id'=>$school->id,
                    'academic_year_id'=>$enrollData['enroll_academic_year_id'],
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

    public function show(School $school, Student $student, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $years = AcademicYear::forSchool($school->id)->orderByDesc('start_date')->get();
        $enrollments = $student->enrollments()
            ->with(['class','section','group','subjects.subject','academicYear'])
            ->orderByDesc('academic_year_id')->get();
        // Student's own team memberships (not all teams)
    $memberships = $student->teams()->withPivot('joined_at','status','created_at')->orderBy('name')->get();
    $allTeams = Team::forSchool($school->id)->orderBy('name')->get();

        // Metrics
        $totalYears = $enrollments->count();
        $activeEnrollment = $currentYear ? $enrollments->firstWhere('academic_year_id', $currentYear->id) : null;
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
            $yearName = $en->academicYear?->name;
            $yearNumeric = is_numeric($yearName ?? '') ? (int)$yearName : (int)($en->created_at?->format('Y'));
            $enDate = $en->created_at ?: Carbon::create($yearNumeric,1,1);
            $timeline->push([
                'date'=>$enDate->format('Y-m-d'),
                'type'=>'enrollment',
                'label'=>'ভর্তি ('.$yearName.')',
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

        // Attendance statistics for pie chart
        $attendanceStats = [
            'present' => \App\Models\Attendance::where('student_id', $student->id)->where('status', 'present')->count(),
            'absent' => \App\Models\Attendance::where('student_id', $student->id)->where('status', 'absent')->count(),
            'late' => \App\Models\Attendance::where('student_id', $student->id)->where('status', 'late')->count(),
            'leave' => \App\Models\Attendance::where('student_id', $student->id)->where('status', 'leave')->count(),
        ];
        $workingDays = array_sum($attendanceStats);

        // Calendar Data
        $selectedMonth = $request->get('month', date('n'));
        $selectedYearVal = $request->get('year', date('Y'));
        $carbonDate = Carbon::createFromDate($selectedYearVal, $selectedMonth, 1);
        
        $attendances = Attendance::where('student_id', $student->id)
            ->whereYear('date', $selectedYearVal)
            ->whereMonth('date', $selectedMonth)
            ->get()
            ->keyBy(fn($a) => $a->date->format('j'));

        $holidays = Holiday::forSchool($school->id)
            ->active()
            ->whereYear('date', $selectedYearVal)
            ->whereMonth('date', $selectedMonth)
            ->get()
            ->keyBy(fn($h) => $h->date->format('j'));

        $weeklyHolidays = WeeklyHoliday::forSchool($school->id)
            ->active()
            ->pluck('day_number')
            ->toArray(); // 0 (Sun) to 6 (Sat)

        return view('principal.institute.students.show',compact(
            'school','student','enrollments','memberships','allTeams','currentYear','activeEnrollment','currentSubjects','totalYears','timeline','years',
            'attendanceStats','workingDays', 'attendances', 'holidays', 'weeklyHolidays', 'carbonDate'
        ));
    }

    public function lessonEvaluationDetails(School $school, Student $student, Request $request)
    {
        $this->authorizePrincipal($school);
        $date = $request->get('date');
        
        $evaluations = LessonEvaluation::with(['subject', 'teacher', 'records' => function($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->where('school_id', $school->id)
            ->whereDate('evaluation_date', $date)
            ->get();

        $data = $evaluations->map(function($ev) {
            $record = $ev->records->first();
            return [
                'subject' => $ev->subject?->name,
                'teacher' => $ev->teacher?->full_name,
                'status' => $record?->status_label ?? 'N/A',
                'status_raw' => $record?->status
            ];
        });

        return response()->json([
            'date' => Carbon::parse($date)->format('d-m-Y'),
            'evaluations' => $data,
            'summary' => [
                'total' => $data->count(),
                'completed' => $data->where('status_raw', 'completed')->count(),
                'not_done' => $data->where('status_raw', 'not_done')->count()
            ]
        ]);
    }

    public function edit(School $school, Student $student)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
        
        // Get current year and active enrollment for editing
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $activeEnrollment = $currentYear ? $student->enrollments()->where('academic_year_id', $currentYear->id)->first() : null;
        $years = AcademicYear::forSchool($school->id)->orderByDesc('start_date')->get();
        
        return view('principal.institute.students.edit',compact('school','student','currentYear','activeEnrollment','years'));
    }

    public function update(School $school, Student $student, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
        $data = $request->validate([
            'student_name_en'=>['required','string','max:150'],
            'student_name_bn'=>['nullable','string','max:150'],
            'date_of_birth'=>['nullable','date'],
            'gender'=>['required','in:male,female'],
            'father_name'=>['required','string','max:120'],
            'mother_name'=>['required','string','max:120'],
            'father_name_bn'=>['required','string','max:150'],
            'mother_name_bn'=>['required','string','max:150'],
            'guardian_phone'=>['required','string','max:20'],
            'guardian_relation'=>['nullable','in:father,mother,other'],
            'guardian_name_en'=>['nullable','string','max:120'],
            'guardian_name_bn'=>['nullable','string','max:150'],
            'present_village'=>['nullable','string','max:120'],
            'present_para_moholla'=>['nullable','string','max:120'],
            'present_post_office'=>['nullable','string','max:120'],
            'present_upazilla'=>['nullable','string','max:120'],
            'present_district'=>['nullable','string','max:120'],
            'permanent_village'=>['nullable','string','max:120'],
            'permanent_para_moholla'=>['nullable','string','max:120'],
            'permanent_post_office'=>['nullable','string','max:120'],
            'permanent_upazilla'=>['nullable','string','max:120'],
            'permanent_district'=>['nullable','string','max:120'],
            'blood_group'=>['nullable','in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'religion'=>['nullable','in:Islam,Hindu,Buddhist,Christian,Other,islam,hindu,buddhist,christian,other'],
            'previous_school'=>['nullable','string','max:200'],
            'pass_year'=>['nullable','string','max:10'],
            'previous_result'=>['nullable','string','max:50'],
            'previous_remarks'=>['nullable','string','max:500'],
            'admission_date'=>['nullable','date'],
            'status'=>['required','in:active,inactive,graduated,transferred'],
            'photo'=>['nullable','image','max:1024'],
        ]);
        // Handle photo upload on update: store new and remove old if present
        if ($request->hasFile('photo')) {
                try {
                    $photoPath = $request->file('photo')->store('students','public');
                    // remove old photo from public disk if exists
                    if (!empty($student->photo)) {
                        try { Storage::disk('public')->delete($student->photo); } catch (\Throwable $e) { /* ignore */ }
                    }
                    $data['photo'] = $photoPath;
                } catch (\Throwable $e) {
                    Log::warning('Photo upload failed (update): '.$e->getMessage());
                }
            }

            $student->update($data);
            
        // Handle enrollment update if provided
        $enrollData = $request->validate([
            'enroll_academic_year_id'=>['nullable', Rule::exists('academic_years','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_class_id'=>['nullable', Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_section_id'=>['required_with:enroll_class_id', Rule::exists('sections','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_group_id'=>['nullable', Rule::exists('groups','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_roll_no'=>['nullable','integer','min:1']
        ]);
        
        if (!empty($enrollData['enroll_academic_year_id']) && !empty($enrollData['enroll_class_id']) && !empty($enrollData['enroll_roll_no'])) {
            // Check if enrollment exists for this year
            $enrollment = $student->enrollments()->where('academic_year_id', $enrollData['enroll_academic_year_id'])->first();
            
            // Validate group for class 9 and 10
            $class = SchoolClass::find($enrollData['enroll_class_id']);
            if ($class && $class->usesGroups() && empty($enrollData['enroll_group_id'])) {
                return back()->withInput()->withErrors(['enroll_group_id' => 'ক্লাস ৯ম ও ১০ম এর জন্য গ্রুপ নির্বাচন বাধ্যতামূলক']);
            }
            
            if ($class && !$class->usesGroups()) {
                $enrollData['enroll_group_id'] = null;
            }
            
            if ($enrollment) {
                // Update existing enrollment
                $enrollment->update([
                    'class_id' => $enrollData['enroll_class_id'],
                    'section_id' => $enrollData['enroll_section_id'] ?? null,
                    'group_id' => $enrollData['enroll_group_id'] ?? null,
                    'roll_no' => $enrollData['enroll_roll_no'],
                ]);
            } else {
                // Create new enrollment
                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'school_id' => $school->id,
                    'academic_year_id' => $enrollData['enroll_academic_year_id'],
                    'class_id' => $enrollData['enroll_class_id'],
                    'section_id' => $enrollData['enroll_section_id'] ?? null,
                    'group_id' => $enrollData['enroll_group_id'] ?? null,
                    'roll_no' => $enrollData['enroll_roll_no'],
                    'status' => 'active'
                ]);
            }
        }
            
        return redirect()->route('principal.institute.students.show',[$school,$student])->with('success','শিক্ষার্থী আপডেট হয়েছে');
    }

    // Enrollment operations
    public function addEnrollment(School $school, Student $student, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($student->school_id===$school->id,404);
        $data = $request->validate([
            'academic_year_id'=>['required', Rule::exists('academic_years','id')->where(fn($q)=>$q->where('school_id',$school->id))],
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

    /**
     * Show bulk import form (CSV)
     */
    public function bulkForm(School $school)
    {
        $this->authorizePrincipal($school);
        return view('principal.institute.students.bulk_import', compact('school'));
    }

    /**
     * Process uploaded CSV and create students + optional enrollments
     */
    public function bulkImport(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $request->validate([
            'file'=>['required','file'],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?? '');

        $rows = [];
        // Support XLSX/XLS/ODS via maatwebsite/excel if installed
        if (in_array($ext, ['xlsx','xls','ods'])) {
            if (!class_exists('Maatwebsite\\Excel\\Facades\\Excel')) {
                return back()->with('error','XLS/XLSX ইমপোর্ট সমর্থিত নয় — প্যাকেজ ইনস্টল করুন: composer require maatwebsite/excel');
            }
            try {
                $import = new class implements \Maatwebsite\Excel\Concerns\ToArray {
                    public $sheets = [];
                    public function array(array $array) { $this->sheets[] = $array; }
                };
                $sheets = \Maatwebsite\Excel\Facades\Excel::toArray($import, $file);
            } catch (\Throwable $e) {
                return back()->with('error','ফাইল পড়তে ব্যর্থ: ' . $e->getMessage());
            }
            if (empty($sheets) || empty($sheets[0]) || count($sheets[0]) < 2) {
                return back()->with('error','শিট খালি অথবা হেডার অনুপস্থিত');
            }
            $header = array_map(function($h){ return trim(strtolower($h)); }, $sheets[0][0]);
            $dataRows = array_slice($sheets[0],1);
            $rows = $dataRows;
        } else {
            // treat as CSV
            $path = $file->getRealPath();
            if (!$path) { return back()->with('error','ফাইল খালি বা পড়া যায় না'); }
            $handle = fopen($path, 'r');
            if (!$handle) { return back()->with('error','ফাইল খোলা যায়নি'); }
            $header = null;
            while (($data = fgetcsv($handle)) !== false) {
                if (!$header) { $header = array_map(function($h){ return trim(strtolower($h)); }, $data); continue; }
                $rows[] = $data;
            }
            fclose($handle);
            if (!$header || count($rows) === 0) { return back()->with('error','CSV হেডার/রো খালি'); }
        }

        $rowNo = 1;
        $success = 0;
        $errors = [];
        $importedStudents = [];

        foreach ($rows as $cols) {
            $rowNo++;
            // skip empty rows
            $allBlank = true; foreach ($cols as $c) { if (trim((string)$c) !== '') { $allBlank = false; break; } }
            if ($allBlank) continue;
            $assoc = [];
            foreach ($header as $i => $colName) {
                $assoc[$colName] = isset($cols[$i]) ? trim((string)$cols[$i]) : null;
            }

            // Only required: student_name_en, enroll_academic_year, enroll_roll_no, class_name, section_name
            $validator = \Illuminate\Support\Facades\Validator::make($assoc, [
                'student_name_en' => ['required','string','max:150'],
                'enroll_academic_year' => ['required','numeric'],
                'enroll_roll_no' => ['required','numeric'],
                // Only class/section names required (not IDs)
                'enroll_class_name' => ['required','string'],
                'enroll_section_name' => ['required','string'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNo}: validation failed - " . implode(', ', $validator->errors()->all());
                continue;
            }

            // Optional date parsing (ignore if invalid or empty)
            $dob = null; if (!empty($assoc['date_of_birth'])) { try { $dob = Carbon::parse($assoc['date_of_birth'])->toDateString(); } catch (\Throwable $e) { try { $dob = Carbon::createFromFormat('d/m/Y', $assoc['date_of_birth'])->toDateString(); } catch (\Throwable $e) { $dob = null; } } }
            $admission_date = null; if (!empty($assoc['admission_date'])) { try { $admission_date = Carbon::parse($assoc['admission_date'])->toDateString(); } catch (\Throwable $e) { try { $admission_date = Carbon::createFromFormat('d/m/Y', $assoc['admission_date'])->toDateString(); } catch (\Throwable $e) { $admission_date = null; } } }

            // Get class first to generate proper student_id
            $cname = trim($assoc['enroll_class_name']);
            $foundClass = SchoolClass::where('school_id',$school->id)->where('name','like',"%{$cname}%")->first();
            if (!$foundClass) {
                $errors[] = "Row {$rowNo}: Class not found - {$cname}";
                continue;
            }
            
            // Generate student_id with proper format: {school_code}{class_2digits}{4digit_counter}
            $studentId = Student::generateStudentId($school->id, $foundClass->numeric_value);
            
            $studentData = [
                'student_id' => $studentId,
                'student_name_en' => $assoc['student_name_en'],
                'student_name_bn' => $assoc['student_name_bn'] ?? null,
                'date_of_birth' => $dob,
                'gender' => $assoc['gender'] ?? null,
                'father_name' => $assoc['father_name'] ?? null,
                'mother_name' => $assoc['mother_name'] ?? null,
                'father_name_bn' => $assoc['father_name_bn'] ?? ($assoc['father_name'] ?? null),
                'mother_name_bn' => $assoc['mother_name_bn'] ?? ($assoc['mother_name'] ?? null),
                'guardian_phone' => $assoc['guardian_phone'] ?? null,
                'address' => $assoc['address'] ?? null,
                'blood_group' => $assoc['blood_group'] ?? null,
                'religion' => $assoc['religion'] ?? null,
                'admission_date' => $admission_date,
                'status' => $assoc['status'] ?? 'active',
                'school_id' => $school->id,
                'class_id' => null,
            ];

            try {
                $student = Student::create($studentData);
                
                // Add to imported students list
                $importedStudents[] = [
                    'roll' => $assoc['enroll_roll_no'] ?? null,
                    'name_en' => $assoc['student_name_en'],
                    'name_bn' => $assoc['student_name_bn'] ?? null,
                    'class' => $assoc['enroll_class_name'] ?? $assoc['enroll_class_id'] ?? null,
                    'section' => $assoc['enroll_section_name'] ?? $assoc['enroll_section_id'] ?? null,
                    'year' => $assoc['enroll_academic_year'] ?? null,
                    'dob' => $dob,
                    'gender' => $assoc['gender'] ?? null,
                    'religion' => $assoc['religion'] ?? null,
                ];
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNo}: failed to create student - {$e->getMessage()}";
                continue;
            }

            // Enrollment is now required since validation passed
            $enYear = $assoc['enroll_academic_year'];
            $enClass = $foundClass->id; // Already found above
            $enSection = null; $enGroup = null; $enRoll = intval($assoc['enroll_roll_no']);
            
            // Get section by name (required)
            $sname = trim($assoc['enroll_section_name']);
            $foundSection = Section::where('school_id',$school->id)->where('name','like',"%{$sname}%")->first();
            if ($foundSection) { 
                $enSection = $foundSection->id; 
            } else {
                $errors[] = "Row {$rowNo}: Section not found - {$sname}";
                continue;
            }
            
            // Get group by name (optional)
            if (!empty($assoc['enroll_group_name'])) {
                $gname = trim($assoc['enroll_group_name']);
                $foundGroup = Group::where('school_id',$school->id)->where('name','like',"%{$gname}%")->first();
                if ($foundGroup) { $enGroup = $foundGroup->id; }
            }

            if ($enYear && $enClass && $enSection && $enRoll) {
                $class = SchoolClass::find($enClass);
                if ($class && !$class->usesGroups()) { $enGroup = null; }
                // Map numeric year to AcademicYear model
                $yearNumber = intval($enYear);
                $yearModel = AcademicYear::firstOrCreate([
                    'school_id'=>$school->id,
                    'name'=>(string)$yearNumber,
                ],[
                    'start_date'=>Carbon::create($yearNumber,1,1),
                    'end_date'=>Carbon::create($yearNumber,12,31),
                    'is_current'=>false,
                ]);
                // check duplicate roll for same school/class/year/section/group
                $dupQuery = StudentEnrollment::where('school_id',$school->id)
                    ->where('academic_year_id', $yearModel->id)
                    ->where('class_id', $enClass)
                    ->where('section_id', $enSection);
                if ($enGroup) { $dupQuery->where('group_id', $enGroup); } else { $dupQuery->whereNull('group_id'); }
                $dupQuery->where('roll_no', $enRoll);
                if ($dupQuery->exists()) {
                    $errors[] = "Row {$rowNo}: Enrollment failed - roll {$enRoll} already exists for class/section/group in {$enYear}";
                } else {
                    try {
                        StudentEnrollment::create([
                            'student_id' => $student->id,
                            'school_id' => $school->id,
                            'academic_year_id' => $yearModel->id,
                            'class_id' => $enClass,
                            'section_id' => $enSection,
                            'group_id' => $enGroup ?: null,
                            'roll_no' => $enRoll,
                            'status' => 'active'
                        ]);
                    } catch (\Throwable $e) {
                        $errors[] = "Row {$rowNo}: enrollment failed - {$e->getMessage()}";
                    }
                }
            } else {
                $errors[] = "Row {$rowNo}: Missing required enrollment data (year/class/section/roll)";
            }

            $success++;
        }

        $report = ['success'=>$success, 'errors'=>$errors, 'imported_students'=>$importedStudents];
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($report);
        }
        return back()->with('bulk_import_report', $report);
    }

    /**
     * Enqueue bulk import job: stores uploaded file and dispatches background job.
     */
    public function bulkEnqueue(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $request->validate(['file'=>['required','file']]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?? 'csv');
        $importId = Str::uuid()->toString();
        $path = $file->storeAs('bulk_imports', $importId . '.' . $ext);

        // queue the job
        try {
            ProcessStudentBulkImport::dispatch($path, $school->id, $importId);
            $statusKey = "bulk_import:{$importId}:status";
            Cache::put($statusKey, ['status'=>'queued','processed'=>0,'total'=>0], 3600);
            return response()->json(['id'=>$importId,'message'=>'queued']);
        } catch (\Throwable $e) {
            Log::error('Bulk enqueue failed: '.$e->getMessage());
            return response()->json(['error'=>'enqueue failed','message'=>$e->getMessage()], 500);
        }
    }

    /**
     * Download Excel/CSV template for bulk student import
     */
    public function bulkTemplate(School $school)
    {
        $this->authorizePrincipal($school);
        // Prefer Excel download if package available, else fallback CSV stream
        if (class_exists('Maatwebsite\\Excel\\Facades\\Excel')) {
            return Excel::download(new StudentBulkTemplateExport(), 'students-template.xlsx');
        }
        $rows = [
            ['student_name_bn','student_name_en','date_of_birth','gender','father_name','mother_name','guardian_phone','address','admission_date','status','enroll_academic_year','enroll_class_id','enroll_section_id','enroll_group_id','enroll_roll_no'],
            ['রশিদ','Rashid','2010-05-13','male','আব্বা','আম্মা','01700000000','Dhaka','2023-01-15','active','2023','1','1','','10']
        ];
        $fh = fopen('php://temp','w');
        foreach($rows as $r){ fputcsv($fh,$r); }
        rewind($fh); $csv = stream_get_contents($fh); fclose($fh);
        return response($csv,200,[
            'Content-Type'=>'text/csv; charset=UTF-8',
            'Content-Disposition'=>'attachment; filename="students-template.csv"'
        ]);
    }

    /**
     * Return job status/progress from cache
     */
    public function bulkStatus(School $school, $id)
    {
        $this->authorizePrincipal($school);
        $statusKey = "bulk_import:{$id}:status";
        $reportKey = "bulk_import:{$id}:report";
        $status = Cache::get($statusKey);
        $report = Cache::get($reportKey);
        $processed = $status['processed'] ?? 0;
        $total = max(1, $status['total'] ?? 1); // avoid div by zero
        $progressPct = $total ? round(($processed / $total) * 100, 2) : 0;
        $finished = ($status['status'] ?? null) === 'finished';
        return response()->json([
            'status' => $status['status'] ?? null,
            'processed' => $processed,
            'total' => $status['total'] ?? 0,
            'progress' => $progressPct,
            'success' => $report['success'] ?? 0,
            'errors' => $report['errors'] ?? [],
            'report_available' => !empty($report['report_path']),
            'finished' => $finished,
        ]);
    }

    /**
     * Download failure report CSV if exists
     */
    public function bulkReport(School $school, $id)
    {
        $this->authorizePrincipal($school);
        $reportPath = "bulk_reports/{$id}.csv";
        if (!Storage::exists($reportPath)) {
            return response()->json(['error'=>'report not found'],404);
        }
        return Storage::download($reportPath, "bulk-report-{$id}.csv");
    }

    // Print Controls page
    public function printControls(School $school, Request $request)
    {
        $this->authorizePrincipal($school);

        $years = AcademicYear::forSchool($school->id)->orderByDesc('start_date')->get();
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $selectedYearId = (int)($request->query('year_id') ?: ($currentYear->id ?? 0));
        $selectedYear = $years->firstWhere('id', $selectedYearId);

        // Load classes and sections separately for better control
        $classes = SchoolClass::forSchool($school->id)->ordered()->get();
        $sections = Section::forSchool($school->id)->with('class')->get();

        // Default selected columns (Photo optional)
        $defaultCols = ['serial','student_id','name_bn','father_bn','class','section','roll','group','mobile','status','subjects'];
        $cols = $request->query('cols', $defaultCols);

        return view('principal.institute.students.print-controls', [
            'school' => $school,
            'years' => $years,
            'currentYear' => $currentYear,
            'selectedYear' => $selectedYear,
            'selectedYearId' => $selectedYearId,
            'classes' => $classes,
            'sections' => $sections,
            'cols' => $cols,
            'lang' => $request->query('lang','bn'),
        ]);
    }

    // Print Preview page
    public function printPreview(School $school, Request $request)
    {
        $this->authorizePrincipal($school);

        $lang = $request->query('lang','bn');

        $years = AcademicYear::forSchool($school->id)->orderByDesc('start_date')->get();
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $selectedYearId = (int)($request->query('year_id') ?: ($currentYear->id ?? 0));
        $selectedYear = $years->firstWhere('id', $selectedYearId);
        $yearLabel = $selectedYear ? $selectedYear->name : ($currentYear ? $currentYear->name : '');

        // Filters
        $q = $request->get('q');
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $groupId = $request->get('group_id');
        $status = $request->get('status');
        $gender = $request->get('gender');
        $religion = $request->get('religion');
        $village = $request->get('village');

        // Sorting parameters
        $sortBy = $request->get('sort_by', 'student_id');
        $sortOrder = $request->get('sort_order', 'asc');

        $limit = (int)$request->query('limit', 1000);
        if ($limit < 1) $limit = 1;
        if ($limit > 5000) $limit = 5000;

        $studentsQuery = Student::where('students.school_id', $school->id)
            ->when($q, function($x) use ($q){
                $x->where(function($inner) use ($q){
                    $inner->where('students.student_name_en','like',"%$q%")
                          ->orWhere('students.student_name_bn','like',"%$q%")
                          ->orWhere('students.student_id','like',"%$q%");
                });
            })
            ->when($status, function($x) use ($status){
                $x->where('students.status', $status);
            })
            ->when($gender, function($x) use ($gender){
                $x->where('students.gender', $gender);
            })
            ->when($religion, function($x) use ($religion){
                $x->where('students.religion', $religion);
            })
            ->when($village, function($x) use ($village){
                $x->where('students.present_village', $village);
            })
            ->whereHas('enrollments', function($en) use ($selectedYearId, $classId, $sectionId, $groupId){
                if ($selectedYearId) { $en->where('academic_year_id', $selectedYearId); }
                else { $en->whereRaw('1=0'); }
                if ($classId) { $en->where('class_id', $classId); }
                if ($sectionId) { $en->where('section_id', $sectionId); }
                if ($groupId) { $en->where('group_id', $groupId); }
            })
            ->with(['enrollments' => function($en) use ($selectedYearId){
                if ($selectedYearId) { $en->where('academic_year_id', $selectedYearId); }
                $en->with(['class','section','group','subjects.subject','academicYear']);
            }]);

        // Apply sorting based on sort_by parameter
        switch ($sortBy) {
            case 'student_id':
                $studentsQuery->orderBy('students.student_id', $sortOrder);
                break;
            case 'class':
                $studentsQuery->join('student_enrollments', function($join) use ($selectedYearId) {
                    $join->on('students.id', '=', 'student_enrollments.student_id')
                         ->where('student_enrollments.academic_year_id', $selectedYearId);
                })
                ->join('classes', 'student_enrollments.class_id', '=', 'classes.id')
                ->orderBy('classes.numeric_value', $sortOrder)
                ->select('students.*');
                break;
            case 'section':
                $studentsQuery->join('student_enrollments', function($join) use ($selectedYearId) {
                    $join->on('students.id', '=', 'student_enrollments.student_id')
                         ->where('student_enrollments.academic_year_id', $selectedYearId);
                })
                ->join('sections', 'student_enrollments.section_id', '=', 'sections.id')
                ->orderBy('sections.name', $sortOrder)
                ->select('students.*');
                break;
            case 'roll':
                $studentsQuery->join('student_enrollments', function($join) use ($selectedYearId) {
                    $join->on('students.id', '=', 'student_enrollments.student_id')
                         ->where('student_enrollments.academic_year_id', $selectedYearId);
                })
                ->orderBy('student_enrollments.roll_no', $sortOrder)
                ->select('students.*');
                break;
            case 'village':
                $studentsQuery->orderBy('students.present_village', $sortOrder);
                break;
            default:
                $studentsQuery->orderBy('students.id', 'desc');
        }

        $students = $studentsQuery->take($limit)->get();

        // Columns selection
        $defaultCols = ['serial','student_id','name_bn','father_bn','class','section','roll','group','mobile','status','subjects'];
        $cols = $request->query('cols', $defaultCols);

        // Labels per language
        $labelsBn = [
            'serial' => 'ক্রমিক',
            'student_id' => 'আইডি নং',
            'name_bn' => 'নাম',
            'name_en' => 'নাম',
            'father_bn' => 'পিতার নাম',
            'father_en' => 'পিতার নাম',
            'mother_bn' => 'মাতার নাম',
            'mother_en' => 'মাতার নাম',
            'class' => 'শ্রেণি',
            'section' => 'শাখা',
            'roll' => 'রোল',
            'group' => 'গ্রুপ',
            'mobile' => 'মোবাইল নং',
            'status' => 'স্ট্যাটাস',
            'photo' => 'ছবি',
            'subjects' => 'বিষয়সমূহ',
            'date_of_birth' => 'জন্ম তারিখ',
            'gender' => 'লিঙ্গ',
            'religion' => 'ধর্ম',
            'blood_group' => 'রক্তের গ্রুপ',
            'guardian_name_bn' => 'অভিভাবকের নাম',
            'guardian_name_en' => 'অভিভাবকের নাম',
            'guardian_relation' => 'অভিভাবকের সম্পর্ক',
            'present_village' => 'বর্তমান গ্রাম',
            'present_para_moholla' => 'বর্তমান পাড়া/মহল্লা',
            'present_post_office' => 'বর্তমান ডাকঘর',
            'present_upazilla' => 'বর্তমান উপজেলা',
            'present_district' => 'বর্তমান জেলা',
            'permanent_village' => 'স্থায়ী গ্রাম',
            'permanent_para_moholla' => 'স্থায়ী পাড়া/মহল্লা',
            'permanent_post_office' => 'স্থায়ী ডাকঘর',
            'permanent_upazilla' => 'স্থায়ী উপজেলা',
            'permanent_district' => 'স্থায়ী জেলা',
            'admission_date' => 'ভর্তির তারিখ',
            'previous_school' => 'পূর্ববর্তী স্কুল',
            'pass_year' => 'পাসের বছর',
            'previous_result' => 'পূর্ববর্তী ফলাফল',
            'signature' => 'স্বাক্ষর',
        ];
        $labelsEn = [
            'serial' => 'Serial',
            'student_id' => 'Student ID',
            'name_bn' => 'Name',
            'name_en' => 'Name',
            'father_bn' => "Father's Name",
            'father_en' => "Father's Name",
            'mother_bn' => "Mother's Name",
            'mother_en' => "Mother's Name",
            'class' => 'Class',
            'section' => 'Section',
            'roll' => 'Roll',
            'group' => 'Group',
            'mobile' => 'Mobile',
            'status' => 'Status',
            'photo' => 'Photo',
            'subjects' => 'Subjects',
            'date_of_birth' => 'Date of Birth',
            'gender' => 'Gender',
            'religion' => 'Religion',
            'blood_group' => 'Blood Group',
            'guardian_name_bn' => 'Guardian Name',
            'guardian_name_en' => 'Guardian Name',
            'guardian_relation' => 'Guardian Relation',
            'present_village' => 'Present Village',
            'present_para_moholla' => 'Present Para/Moholla',
            'present_post_office' => 'Present Post Office',
            'present_upazilla' => 'Present Upazilla',
            'present_district' => 'Present District',
            'permanent_village' => 'Permanent Village',
            'permanent_para_moholla' => 'Permanent Para/Moholla',
            'permanent_post_office' => 'Permanent Post Office',
            'permanent_upazilla' => 'Permanent Upazilla',
            'permanent_district' => 'Permanent District',
            'admission_date' => 'Admission Date',
            'previous_school' => 'Previous School',
            'pass_year' => 'Pass Year',
            'previous_result' => 'Previous Result',
            'signature' => 'Signature',
        ];
        $labels = $lang === 'bn' ? $labelsBn : $labelsEn;

        return view('principal.institute.students.print-preview', [
            'school' => $school,
            'students' => $students,
            'years' => $years,
            'currentYear' => $currentYear,
            'selectedYear' => $selectedYear,
            'selectedYearId' => $selectedYearId,
            'yearLabel' => $yearLabel,
            'cols' => $cols,
            'labels' => $labels,
            'lang' => $lang,
        ]);
    }
}
