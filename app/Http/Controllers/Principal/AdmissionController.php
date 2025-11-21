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

    public function payments(School $school)
    {
        // Placeholder view until payment integration is specified
        $payments = collect();
        return view('principal.admissions.payments', compact('school','payments'));
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
                if (!$application->admission_roll_no) {
                    $q = AdmissionApplication::where('school_id', $school->id);
                    if ($application->academic_year_id) {
                        $q->where('academic_year_id', $application->academic_year_id);
                    }
                    $max = (int) ($q->lockForUpdate()->max('admission_roll_no') ?? 0);
                    $application->admission_roll_no = $max + 1; // store as int; render padded
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
        return view('principal.admissions.show', compact('school','application'));    
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
            'mother_name_en' => 'required|string|max:191',
            'guardian_name_en' => 'nullable|string|max:191',
            'gender' => 'required|string|max:16',
            'religion' => 'nullable|string|max:32',
            'dob' => 'nullable|date|before:today',
            'mobile' => 'required|string|max:32',
            'class_name' => 'nullable|string|max:64',
            'last_school' => 'nullable|string|max:191',
            'result' => 'nullable|string|max:64',
            'pass_year' => 'nullable|string|max:8',
        ]);
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
}
