<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class BackgroundSettingsController extends Controller
{
    public function index(School $school)
    {
        $admitBackground = Setting::where('school_id', $school->id)->where('key', 'admit_card_v1_background')->first()?->value;
        return view('principal.background_settings.index', compact('school', 'admitBackground'));
    }

    public function update(Request $request, School $school)
    {
        $request->validate([
            'admit_card_v1_background' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('admit_card_v1_background')) {
            $file = $request->file('admit_card_v1_background');
            $path = $file->store('backgrounds', 'public');
            
            // Delete old file if exists
            $old = Setting::where('school_id', $school->id)->where('key', 'admit_card_v1_background')->first();
            if ($old && $old->value) {
                Storage::disk('public')->delete($old->value);
            }

            Setting::updateOrCreate(
                ['school_id' => $school->id, 'key' => 'admit_card_v1_background'],
                ['value' => $path]
            );
        }

        return redirect()->back()->with('success', 'Background updated successfully!');
    }
}
