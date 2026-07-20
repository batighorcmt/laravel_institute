<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class MobileSettingsController extends Controller
{
    public function edit()
    {
        $apiBaseUrl = SystemSetting::get('mobile_api_base_url', 'https://institute.batighorbd.com/api/v1/');
        return view('superadmin.mobile_settings.edit', compact('apiBaseUrl'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'api_base_url' => ['required', 'url', 'ends_with:/'],
        ]);

        SystemSetting::set('mobile_api_base_url', $data['api_base_url']);

        return redirect()->route('superadmin.mobile-settings.edit')
            ->with('success', 'মোবাইল অ্যাপ সেটিংস আপডেট হয়েছে। ইনস্টল করা অ্যাপগুলো পরবর্তী চালুর সময় নতুন সার্ভার ব্যবহার করবে।');
    }
}
