<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\WebsitePageTemplate;
use App\Models\WebsiteMenuTemplate;
use App\Services\FrontendMenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebsiteMenuTemplateController extends Controller
{
    public function __construct(protected FrontendMenuService $menuService) {}

    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'menuTemplates' => WebsiteMenuTemplate::orderBy('sort_order')->orderBy('id')->get(),
                'pageTemplates' => WebsitePageTemplate::active()->orderBy('sort_order')->get(['id', 'title', 'default_slug']),
                'sections' => $this->menuService->homepageSections(),
            ]);
        }

        return view('superadmin.website.menu-templates.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $menuTemplate = WebsiteMenuTemplate::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'config' => $this->menuService->defaults(),
        ]);

        return response()->json([
            'message' => 'মেনু টেমপ্লেট তৈরি হয়েছে।',
            'menuTemplate' => $menuTemplate,
        ]);
    }

    public function update(Request $request, WebsiteMenuTemplate $menuTemplate)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'config' => ['required', 'array'],
            'config.menus' => ['required', 'array'],
            'config.menus.*.id' => ['required', 'string', 'max:100'],
            'config.menus.*.name' => ['required', 'string', 'max:255'],
            'config.menus.*.items' => ['nullable', 'array'],
            'config.locations' => ['required', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $menuTemplate->update($data);

        return response()->json([
            'message' => 'মেনু টেমপ্লেট আপডেট হয়েছে।',
            'menuTemplate' => $menuTemplate->fresh(),
        ]);
    }

    public function toggle(WebsiteMenuTemplate $menuTemplate)
    {
        $menuTemplate->update(['is_active' => ! $menuTemplate->is_active]);

        return response()->json([
            'message' => $menuTemplate->is_active ? 'মেনু টেমপ্লেট চালু করা হয়েছে।' : 'মেনু টেমপ্লেট বাতিল করা হয়েছে।',
            'menuTemplate' => $menuTemplate->fresh(),
        ]);
    }

    public function destroy(WebsiteMenuTemplate $menuTemplate)
    {
        if ($menuTemplate->schoolFrontendSettings()->exists()) {
            return response()->json([
                'message' => 'এই মেনু টেমপ্লেটটি ইতিমধ্যে এক বা একাধিক স্কুলে প্রয়োগ করা হয়েছে, তাই মুছে ফেলা যাবে না।',
            ], 422);
        }

        $menuTemplate->delete();

        return response()->json(['message' => 'মেনু টেমপ্লেট মুছে ফেলা হয়েছে।']);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'menu-'.time();
        $slug = $base;
        $i = 1;

        while (WebsiteMenuTemplate::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
