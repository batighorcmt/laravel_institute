<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AdmissionApplication;
use App\Models\AdmissionClassSetting;
use Illuminate\Support\Carbon;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionController extends Controller
{
    public function settings(School $school)
    {
        $academicYears = \App\Models\AcademicYear::where('school_id',$school->id)->orderByDesc('start_date')->get();
        $classSettings = collect();
        if ($school->admission_academic_year_id) {
            $classSettings = AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
                ->orderBy('class_code')
                ->get();
        }
        // Admission exam settings (datetime + venues)
        $raw = Setting::forSchool($school->id)
            ->whereIn('key', [
                'admission_exam_datetime','admission_exam_venues'
            ])
            ->pluck('value','key');
        $examDatetime = $raw->get('admission_exam_datetime');
        $venues = [];
        if ($raw->get('admission_exam_venues')) {
            $v = json_decode($raw->get('admission_exam_venues'), true);
            if (is_array($v)) { $venues = $v; }
        }
        return view('principal.admissions.settings', compact('school','academicYears','classSettings','examDatetime','venues'));
    }

    public function updateSettings(Request $request, School $school)
    {
        $data = $request->validate([
            'admissions_enabled' => 'nullable|boolean',
            'admission_academic_year_id' => 'required|exists:academic_years,id',
            'exam_datetime' => 'nullable|date',
            'venues_name' => 'array',
            'venues_name.*' => 'nullable|string|max:191',
            'venues_address' => 'array',
            'venues_address.*' => 'nullable|string|max:500',
        ]);
        $school->update([
            'admissions_enabled' => (bool)($data['admissions_enabled'] ?? false),
            'admission_academic_year_id' => $data['admission_academic_year_id']
        ]);
        // Save exam datetime
        $examDt = $data['exam_datetime'] ?? null;
        if ($examDt) {
            Setting::updateOrCreate(
                ['school_id'=>$school->id,'key'=>'admission_exam_datetime'],
                ['value'=> (string)Carbon::parse($examDt)->toDateTimeString()]
            );
        } else {
            // allow clearing
            Setting::where('school_id',$school->id)->where('key','admission_exam_datetime')->delete();
        }
        // Save venues as JSON array
        $names = $request->input('venues_name', []);
        $addresses = $request->input('venues_address', []);
        $out = [];
        $count = max(count($names), count($addresses));
        for ($i=0; $i<$count; $i++) {
            $n = trim((string)($names[$i] ?? ''));
            $a = trim((string)($addresses[$i] ?? ''));
            if ($n || $a) { $out[] = ['name'=>$n, 'address'=>$a]; }
        }
        if (!empty($out)) {
            Setting::updateOrCreate(
                ['school_id'=>$school->id,'key'=>'admission_exam_venues'],
                ['value'=> json_encode($out, JSON_UNESCAPED_UNICODE)]
            );
        } else {
            Setting::where('school_id',$school->id)->where('key','admission_exam_venues')->delete();
        }
        return redirect()->back()->with('success','Admission সেটিংস আপডেট হয়েছে');
    }

    public function applications(School $school)
    {
        $query = AdmissionApplication::where('school_id',$school->id);
        $apps = $query->orderByDesc('id')->paginate(20);

        // Statistics
        $totalApps = (clone $query)->count();
        $acceptedApps = (clone $query)->whereNotNull('accepted_at')->count();
        $cancelledApps = (clone $query)->where('status','cancelled')->count();
        $paidApps = (clone $query)->where('payment_status','Paid')->count();

        // Total paid amount (sum of successful payments for applications of this school)
        $totalPaidAmount = \App\Models\AdmissionPayment::whereHas('application', function($q) use ($school){
            $q->where('school_id',$school->id);
        })->where('status','Completed')->sum('amount');

        // Expected total fees based on class settings (match by class_code == application->class_name)
        $expectedTotalFees = 0;
        $settings = [];
        if ($school->admission_academic_year_id) {
            $settings = \App\Models\AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
                ->get()->keyBy('class_code');
        }
        foreach ((clone $query)->get(['class_name']) as $appRow) {
            if ($appRow->class_name && isset($settings[$appRow->class_name])) {
                $expectedTotalFees += (float) $settings[$appRow->class_name]->fee_amount;
            }
        }
        $unpaidAmount = max($expectedTotalFees - (float)$totalPaidAmount, 0);

        return view('principal.admissions.index', compact(
            'school','apps','totalApps','acceptedApps','cancelledApps','paidApps','totalPaidAmount','expectedTotalFees','unpaidAmount'
        ));
    }

    public function payments(Request $request, School $school)
    {
        // Filters: status (Completed/Failed/Pending), date range (from/to), search (app_id/name)
        $status = $request->string('status')->trim()->toString();
        $from = $request->date('from');
        $to = $request->date('to');
        $search = $request->string('q')->trim()->toString();

        $query = \App\Models\AdmissionPayment::with(['application' => function($q){
                $q->select('id','school_id','app_id','name_en','name_bn','class_name','payment_status');
            }])
            ->whereHas('application', function($q) use ($school){
                $q->where('school_id', $school->id);
            });

        if ($status) {
            $query->where('status', $status);
        }
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('transaction_id','like',"%$search%")
                  ->orWhereHas('application', function($qa) use ($search){
                      $qa->where('app_id','like',"%$search%")
                         ->orWhere('name_en','like',"%$search%")
                         ->orWhere('name_bn','like',"%$search%");
                  });
            });
        }

        $payments = $query->orderByDesc('id')->paginate(30)->appends($request->query());
        return view('principal.admissions.payments', compact('school','payments','status','from','to','search'));
    }

    public function paymentInvoice(School $school, \App\Models\AdmissionPayment $payment)
    {
        abort_if(optional($payment->application)->school_id !== $school->id, 404);
        $payment->load(['application']);
        return view('principal.admissions.payment_invoice', [
            'school' => $school,
            'payment' => $payment,
            'application' => $payment->application,
        ]);
    }
    public function accept(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        if ($application->payment_status !== 'Paid') {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
                ->with('error','পেমেন্ট সম্পন্ন হয়নি, আবেদন গ্রহণ করা যাবে না');
        }
        if ($application->status === 'cancelled') {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
                ->with('error','বাতিলকৃত আবেদন গ্রহণযোগ্য নয়');
        }
        if (!$application->accepted_at) {
            DB::transaction(function () use ($school, $application) {
                // Compute class-prefixed roll: classNumber*1000 + sequence (within class)
                if (!$application->admission_roll_no) {
                    $classRaw = (string)($application->class_name ?? '');
                    $classNum = (int)preg_replace('/[^0-9]/','', $classRaw);
                    // Fallback to simple sequence if class number not found
                    if ($classNum <= 0) {
                        $q = AdmissionApplication::where('school_id', $school->id);
                        if ($application->academic_year_id) {
                            $q->where('academic_year_id', $application->academic_year_id);
                        }
                        $max = (int) ($q->lockForUpdate()->max('admission_roll_no') ?? 0);
                        $application->admission_roll_no = $max + 1;
                    } else {
                        // Count existing accepted/applications with same class to determine next sequence
                        $q = AdmissionApplication::where('school_id', $school->id)
                            ->where(function($qc) use ($classRaw) {
                                $qc->where('class_name', $classRaw);
                            });
                        if ($application->academic_year_id) {
                            $q->where('academic_year_id', $application->academic_year_id);
                        }
                        // lock for update to avoid race conditions
                        $acceptedCount = (int) ($q->lockForUpdate()->whereNotNull('accepted_at')->count());
                        $seq = max(min($acceptedCount + 1, 999), 1);
                        $prefix = $classNum * 1000;
                        $roll = (int) ($prefix + $seq);
                        // ensure uniqueness just in case
                        while (AdmissionApplication::where('school_id',$school->id)
                                ->when($application->academic_year_id, function($qa) use($application){ $qa->where('academic_year_id',$application->academic_year_id); })
                                ->where('admission_roll_no',$roll)->exists() && $seq < 999) {
                            $seq++;
                            $roll = (int) ($prefix + $seq);
                        }
                        $application->admission_roll_no = $roll;
                    }
                }
                $application->accepted_at = now();
                $application->status = 'accepted';
                $application->save();
            });
            // Send acceptance SMS
            $rollDisplay = str_pad((string)$application->admission_roll_no, 4, '0', STR_PAD_LEFT);
            $smsService = new \App\Services\SmsService($school);
            $message = "আপনার ভর্তি আবেদন গ্রহণ করা হয়েছে। ভর্তি রোল নং-{$rollDisplay}.-JSS";
            $smsService->sendSms($application->mobile, $message, 'admission_accept', [
                'recipient_type' => 'applicant',
                'recipient_id' => $application->id,
                'recipient_name' => $application->name_en,
            ]);
        }
        return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
            ->with('success','আবেদন গ্রহণ করা হয়েছে');
    }

    public function show(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        $academicYear = null;
        if ($application->academic_year_id) {
            $academicYear = \App\Models\AcademicYear::find($application->academic_year_id);
        }
        return view('principal.admissions.show', compact('school','application','academicYear'));
    }

    public function copy(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        if (strtolower($application->payment_status) !== 'paid') {
            return response()->view('admission.blocked', [
                'schoolCode' => $school->code,
                'title' => 'দেখার অনুমতি নেই',
                'message' => 'ফিস পরিশোধ হয় নাই। তাই দেখানো সম্ভব নয়।',
                'showLogout' => false,
            ], 403);
        }
        $payment = $application->payments()->latest()->first();
        return view('admission.application_copy', [
            'school' => $school,
            'application' => $application,
            'payment' => $payment,
        ]);
    }

    public function admitCard(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        abort_unless((bool)$application->accepted_at, 403, 'এখনো গ্রহণ হয়নি');
        $settings = Setting::forSchool($school->id)
            ->whereIn('key', ['admission_exam_datetime','admission_exam_venues'])
            ->pluck('value','key');
        $examDatetime = $settings->get('admission_exam_datetime');
        $venues = [];
        if ($settings->get('admission_exam_venues')) {
            $v = json_decode($settings->get('admission_exam_venues'), true);
            if (is_array($v)) { $venues = $v; }
        }
        return view('principal.admissions.admit_card', compact('school','application','examDatetime','venues'));
    }

    public function cancel(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        // Prevent cancelling if already enrolled
        if ($application->student_id) {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
                ->with('error','শিক্ষার্থী ভর্তি সম্পন্ন হয়েছে, আবেদন বাতিল করা যাবে না');
        }
        if ($application->status === 'cancelled') {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
                ->with('error','ইতোমধ্যে আবেদন বাতিল করা হয়েছে');
        }
        $data = request()->validate([
            'cancellation_reason' => 'required|string|max:1000'
        ],[
            'cancellation_reason.required' => 'বাতিলের কারণ লিখতে হবে'
        ]);
        $application->accepted_at = null;
        $application->status = 'cancelled';
        $application->cancellation_reason = $data['cancellation_reason'];
        $application->save();
        // Send rejection SMS
        $smsService = new \App\Services\SmsService($school);
        $message = "আপনার ভর্তি আবেদন বাতিল করা হয়েছে। সঠিক তথ্য দিয়ে পুনারায় আবেদন করুন-JSS";
        $smsService->sendSms($application->mobile, $message, 'admission_reject', [
            'recipient_type' => 'applicant',
            'recipient_id' => $application->id,
            'recipient_name' => $application->name_en,
        ]);
        return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
            ->with('success','আবেদন বাতিল করা হয়েছে');
    }


    public function edit(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        if ($application->student_id) {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
                ->with('error','ভর্তি সম্পন্ন হওয়ায় আবেদন সম্পাদনা সম্ভব নয়');
        }
        return view('principal.admissions.edit', compact('school','application'));
    }

    public function update(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        if ($application->student_id) {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
                ->with('error','ভর্তি সম্পন্ন হওয়ায় আবেদন তথ্য পরিবর্তন করা যাবে না');
        }
        $data = request()->validate([
            'name_en' => 'required|string|max:191',
            'name_bn' => 'required|string|max:191',
            'father_name_en' => 'required|string|max:191',
            'father_name_bn' => 'nullable|string|max:191',
            'mother_name_en' => 'required|string|max:191',
            'mother_name_bn' => 'nullable|string|max:191',
            'guardian_name_en' => 'nullable|string|max:191',
            'guardian_name_bn' => 'nullable|string|max:191',
            'guardian_relation' => 'nullable|string|max:64',
            'gender' => 'required|string|max:16',
            'religion' => 'nullable|string|max:32',
            'blood_group' => 'nullable|string|max:8',
            'birth_reg_no' => 'nullable|string|max:64',
            'dob' => 'nullable|date|before:today',
            'mobile' => 'required|string|max:32',
            'class_name' => 'nullable|string|max:64',
            'last_school' => 'nullable|string|max:191',
            'result' => 'nullable|string|max:64',
            'pass_year' => 'nullable|string|max:8',
            'achievement' => 'nullable|string|max:500',
            // Present address
            'present_village' => 'nullable|string|max:191',
            'present_para_moholla' => 'nullable|string|max:191',
            'present_post_office' => 'nullable|string|max:191',
            'present_upazilla' => 'nullable|string|max:191',
            'present_district' => 'nullable|string|max:191',
            // Permanent address
            'permanent_village' => 'nullable|string|max:191',
            'permanent_para_moholla' => 'nullable|string|max:191',
            'permanent_post_office' => 'nullable|string|max:191',
            'permanent_upazilla' => 'nullable|string|max:191',
            'permanent_district' => 'nullable|string|max:191',
            // Photo
            'photo' => 'nullable|image|max:2048',
        ]);

        // Handle photo upload
        if (request()->hasFile('photo')) {
            $file = request()->file('photo');
            $name = 'app_'.$application->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $file->storeAs('public/admission', $name);
            // Optionally remove old photo if exists
            if ($application->photo && \Storage::disk('public')->exists('admission/'.$application->photo)) {
                // Silent try-catch to avoid breaking if deletion fails
                try { \Storage::disk('public')->delete('admission/'.$application->photo); } catch (\Throwable $e) {}
            }
            $data['photo'] = $name;
        }

        $application->fill($data)->save();
        return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
            ->with('success','আবেদন তথ্য আপডেট হয়েছে');
    }

    public function applicationPayments(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        $payments = $application->payments()->latest()->get();
        return view('principal.admissions.payment_details', compact('school','application','payments'));
    }

    public function resetPassword(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        // Find associated user by username (stored as APP ID during creation)
        $user = \App\Models\User::where('username', $application->app_id)->first();
        if (!$user) {
            return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
                ->with('error','ইউজার পাওয়া যায়নি (username mismatch)');
        }
        // Generate new password (8 char mixed) for robustness
        $newPlain = \Illuminate\Support\Str::random(8);
        $user->password = bcrypt($newPlain);
        if (\Illuminate\Support\Facades\Schema::hasColumn('users','password_changed_at')) {
            $user->password_changed_at = now();
        }
        $user->save();
        // Update application->data password & hashed variant
        $dataArr = is_array($application->data) ? $application->data : [];
        $dataArr['password'] = $newPlain;
        $dataArr['password_hashed'] = $user->password;
        $application->data = $dataArr;
        $application->save();
        // Send SMS via service and log
        $smsService = new \App\Services\SmsService($school);
        $message = "আপনার ভর্তি আবেদন পাসওয়ার্ড রিসেট করা হয়েছে। Username: {$application->app_id}, New Password: {$newPlain}.";
        $smsService->sendSms($application->mobile, $message, 'admission_password_reset', [
            'recipient_type' => 'applicant',
            'recipient_id' => $application->id,
            'recipient_name' => $application->name_en,
        ]);
        \Illuminate\Support\Facades\Log::info('sms_dispatch', [
            'type' => 'admission_password_reset',
            'school_code' => $school->code,
            'recipient' => $application->mobile,
            'app_id' => $application->app_id,
            'status' => 'sent'
        ]);
        return redirect()->route('principal.institute.admissions.applications.show', [$school->id, $application->id])
            ->with('success','পাসওয়ার্ড রিসেট হয়েছে এবং এসএমএস পাঠানো হয়েছে');
    }
}
