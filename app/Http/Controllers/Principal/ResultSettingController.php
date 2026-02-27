<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Setting;
use Illuminate\Http\Request;

class ResultSettingController extends Controller
{
    public function index(School $school)
    {
        $settings = Setting::where('school_id', $school->id)->get()->keyBy('key');
        return view('principal.institute.result_settings.index', compact('school', 'settings'));
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'marks_decimal_position' => 'required|integer|min:0|max:5',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['school_id' => $school->id, 'key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
