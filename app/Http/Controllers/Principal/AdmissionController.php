<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AdmissionApplication;
use App\Models\AdmissionClassSetting;
use Illuminate\Support\Carbon;
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
        return view('principal.admissions.settings', compact('school','academicYears','classSettings'));
    }

    public function updateSettings(Request $request, School $school)
    {
        $data = $request->validate([
            'admissions_enabled' => 'nullable|boolean',
            'admission_academic_year_id' => 'required|exists:academic_years,id'
        ]);
        $school->update([
            'admissions_enabled' => (bool)($data['admissions_enabled'] ?? false),
            'admission_academic_year_id' => $data['admission_academic_year_id']
        ]);
        return redirect()->back()->with('success','Admission সেটিংস আপডেট হয়েছে');
    }

    public function applications(School $school)
    {
        $apps = AdmissionApplication::where('school_id',$school->id)
            ->orderByDesc('id')->paginate(20);
        return view('principal.admissions.index', compact('school','apps'));
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
        return view('principal.admissions.admit_card', compact('school','application'));
    }

    public function cancel(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
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
        return redirect()->route('principal.institute.admissions.applications.show', [$school->id,$application->id])
            ->with('success','আবেদন বাতিল করা হয়েছে');
    }

    public function edit(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
        return view('principal.admissions.edit', compact('school','application'));
    }

    public function update(School $school, AdmissionApplication $application)
    {
        abort_if($application->school_id !== $school->id, 404);
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
