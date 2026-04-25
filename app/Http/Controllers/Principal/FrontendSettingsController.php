<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class FrontendSettingsController extends Controller
{
    public function index(School $school)
    {
        return view('principal.frontend.settings', compact('school'));
    }

    public function getData(School $school)
    {
        $settings = \App\Models\SchoolFrontendSetting::firstOrCreate([
            'school_id' => $school->id,
        ]);

        return response()->json([
            'settings' => $settings,
        ]);
    }

    public function updateData(Request $request, School $school)
    {
        $settings = \App\Models\SchoolFrontendSetting::firstOrCreate([
            'school_id' => $school->id,
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
            'hero_images_json' => 'nullable|string', // Current list of images
        ]);

        // Handle Single Images
        if ($request->hasFile('hero_image')) {
            if ($settings->hero_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->hero_image);
            }
            $data['hero_image'] = $request->file('hero_image')->store('frontend/'.$school->id, 'public');
        }

        if ($request->hasFile('about_image')) {
            if ($settings->about_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->about_image);
            }
            $data['about_image'] = $request->file('about_image')->store('frontend/'.$school->id, 'public');
        }

        if ($request->hasFile('principal_image')) {
            if ($settings->principal_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->principal_image);
            }
            $data['principal_image'] = $request->file('principal_image')->store('frontend/'.$school->id, 'public');
        }

        // Handle Multiple Hero Slider Items
        $currentItems = [];
        if ($request->filled('hero_images_json')) {
            $currentItems = json_decode($request->hero_images_json, true) ?: [];
        }

        if ($request->hasFile('hero_slider_files')) {
            $metas = $request->input('hero_slider_meta', []);
            foreach ($request->file('hero_slider_files') as $idx => $file) {
                $path = $file->store('frontend/'.$school->id.'/slider', 'public');
                $meta = isset($metas[$idx]) ? json_decode($metas[$idx], true) : ['title' => '', 'subtitle' => '', 'active' => true];
                $currentItems[] = array_merge($meta, ['image' => $path]);
            }
        }
        $data['hero_images'] = $currentItems;
        unset($data['hero_images_json']);

        $settings->update($data);

        return response()->json([
            'message' => 'Website settings updated successfully!',
            'settings' => $settings->fresh(),
        ]);
    }

    public function uploadImage(Request $request, School $school)
    {
        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($request->hasFile('upload')) {
            $path = $request->file('upload')->store('attachments/'.$school->id, 'public');
            $url = \Illuminate\Support\Facades\Storage::url($path);

            return response()->json([
                'url' => $url,
                'location' => $url,
            ]);
        }

        return response()->json(['error' => 'Upload failed'], 400);
    }
}
