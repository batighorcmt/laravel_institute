<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AdmissionExam;
use App\Models\AdmissionExamSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdmissionExamController extends Controller
{
    protected function authorizePrincipal(School $school): void
    {
        /** @var \App\Models\User $u */ $u = Auth::user();
        abort_unless($u && $u->isPrincipal($school->id), 403);
    }

    public function index(School $school)
    {
        $this->authorizePrincipal($school);
        $exams = AdmissionExam::where('school_id',$school->id)->orderByDesc('id')->paginate(20);
        return view('principal.institute.admissions.exams.index', compact('school','exams'));
    }

    public function create(School $school)
    {
        $this->authorizePrincipal($school);
        $classOptions = [];
        if ($school->admission_academic_year_id) {
            $classOptions = \App\Models\AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
                ->pluck('class_code')
                ->unique()
                ->sort()
                ->values();
        }
        return view('principal.institute.admissions.exams.create', compact('school','classOptions'));
    }

    public function store(School $school, Request $request)
    {
        $this->authorizePrincipal($school);
        $data = $request->validate([
            'class_name'=>['required','string','max:50'],
            'name'=>['required','string','max:150'],
            'type'=>['required', Rule::in(['subject','overall'])],
            'overall_pass_mark'=>['nullable','integer','min:0'],
            'exam_date'=>['nullable','date'],
        ]);
        if ($data['type']==='overall') {
            $request->validate(['overall_pass_mark'=>['required','integer','min:0']]);
        }
        $data['school_id']=$school->id;
        $exam = AdmissionExam::create($data);

        // Subjects arrays
        $subjectNames = $request->input('subject_name', []);
        $fullMarks = $request->input('full_mark', []);
        $passMarks = $request->input('pass_mark', []);
        foreach ($subjectNames as $idx => $sName) {
            $sName = trim((string)$sName);
            if ($sName==='') { continue; }
            $fm = (int)($fullMarks[$idx] ?? 0);
            $pm = $data['type']==='subject' ? (int)($passMarks[$idx] ?? 0) : null;
            if ($fm<=0) { continue; }
            if ($pm !== null && $pm > $fm) { $pm = $fm; }
            AdmissionExamSubject::create([
                'exam_id'=>$exam->id,
                'subject_name'=>$sName,
                'full_mark'=>$fm,
                'pass_mark'=>$pm,
                'display_order'=>$idx,
            ]);
        }
        return redirect()->route('principal.institute.admissions.exams.index',$school)->with('success','ভর্তি পরীক্ষা তৈরি হয়েছে');
    }

    public function edit(School $school, AdmissionExam $exam)
    {
        $this->authorizePrincipal($school);
        abort_unless($exam->school_id===$school->id,404);
        $exam->load('subjects');
        $classOptions = [];
        if ($school->admission_academic_year_id) {
            $classOptions = \App\Models\AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
                ->pluck('class_code')
                ->unique()
                ->sort()
                ->values();
        }
        return view('principal.institute.admissions.exams.edit', compact('school','exam','classOptions'));
    }

    public function update(School $school, AdmissionExam $exam, Request $request)
    {
        $this->authorizePrincipal($school);
        abort_unless($exam->school_id===$school->id,404);
        $data = $request->validate([
            'class_name'=>['required','string','max:50'],
            'name'=>['required','string','max:150'],
            'type'=>['required', Rule::in(['subject','overall'])],
            'overall_pass_mark'=>['nullable','integer','min:0'],
            'exam_date'=>['nullable','date'],
            'status'=>['nullable', Rule::in(['draft','scheduled','completed'])]
        ]);
        if ($data['type']==='overall') {
            $request->validate(['overall_pass_mark'=>['required','integer','min:0']]);
        } else {
            $data['overall_pass_mark'] = null; // not used
        }
        $exam->update($data);
        // Sync subjects: simple replace for now
        $exam->subjects()->delete();
        $subjectNames = $request->input('subject_name', []);
        $fullMarks = $request->input('full_mark', []);
        $passMarks = $request->input('pass_mark', []);
        foreach ($subjectNames as $idx => $sName) {
            $sName = trim((string)$sName);
            if ($sName==='') { continue; }
            $fm = (int)($fullMarks[$idx] ?? 0);
            $pm = $data['type']==='subject' ? (int)($passMarks[$idx] ?? 0) : null;
            if ($fm<=0) { continue; }
            if ($pm !== null && $pm > $fm) { $pm = $fm; }
            AdmissionExamSubject::create([
                'exam_id'=>$exam->id,
                'subject_name'=>$sName,
                'full_mark'=>$fm,
                'pass_mark'=>$pm,
                'display_order'=>$idx,
            ]);
        }
        return redirect()->route('principal.institute.admissions.exams.index',$school)->with('success','ভর্তি পরীক্ষা আপডেট হয়েছে');
    }

    public function destroy(School $school, AdmissionExam $exam)
    {
        $this->authorizePrincipal($school);
        abort_unless($exam->school_id===$school->id,404);
        $exam->delete();
        return back()->with('success','পরীক্ষা মুছে ফেলা হয়েছে');
    }

    // Marks entry form
    public function marks(School $school, AdmissionExam $exam)
    {
        $this->authorizePrincipal($school); abort_unless($exam->school_id===$school->id,404);
        $exam->load('subjects');
        // Accepted applications only (with admission_roll_no) for this school
        $apps = \App\Models\AdmissionApplication::where('school_id',$school->id)
            ->whereNotNull('accepted_at')
            ->where('status','accepted')
            ->whereNotNull('admission_roll_no')
            ->when($exam->class_name, function($q) use ($exam){ $q->where('class_name',$exam->class_name); })
            ->orderBy('admission_roll_no')
            ->limit(500)
            ->get();
        // Existing marks map
        $marks = \App\Models\AdmissionExamMark::where('exam_id',$exam->id)->get()->groupBy('application_id');
        return view('principal.institute.admissions.exams.marks', compact('school','exam','apps','marks'));
    }

    // Store marks
    public function marksStore(School $school, AdmissionExam $exam, Request $request)
    {
        $this->authorizePrincipal($school); abort_unless($exam->school_id===$school->id,404);
        $exam->load('subjects');
        if ($exam->type === 'subject') {
            $submitted = $request->input('marks', []); // [appId][subjectId] => value
            foreach ($submitted as $appId => $subArray) {
                foreach ($subArray as $subjectId => $val) {
                    if ($val === '' || $val === null) { continue; }
                    $val = (int)$val; if ($val < 0) { $val = 0; }
                    $subject = $exam->subjects->firstWhere('id',(int)$subjectId);
                    if (!$subject) { continue; }
                    if ($val > (int)$subject->full_mark) { $val = (int)$subject->full_mark; }
                    \App\Models\AdmissionExamMark::updateOrCreate([
                        'exam_id'=>$exam->id,
                        'application_id'=>(int)$appId,
                        'subject_id'=>(int)$subjectId,
                    ], [
                        'obtained_mark'=>$val
                    ]);
                }
            }
        } else { // overall type
            $submitted = $request->input('overall', []); // [appId] => total
            $fullMark = (int)($exam->overall_pass_mark ?? 0); // Using pass mark as max? (No total stored in exam) - accept any positive if not set
            foreach ($submitted as $appId => $val) {
                if ($val === '' || $val === null) { continue; }
                $val = (int)$val; if ($val < 0) { $val = 0; }
                if ($fullMark > 0 && $val > $fullMark*2) { /* arbitrary cap */ $val = $fullMark*2; }
                \App\Models\AdmissionExamMark::updateOrCreate([
                    'exam_id'=>$exam->id,
                    'application_id'=>(int)$appId,
                    'subject_id'=>null,
                ], [
                    'obtained_mark'=>$val
                ]);
            }
        }
        // Compute / update results after marks save
        $this->computeResults($exam, $school);
        return back()->with('success','নম্বর সেভ হয়েছে');
    }

    protected function computeResults(AdmissionExam $exam, School $school): void
    {
        $exam->load('subjects');
        $acceptedApps = \App\Models\AdmissionApplication::where('school_id',$school->id)
            ->whereNotNull('accepted_at')
            ->where('status','accepted')
            ->whereNotNull('admission_roll_no')
            ->when($exam->class_name, function($q) use ($exam){ $q->where('class_name',$exam->class_name); })
            ->pluck('id');
        if ($exam->type === 'subject') {
            $subjects = $exam->subjects;
            $marksByApp = \App\Models\AdmissionExamMark::where('exam_id',$exam->id)->get()->groupBy('application_id');
            foreach ($acceptedApps as $appId) {
                $total = 0; $failed = 0;
                foreach ($subjects as $sub) {
                    $m = optional($marksByApp->get($appId))->firstWhere('subject_id',$sub->id);
                    $obt = $m?->obtained_mark ?? 0;
                    $total += $obt;
                    if ($sub->pass_mark !== null && $obt < $sub->pass_mark) { $failed++; }
                }
                \App\Models\AdmissionExamResult::updateOrCreate([
                    'exam_id'=>$exam->id,
                    'application_id'=>$appId,
                ],[
                    'total_obtained'=>$total,
                    'failed_subjects_count'=>$failed,
                    'is_pass'=>$failed===0
                ]);
            }
        } else { // overall type
            $marks = \App\Models\AdmissionExamMark::where('exam_id',$exam->id)->whereNull('subject_id')->get()->keyBy('application_id');
            $passMark = (int)($exam->overall_pass_mark ?? 0);
            foreach ($acceptedApps as $appId) {
                $obt = (int)($marks[$appId]->obtained_mark ?? 0);
                $failed = $obt < $passMark ? 1 : 0;
                \App\Models\AdmissionExamResult::updateOrCreate([
                    'exam_id'=>$exam->id,
                    'application_id'=>$appId,
                ],[
                    'total_obtained'=>$obt,
                    'failed_subjects_count'=>$failed,
                    'is_pass'=>$failed===0
                ]);
            }
        }
    }

    public function results(School $school, AdmissionExam $exam)
    {
        $this->authorizePrincipal($school); abort_unless($exam->school_id===$school->id,404);
        // Ensure results exist (compute on demand if none)
        if ($exam->results()->count() === 0) { $this->computeResults($exam,$school); }
        $exam->load(['subjects','results.application']);
        
        // Get all results and calculate failed subjects dynamically
        $allResults = $exam->results()->with(['application'])->get();
        
        if($exam->type === 'subject') {
            // Calculate failed subjects count dynamically for subject-based exams
            foreach($allResults as $result) {
                $failedCount = 0;
                $marks = $exam->marks()
                    ->where('application_id', $result->application_id)
                    ->get();
                
                foreach($marks as $mark) {
                    $subject = $exam->subjects()->find($mark->subject_id);
                    if($subject && $mark->obtained_mark < $subject->pass_mark) {
                        $failedCount++;
                    }
                }
                $result->failed_subjects_count = $failedCount;
            }
        }
        
        // Sort by total_obtained DESC, then by admission_roll_no ASC for ties
        $sortedResults = $allResults->sort(function($a, $b) {
            // First compare by total marks (descending)
            $marksDiff = $b->total_obtained - $a->total_obtained;
            if ($marksDiff != 0) {
                return $marksDiff;
            }
            // If marks are equal, compare by roll number (ascending)
            return $a->application->admission_roll_no <=> $b->application->admission_roll_no;
        })->values();
        
        // Assign merit position
        $position = 1;
        foreach($sortedResults as $result) {
            $result->merit_position = $position++;
        }
        
        // Paginate manually
        $perPage = 200;
        $currentPage = request()->get('page', 1);
        $results = new \Illuminate\Pagination\LengthAwarePaginator(
            $sortedResults->forPage($currentPage, $perPage),
            $sortedResults->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        return view('principal.institute.admissions.exams.results', compact('school','exam','results'));
    }

    public function resultsPrint(School $school, AdmissionExam $exam)
    {
        $this->authorizePrincipal($school); abort_unless($exam->school_id===$school->id,404);
        // Ensure results exist (compute on demand if none)
        if ($exam->results()->count() === 0) { $this->computeResults($exam,$school); }
        $exam->load(['subjects','results.application']);
        
        // Get all results and calculate failed subjects dynamically
        $allResults = $exam->results()->with(['application'])->get();
        
        if($exam->type === 'subject') {
            // Calculate failed subjects count dynamically for subject-based exams
            foreach($allResults as $result) {
                $failedCount = 0;
                $marks = $exam->marks()
                    ->where('application_id', $result->application_id)
                    ->get();
                
                foreach($marks as $mark) {
                    $subject = $exam->subjects()->find($mark->subject_id);
                    if($subject && $mark->obtained_mark < $subject->pass_mark) {
                        $failedCount++;
                    }
                }
                $result->failed_subjects_count = $failedCount;
            }
        }
        
        // Sort by total_obtained DESC, then by admission_roll_no ASC for ties
        $sortedResults = $allResults->sort(function($a, $b) {
            // First compare by total marks (descending)
            $marksDiff = $b->total_obtained - $a->total_obtained;
            if ($marksDiff != 0) {
                return $marksDiff;
            }
            // If marks are equal, compare by roll number (ascending)
            return $a->application->admission_roll_no <=> $b->application->admission_roll_no;
        })->values();
        
        // Assign merit position
        $position = 1;
        foreach($sortedResults as $result) {
            $result->merit_position = $position++;
        }
        
        $results = $sortedResults; // No pagination for print view
        
        return view('principal.institute.admissions.exams.results_print', compact('school','exam','results'));
    }

    public function sendResultsSms(Request $request, School $school, AdmissionExam $exam)
    {
        $this->authorizePrincipal($school); 
        abort_unless($exam->school_id===$school->id,404);
        
        // Validate request
        $request->validate([
            'message_template' => 'required|string|max:1000',
            'only_pass' => 'nullable|boolean',
            'only_fail' => 'nullable|boolean',
        ]);
        
        // Ensure results exist
        if ($exam->results()->count() === 0) {
            return back()->with('error', 'কোন ফলাফল পাওয়া যায়নি। প্রথমে নম্বর এন্ট্রি করুন।');
        }
        
        $messageTemplate = $request->input('message_template');
        $onlyPass = $request->boolean('only_pass');
        $onlyFail = $request->boolean('only_fail');
        
        // Load results with filters
        $resultsQuery = $exam->results()->with('application');
        
        if($onlyPass) {
            $resultsQuery->where('is_pass', true);
        } elseif($onlyFail) {
            $resultsQuery->where('is_pass', false);
        }
        
        $results = $resultsQuery->get();
        
        if($results->isEmpty()) {
            return back()->with('error', 'নির্বাচিত ফিল্টারে কোন ফলাফল নেই।');
        }
        
        // Calculate merit positions if not already done
        $sortedResults = $results->sort(function($a, $b) {
            $marksDiff = $b->total_obtained - $a->total_obtained;
            if ($marksDiff != 0) return $marksDiff;
            return $a->application->admission_roll_no <=> $b->application->admission_roll_no;
        })->values();
        
        $position = 1;
        foreach($sortedResults as $result) {
            $result->merit_position = $position++;
        }
        
        $exam->load(['subjects']);
        
        $sentCount = 0;
        $failedCount = 0;
        $errors = [];
        $processedMobiles = []; // Track processed mobile numbers to avoid duplicates
        
        foreach($sortedResults as $result) {
            $app = $result->application;
            
            // Check if guardian mobile exists
            if(!$app || !$app->mobile) {
                $failedCount++;
                $errors[] = "Roll {$app?->admission_roll_no}: মোবাইল নম্বর নেই";
                continue;
            }
            
            // Format mobile number
            $mobile = preg_replace('/[^0-9]/', '', $app->mobile);
            if(strlen($mobile) === 13 && substr($mobile, 0, 3) === '880') {
                $mobile = '0'.substr($mobile, 3);
            }
            
            if(strlen($mobile) !== 11) {
                $failedCount++;
                $errors[] = "Roll {$app->admission_roll_no}: অবৈধ মোবাইল নম্বর ({$app->mobile})";
                continue;
            }
            
            // Skip if already sent to this mobile
            if(in_array($mobile, $processedMobiles)) {
                continue; // Skip duplicate
            }
            
            $processedMobiles[] = $mobile;
            
            // Calculate failed subjects dynamically
            $failedSubjectsCount = 0;
            if($exam->type === 'subject') {
                $marks = $exam->marks()->where('application_id', $app->id)->get();
                foreach($marks as $mark) {
                    $subject = $exam->subjects->find($mark->subject_id);
                    if($subject && $mark->obtained_mark < $subject->pass_mark) {
                        $failedSubjectsCount++;
                    }
                }
            }
            
            // Prepare keyword replacements
            $studentName = $app->name_bn ?? $app->name_en;
            $rollNo = $app->admission_roll_no;
            $meritPosition = $result->merit_position;
            $totalMarks = $result->total_obtained;
            $resultStatus = $result->is_pass ? 'উত্তীর্ণ' : 'অকৃতকার্য';
            
            $failedSubjectsInfo = '';
            if(!$result->is_pass && $failedSubjectsCount > 0) {
                $failedSubjectsInfo = "ফেল বিষয়: {$failedSubjectsCount}টি";
            }
            
            // Replace keywords in template
            $message = str_replace(
                ['{school_name}', '{exam_name}', '{student_name}', '{roll_no}', '{merit_position}', '{total_marks}', '{result_status}', '{failed_subjects_info}'],
                [$school->name_bn, $exam->name, $studentName, $rollNo, $meritPosition, $totalMarks, $resultStatus, $failedSubjectsInfo],
                $messageTemplate
            );
            
            // Clean up empty lines
            $message = preg_replace('/\n{3,}/', "\n\n", $message);
            $message = trim($message);
            
            // Send SMS using existing SmsSender service
            try {
                $smsResult = \App\Services\SmsSender::send($school->id, $mobile, $message);
                
                if($smsResult['success']) {
                    $sentCount++;
                    
                    // Log SMS - success
                    \App\Models\SmsLog::create([
                        'school_id' => $school->id,
                        'sent_by_user_id' => auth()->id(),
                        'recipient_type' => 'admission_applicant',
                        'recipient_category' => 'admission_exam_result',
                        'recipient_id' => $app->id,
                        'recipient_name' => $studentName,
                        'recipient_role' => 'student',
                        'roll_number' => $rollNo,
                        'class_name' => null,
                        'section_name' => null,
                        'recipient_number' => $mobile,
                        'message' => $message,
                        'status' => 'sent',
                        'response' => $smsResult['message'] . ' | ' . substr($smsResult['response'] ?? '', 0, 200),
                        'message_type' => 'result_notification',
                    ]);
                } else {
                    $failedCount++;
                    $errors[] = "Roll {$rollNo}: " . $smsResult['message'];
                    
                    // Log SMS - failed
                    \App\Models\SmsLog::create([
                        'school_id' => $school->id,
                        'sent_by_user_id' => auth()->id(),
                        'recipient_type' => 'admission_applicant',
                        'recipient_category' => 'admission_exam_result',
                        'recipient_id' => $app->id,
                        'recipient_name' => $studentName,
                        'recipient_role' => 'student',
                        'roll_number' => $rollNo,
                        'class_name' => null,
                        'section_name' => null,
                        'recipient_number' => $mobile,
                        'message' => $message,
                        'status' => 'failed',
                        'response' => $smsResult['message'] . ' | ' . substr($smsResult['response'] ?? '', 0, 200),
                        'message_type' => 'result_notification',
                    ]);
                }
            } catch(\Exception $e) {
                $failedCount++;
                $errors[] = "Roll {$rollNo}: " . $e->getMessage();
                \Log::error('SMS Send Error for Roll '.$rollNo.': ' . $e->getMessage());
                
                // Log SMS - error
                \App\Models\SmsLog::create([
                    'school_id' => $school->id,
                    'sent_by_user_id' => auth()->id(),
                    'recipient_type' => 'admission_applicant',
                    'recipient_category' => 'admission_exam_result',
                    'recipient_id' => $app->id,
                    'recipient_name' => $studentName,
                    'recipient_role' => 'student',
                    'roll_number' => $rollNo,
                    'class_name' => null,
                    'section_name' => null,
                    'recipient_number' => $mobile,
                    'message' => $message,
                    'status' => 'failed',
                    'response' => 'Exception: ' . $e->getMessage(),
                    'message_type' => 'result_notification',
                ]);
            }
        }
        
        $msg = "SMS পাঠানো সম্পন্ন। মোট প্রাপক: ".count($sortedResults)." জন | সফল: {$sentCount}টি, ব্যর্থ: {$failedCount}টি";
        
        if($sentCount > 0) {
            if($failedCount > 0 && count($errors) > 0) {
                $msg .= " | ব্যর্থ হওয়ার কারণ: " . implode(', ', array_slice($errors, 0, 3));
            }
            return back()->with('success', $msg);
        } else {
            if(count($errors) > 0) {
                $msg .= " | ত্রুটি: " . implode(', ', array_slice($errors, 0, 3));
            }
            return back()->with('error', $msg);
        }
    }
}
