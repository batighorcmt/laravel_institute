<?php

namespace App\Http\Controllers\Principal\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentSetting;
use App\Models\DocumentTemplate;
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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'setting' => $setting]);
        }

        return redirect()->route('principal.institute.documents.settings.index', $school)->with('success','Settings saved');
    }

    public function templates(School $school)
    {
        $templates = DocumentTemplate::where('school_id', $school->id)->get();
        return response()->json($templates);
    }

    public function storeTemplate(Request $request, School $school)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:document_templates,id',
            'type' => 'required|in:prottayon,certificate,testimonial',
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $template = DocumentTemplate::updateOrCreate(
            ['id' => $validated['id'] ?? null],
            [
                'school_id' => $school->id,
                'type' => $validated['type'],
                'name' => $validated['name'],
                'content' => $validated['content'],
                'is_active' => $validated['is_active'],
            ]
        );

        return response()->json($template);
    }

    public function destroyTemplate(School $school, DocumentTemplate $template)
    {
        if ($template->school_id !== $school->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $template->delete();
        return response()->json(['success' => true]);
    }
}
