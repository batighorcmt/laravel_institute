<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolPaymentSetting;
use Illuminate\Http\Request;

class PaymentSettingsController extends Controller
{
    const PROVIDER = 'sslcommerz';

    public function index(School $school)
    {
        $setting = SchoolPaymentSetting::firstOrCreate(
            ['school_id'=>$school->id,'provider'=>self::PROVIDER],
            ['sandbox'=>true,'active'=>false]
        );
        return view('principal.settings.payments', compact('school','setting'));
    }

    public function save(Request $request, School $school)
    {
        $data = $request->validate([
            'store_id' => 'required|string|max:191',
            'store_password' => 'required|string|max:191',
            'sandbox' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ]);
        $setting = SchoolPaymentSetting::firstOrCreate(
            ['school_id'=>$school->id,'provider'=>self::PROVIDER]
        );
        $setting->update([
            'store_id' => $data['store_id'],
            'store_password' => $data['store_password'],
            'sandbox' => (bool)($data['sandbox'] ?? false),
            'active' => (bool)($data['active'] ?? false),
        ]);
        return redirect()->back()->with('success','Online Payments settings saved');
    }
}
