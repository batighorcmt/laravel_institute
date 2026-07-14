<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FrontendSettingsController extends Controller
{
    public function index(School $school)
    {
        return view('principal.frontend.settings', compact('school'));
    }

    public function getData(School $school)
    {
        $settings = SchoolFrontendSetting::firstOrCreate([
            'school_id' => $school->id,
        ]);

        $this->normalizeHeroImages($settings);

        return response()->json([
            'settings' => $settings,
        ]);
    }

    public function updateData(Request $request, School $school)
    {
        $settings = SchoolFrontendSetting::firstOrCreate([
            'school_id' => $school->id,
        ]);

        $data = $request->validate([
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:255',
            'about_text' => 'nullable|string',
            'principal_name' => 'nullable|string|max:255',
            'principal_message' => 'nullable|string',
            'principal_title' => 'nullable|string|max:255',
            'principal_designation' => 'nullable|string|max:255',
            'chairman_name' => 'nullable|string|max:255',
            'chairman_message' => 'nullable|string',
            'chairman_title' => 'nullable|string|max:255',
            'chairman_designation' => 'nullable|string|max:255',
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

        // Handle Single Images
        if ($request->hasFile('hero_image')) {
            if ($settings->hero_image) {
                Storage::disk('public')->delete($settings->hero_image);
            }
            $data['hero_image'] = $request->file('hero_image')->store('frontend/'.$school->id, 'public');
        }

        if ($request->hasFile('about_image')) {
            if ($settings->about_image) {
                Storage::disk('public')->delete($settings->about_image);
            }
            $data['about_image'] = $request->file('about_image')->store('frontend/'.$school->id, 'public');
        }

        if ($request->hasFile('principal_image')) {
            if ($settings->principal_image) {
                Storage::disk('public')->delete($settings->principal_image);
            }
            $data['principal_image'] = $request->file('principal_image')->store('frontend/'.$school->id, 'public');
        }

        if ($request->hasFile('chairman_image')) {
            if ($settings->chairman_image) {
                Storage::disk('public')->delete($settings->chairman_image);
            }
            $data['chairman_image'] = $request->file('chairman_image')->store('frontend/'.$school->id, 'public');
        }

        if ($request->hasFile('principal_feature_image')) {
            if ($settings->principal_feature_image) {
                Storage::disk('public')->delete($settings->principal_feature_image);
            }
            $data['principal_feature_image'] = $request->file('principal_feature_image')->store('frontend/'.$school->id, 'public');
        }

        if ($request->hasFile('chairman_feature_image')) {
            if ($settings->chairman_feature_image) {
                Storage::disk('public')->delete($settings->chairman_feature_image);
            }
            $data['chairman_feature_image'] = $request->file('chairman_feature_image')->store('frontend/'.$school->id, 'public');
        }

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
            $url = Storage::url($path);

            return response()->json([
                'url' => $url,
                'location' => $url,
            ]);
        }

        return response()->json(['error' => 'Upload failed'], 400);
    }

    /**
     * Ensure every hero slide has a stable id so individual slides can be
     * targeted for update/delete without relying on fragile array indexes.
     */
    protected function normalizeHeroImages(SchoolFrontendSetting $settings): void
    {
        $items = $settings->hero_images ?? [];
        if (! is_array($items) || empty($items)) {
            return;
        }

        $changed = false;
        $items = array_map(function ($item) use (&$changed) {
            $item = is_array($item) ? $item : ['image' => $item];
            if (empty($item['id'])) {
                $item['id'] = (string) Str::uuid();
                $changed = true;
            }

            return $item;
        }, $items);

        if ($changed) {
            $settings->update(['hero_images' => $items]);
        }
    }

    public function addSlide(Request $request, School $school)
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
        $this->normalizeHeroImages($settings);

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'button1_text' => 'nullable|string|max:100',
            'button1_url' => 'nullable|string|max:500',
            'button2_text' => 'nullable|string|max:100',
            'button2_url' => 'nullable|string|max:500',
        ]);

        $path = $request->file('image')->store('frontend/'.$school->id.'/slider', 'public');

        $items = $settings->hero_images ?? [];
        $items[] = [
            'id' => (string) Str::uuid(),
            'image' => $path,
            'title' => $validated['title'] ?? '',
            'subtitle' => $validated['subtitle'] ?? '',
            'active' => $request->boolean('active', true),
            'button1_text' => $validated['button1_text'] ?? '',
            'button1_url' => $validated['button1_url'] ?? '',
            'button2_text' => $validated['button2_text'] ?? '',
            'button2_url' => $validated['button2_url'] ?? '',
        ];

        $settings->update(['hero_images' => $items]);

        return response()->json([
            'message' => 'স্লাইড যোগ করা হয়েছে।',
            'settings' => $settings->fresh(),
        ]);
    }

    public function updateSlide(Request $request, School $school, string $slideId)
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
        $this->normalizeHeroImages($settings);

        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'button1_text' => 'nullable|string|max:100',
            'button1_url' => 'nullable|string|max:500',
            'button2_text' => 'nullable|string|max:100',
            'button2_url' => 'nullable|string|max:500',
        ]);

        $items = $settings->hero_images ?? [];
        $found = false;

        foreach ($items as &$item) {
            if (($item['id'] ?? null) !== $slideId) {
                continue;
            }
            $found = true;

            if ($request->hasFile('image')) {
                if (! empty($item['image'])) {
                    Storage::disk('public')->delete($item['image']);
                }
                $item['image'] = $request->file('image')->store('frontend/'.$school->id.'/slider', 'public');
            }

            $item['title'] = $validated['title'] ?? ($item['title'] ?? '');
            $item['subtitle'] = $validated['subtitle'] ?? ($item['subtitle'] ?? '');
            $item['active'] = $request->has('active') ? $request->boolean('active') : ($item['active'] ?? true);
            $item['button1_text'] = $validated['button1_text'] ?? ($item['button1_text'] ?? '');
            $item['button1_url'] = $validated['button1_url'] ?? ($item['button1_url'] ?? '');
            $item['button2_text'] = $validated['button2_text'] ?? ($item['button2_text'] ?? '');
            $item['button2_url'] = $validated['button2_url'] ?? ($item['button2_url'] ?? '');
        }
        unset($item);

        if (! $found) {
            return response()->json(['message' => 'স্লাইড খুঁজে পাওয়া যায়নি।'], 404);
        }

        $settings->update(['hero_images' => $items]);

        return response()->json([
            'message' => 'স্লাইড আপডেট হয়েছে।',
            'settings' => $settings->fresh(),
        ]);
    }

    public function deleteSlide(School $school, string $slideId)
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
        $this->normalizeHeroImages($settings);

        $items = $settings->hero_images ?? [];
        $remaining = [];
        $deleted = false;

        foreach ($items as $item) {
            if (($item['id'] ?? null) === $slideId) {
                $deleted = true;
                if (! empty($item['image'])) {
                    Storage::disk('public')->delete($item['image']);
                }
                continue;
            }
            $remaining[] = $item;
        }

        if (! $deleted) {
            return response()->json(['message' => 'স্লাইড খুঁজে পাওয়া যায়নি।'], 404);
        }

        $settings->update(['hero_images' => $remaining]);

        return response()->json([
            'message' => 'স্লাইড মুছে ফেলা হয়েছে।',
            'settings' => $settings->fresh(),
        ]);
    }

    public function addAboutImages(Request $request, School $school)
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $current = $settings->about_images ?? [];
        foreach ($request->file('images') as $file) {
            $current[] = $file->store('frontend/'.$school->id.'/about', 'public');
        }

        $settings->update(['about_images' => array_values($current)]);

        return response()->json([
            'message' => 'ছবি যোগ করা হয়েছে।',
            'settings' => $settings->fresh(),
        ]);
    }

    public function deleteAboutImage(Request $request, School $school)
    {
        $request->validate(['path' => 'required|string|max:500']);

        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
        $current = collect($settings->about_images ?? []);
        $path = $request->input('path');

        if ($current->contains($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $settings->update(['about_images' => $current->reject(fn ($p) => $p === $path)->values()->all()]);

        return response()->json([
            'message' => 'ছবি মুছে ফেলা হয়েছে।',
            'settings' => $settings->fresh(),
        ]);
    }
}
