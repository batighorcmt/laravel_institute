<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebsiteThemeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'themes' => WebsiteTheme::orderBy('sort_order')->orderBy('id')->get(),
            ]);
        }

        return view('superadmin.website.themes.index');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $theme = WebsiteTheme::create($data);

        return response()->json([
            'message' => 'থিম সফলভাবে যুক্ত করা হয়েছে।',
            'theme' => $theme,
        ]);
    }

    public function update(Request $request, WebsiteTheme $theme)
    {
        $data = $this->validated($request);

        $theme->update($data);

        return response()->json([
            'message' => 'থিম সফলভাবে আপডেট করা হয়েছে।',
            'theme' => $theme->fresh(),
        ]);
    }

    public function toggle(WebsiteTheme $theme)
    {
        $theme->update(['is_active' => ! $theme->is_active]);

        return response()->json([
            'message' => $theme->is_active ? 'থিম চালু করা হয়েছে।' : 'থিম বাতিল করা হয়েছে।',
            'theme' => $theme->fresh(),
        ]);
    }

    public function destroy(WebsiteTheme $theme)
    {
        if ($theme->schoolFrontendSettings()->exists()) {
            return response()->json([
                'message' => 'এই থিমটি ইতিমধ্যে এক বা একাধিক স্কুলে প্রয়োগ করা হয়েছে, তাই মুছে ফেলা যাবে না। প্রয়োজনে থিমটি বাতিল করুন।',
            ], 422);
        }

        if ($theme->preview_image) {
            Storage::disk('public')->delete($theme->preview_image);
        }

        $theme->delete();

        return response()->json(['message' => 'থিম মুছে ফেলা হয়েছে।']);
    }

    public function uploadPreview(Request $request)
    {
        $request->validate([
            'preview_image' => ['required', 'image', 'max:2048'],
        ]);

        $path = $request->file('preview_image')->store('website-themes', 'public');

        return response()->json(['path' => $path, 'url' => Storage::url($path)]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_key' => ['sometimes', Rule::in([WebsiteTheme::TEMPLATE_ONE, WebsiteTheme::TEMPLATE_TWO])],
            'description' => ['nullable', 'string', 'max:500'],
            'colors' => ['required', 'array'],
            'colors.primary' => ['required', 'string', 'max:20'],
            'colors.secondary' => ['required', 'string', 'max:20'],
            'colors.accent' => ['required', 'string', 'max:20'],
            'colors.bg' => ['required', 'string', 'max:20'],
            'colors.text' => ['required', 'string', 'max:20'],
            'font_family' => ['nullable', 'string', 'max:255'],
            'preview_image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'theme-'.time();
        $slug = $base;
        $i = 1;

        while (WebsiteTheme::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
