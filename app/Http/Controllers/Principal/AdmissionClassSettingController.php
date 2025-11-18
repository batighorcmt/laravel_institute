<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AdmissionClassSetting;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class AdmissionClassSettingController extends Controller
{
    public function index(School $school)
    {
        $settings = AdmissionClassSetting::forSchoolYear($school->id, $school->admission_academic_year_id)
            ->orderBy('class_code')
            ->get();
        $classes = SchoolClass::forSchool($school->id)->active()->ordered()->get(['id','name','numeric_value']);
        return view('principal.admissions.class_settings', compact('school','settings','classes'));
    }

    public function store(School $school, Request $request)
    {
        $allowedCodes = SchoolClass::forSchool($school->id)->active()->pluck('numeric_value')->map(fn($v)=>(string)$v)->all();
        $data = $request->validate([
            'class_code' => ['required','string','max:32', 'in:'.implode(',', $allowedCodes)],
            'fee_amount' => 'required|numeric|min:0',
            'deadline' => 'nullable|date|afterOrEqual:today',
            'active' => 'nullable|boolean'
        ]);
        AdmissionClassSetting::create([
            'school_id' => $school->id,
            'academic_year_id' => $school->admission_academic_year_id,
            'class_code' => $data['class_code'],
            'fee_amount' => $data['fee_amount'],
            'deadline' => $data['deadline'] ?? null,
            'active' => (bool)($data['active'] ?? true),
        ]);
        return redirect()->back()->with('success','Class admission setting added');
    }

    public function update(School $school, AdmissionClassSetting $setting, Request $request)
    {
        abort_if($setting->school_id !== $school->id, 404);
        $data = $request->validate([
            'fee_amount' => 'required|numeric|min:0',
            'deadline' => 'nullable|date',
            'active' => 'nullable|boolean'
        ]);
        $setting->update([
            'fee_amount' => $data['fee_amount'],
            'deadline' => $data['deadline'] ?? null,
            'active' => (bool)($data['active'] ?? false),
        ]);
        return redirect()->back()->with('success','Updated');
    }

    public function destroy(School $school, AdmissionClassSetting $setting)
    {
        abort_if($setting->school_id !== $school->id, 404);
        $setting->delete();
        return redirect()->back()->with('success','Deleted');
    }
}
