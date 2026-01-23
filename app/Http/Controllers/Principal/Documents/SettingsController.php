<?php

namespace App\Http\Controllers\Principal\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentSetting;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index(Request $request, School $school)
    {
        $pages = ['prottayon','certificate','testimonial'];
        $settings = DocumentSetting::where('school_id',$school->id)->whereIn('page',$pages)->get()->keyBy('page');
        $selected = [];
        foreach ($pages as $page) {
            $s = $settings[$page] ?? null;
            $selected[$page] = $s ? array_filter($s->memo_format ?: [], 'is_string') : [];
        }
        return view('principal.documents.settings.index', compact('school','settings','pages','selected'));
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'page' => 'required|in:prottayon,certificate,testimonial',
            'background' => 'nullable|image|max:2048',
            'colors' => 'nullable|array',
            'memo_format' => 'nullable|array|min:1',
            'custom_text' => 'nullable|string',
        ]);

        $path = null;
        if ($request->hasFile('background')) {
            $path = $request->file('background')->store('documents/'.$school->id.'/'.$validated['page'], 'public');
        }

        $setting = DocumentSetting::firstOrNew([
            'school_id' => $school->id,
            'page' => $validated['page'],
        ]);
        if ($path) { $setting->background_path = $path; }
        if (isset($validated['colors'])) { $setting->colors = $validated['colors']; }
        if (isset($validated['memo_format'])) { $setting->memo_format = $validated['memo_format']; }
        if (isset($validated['custom_text'])) { $setting->custom_text = array_filter(array_map('trim', explode(',', $validated['custom_text']))); }
        $setting->save();

        return redirect()->route('principal.institute.documents.settings.index', $school)->with('success','Settings saved');
    }
}
