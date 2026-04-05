<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;

class FrontendSettingsController extends Controller
{
    public function index(School $school)
    {
        return view('principal.frontend.settings', compact('school'));
    }

    public function getData(School $school)
    {
        $settings = \App\Models\SchoolFrontendSetting::firstOrCreate([
            'school_id' => $school->id
        ]);
        
        return response()->json([
            'settings' => $settings
        ]);
    }

    public function updateData(Request $request, School $school)
    {
        $settings = \App\Models\SchoolFrontendSetting::firstOrCreate([
            'school_id' => $school->id
        ]);

        $data = $request->validate([
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:255',
            'about_text' => 'nullable|string',
            'principal_name' => 'nullable|string|max:255',
            'principal_message' => 'nullable|string',
            'facebook_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'marquee_text' => 'nullable|string',
            'contact_address' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'committee_text' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        // Handle Images
        if ($request->hasFile('hero_image')) {
            if ($settings->hero_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->hero_image);
            }
            $data['hero_image'] = $request->file('hero_image')->store('frontend/' . $school->id, 'public');
        }

        if ($request->hasFile('about_image')) {
            if ($settings->about_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->about_image);
            }
            $data['about_image'] = $request->file('about_image')->store('frontend/' . $school->id, 'public');
        }

        if ($request->hasFile('principal_image')) {
            if ($settings->principal_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->principal_image);
            }
            $data['principal_image'] = $request->file('principal_image')->store('frontend/' . $school->id, 'public');
        }

        $settings->update($data);

        return response()->json([
            'message' => 'Website settings updated successfully!',
            'settings' => $settings->fresh()
        ]);
    }
}
