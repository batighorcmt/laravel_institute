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

        $students = Student::forSchool($school->id)
            ->when($q, function($x) use ($q){
                $x->where(function($inner) use ($q){
                    $inner->where('student_name_en','like',"%$q%")
                          ->orWhere('student_name_bn','like',"%$q%")
                          ->orWhere('student_id','like',"%$q%");
                });
            })
            ->whereHas('enrollments', function($en) use ($selectedYearId){
                if ($selectedYearId) { $en->where('academic_year_id', $selectedYearId); }
                else { $en->whereRaw('1=0'); }
            })
            ->with(['enrollments' => function($en) use ($selectedYearId){
                if ($selectedYearId) { $en->where('academic_year_id', $selectedYearId); }
                $en->with(['class','section','group','subjects.subject','academicYear']);
            }])
            ->orderBy('id','desc')->paginate(20)->withQueryString();

        return view('principal.institute.students.index',[
            'school'=>$school,
            'students'=>$students,
            'q'=>$q,
            'years'=>$years,
            'currentYear'=>$currentYear,
            'selectedYear'=>$selectedYear,
            'selectedYearId'=>$selectedYearId,
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
            'religion'=>['nullable','in:islam,hindu,buddhist,christian,other'],
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
        $data['student_id'] = Student::generateStudentId($school->id, $classNumeric);

        $student = Student::create($data);

        // Inline enrollment (optional)
        $enrollData = $request->validate([
            'enroll_academic_year_id'=>['nullable', Rule::exists('academic_years','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_class_id'=>['nullable', Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_section_id'=>['nullable', Rule::exists('sections','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_group_id'=>['nullable', Rule::exists('groups','id')->where(fn($q)=>$q->where('school_id',$school->id))],
            'enroll_roll_no'=>['nullable','integer','min:1']
        ]);

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

    public function show(School $school, Student $student)
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

        return view('principal.institute.students.show',compact(
            'school','student','enrollments','memberships','allTeams','currentYear','activeEnrollment','currentSubjects','totalYears','timeline','years'
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
            'religion'=>['nullable','in:islam,hindu,buddhist,christian,other'],
                            'admission_date'=>['required','date'],
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
        return redirect()->route('principal.institute.students.show',[$school,$student])->with('success','শিক্ষার্থী আপডেট হয়েছে');
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

        foreach ($rows as $cols) {
            $rowNo++;
            // skip empty rows
            $allBlank = true; foreach ($cols as $c) { if (trim((string)$c) !== '') { $allBlank = false; break; } }
            if ($allBlank) continue;
            $assoc = [];
            foreach ($header as $i => $colName) {
                $assoc[$colName] = isset($cols[$i]) ? trim((string)$cols[$i]) : null;
            }

            // Minimal required fields now: student_name_en, enroll_academic_year, enroll_roll_no, class (id or name), status optional
            $validator = \Illuminate\Support\Facades\Validator::make($assoc, [
                'student_name_en' => ['required','string','max:150'],
                'enroll_academic_year' => ['required','numeric'],
                'enroll_roll_no' => ['required','numeric'],
                'status' => ['nullable','in:active,inactive,graduated,transferred'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNo}: validation failed - " . implode(', ', $validator->errors()->all());
                continue;
            }

            // Optional date parsing (ignore if invalid or empty)
            $dob = null; if (!empty($assoc['date_of_birth'])) { try { $dob = Carbon::parse($assoc['date_of_birth'])->toDateString(); } catch (\Throwable $e) { try { $dob = Carbon::createFromFormat('d/m/Y', $assoc['date_of_birth'])->toDateString(); } catch (\Throwable $e) { $dob = null; } } }
            $admission_date = null; if (!empty($assoc['admission_date'])) { try { $admission_date = Carbon::parse($assoc['admission_date'])->toDateString(); } catch (\Throwable $e) { try { $admission_date = Carbon::createFromFormat('d/m/Y', $assoc['admission_date'])->toDateString(); } catch (\Throwable $e) { $admission_date = null; } } }

            $studentData = [
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
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNo}: failed to create student - {$e->getMessage()}";
                continue;
            }

            // optional enrollment
            $enYear = $assoc['enroll_academic_year'] ?? null;
            // support ID or name for class/section/group
            $enClass = null; $enSection = null; $enGroup = null; $enRoll = null;
            if (!empty($assoc['enroll_class_id']) && is_numeric($assoc['enroll_class_id'])) { $enClass = intval($assoc['enroll_class_id']); }
            if (empty($enClass) && !empty($assoc['enroll_class_name'])) {
                $cname = trim($assoc['enroll_class_name']);
                $foundClass = SchoolClass::where('school_id',$school->id)->where('name','like',"%{$cname}%")->first();
                if ($foundClass) { $enClass = $foundClass->id; }
            }
            if (!empty($assoc['enroll_section_id']) && is_numeric($assoc['enroll_section_id'])) { $enSection = intval($assoc['enroll_section_id']); }
            if (empty($enSection) && !empty($assoc['enroll_section_name'])) {
                $sname = trim($assoc['enroll_section_name']);
                $foundSection = Section::where('school_id',$school->id)->where('name','like',"%{$sname}%")->first();
                if ($foundSection) { $enSection = $foundSection->id; }
            }
            if (!empty($assoc['enroll_group_id']) && is_numeric($assoc['enroll_group_id'])) { $enGroup = intval($assoc['enroll_group_id']); }
            if (empty($enGroup) && !empty($assoc['enroll_group_name'])) {
                $gname = trim($assoc['enroll_group_name']);
                $foundGroup = Group::where('school_id',$school->id)->where('name','like',"%{$gname}%")->first();
                if ($foundGroup) { $enGroup = $foundGroup->id; }
            }
            if (!empty($assoc['enroll_roll_no']) && is_numeric($assoc['enroll_roll_no'])) { $enRoll = intval($assoc['enroll_roll_no']); }

            if ($enYear && $enClass && $enRoll) {
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
                    ->where('class_id', $enClass);
                if ($enSection) { $dupQuery->where('section_id', $enSection); } else { $dupQuery->whereNull('section_id'); }
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
                            'section_id' => $enSection ?: null,
                            'group_id' => $enGroup ?: null,
                            'roll_no' => $enRoll,
                            'status' => 'active'
                        ]);
                    } catch (\Throwable $e) {
                        $errors[] = "Row {$rowNo}: enrollment failed - {$e->getMessage()}";
                    }
                }
            }

            $success++;
        }

        $report = ['success'=>$success, 'errors'=>$errors];
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
}
