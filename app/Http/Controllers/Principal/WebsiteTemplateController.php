<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Models\WebsiteMenuTemplate;
use App\Models\WebsitePageTemplate;
use App\Models\WebsiteTheme;
use App\Services\WebsiteTemplateApplyService;
use Illuminate\Http\Request;

class WebsiteTemplateController extends Controller
{
    public function index(School $school)
    {
        return view('principal.frontend.website-template', compact('school'));
    }

    public function data(School $school)
    {
        $settings = SchoolFrontendSetting::where('school_id', $school->id)->first();

        return response()->json([
            'themes' => WebsiteTheme::active()->orderBy('sort_order')->orderBy('id')->get(),
            'menuTemplates' => WebsiteMenuTemplate::active()->orderBy('sort_order')->orderBy('id')->get(['id', 'name']),
            'pageTemplates' => WebsitePageTemplate::active()->orderBy('sort_order')->orderBy('id')->get(),
            'current' => [
                'theme_id' => $settings?->theme_id,
                'applied_menu_template_id' => $settings?->applied_menu_template_id,
                'applied_at' => $settings?->applied_at?->toDateTimeString(),
            ],
            'hasThemeCustomization' => (bool) $settings?->theme_id,
            'hasMenuCustomization' => (bool) $settings && ! empty($settings->frontend_menus),
            'hasPagesCustomization' => CmsPage::forSchool($school->id)->exists(),
        ]);
    }

    public function applyTheme(Request $request, School $school, WebsiteTemplateApplyService $applyService)
    {
        $data = $request->validate([
            'theme_id' => ['required', 'exists:website_themes,id'],
        ]);

        $settings = $applyService->applyTheme($school, $data['theme_id']);

        return response()->json([
            'message' => 'থিম সফলভাবে প্রয়োগ করা হয়েছে।',
            'settings' => $settings,
        ]);
    }

    public function applyMenu(Request $request, School $school, WebsiteTemplateApplyService $applyService)
    {
        $data = $request->validate([
            'menu_template_id' => ['required', 'exists:website_menu_templates,id'],
        ]);

        $settings = $applyService->applyMenu($school, $data['menu_template_id']);

        return response()->json([
            'message' => 'মেনু টেমপ্লেট সফলভাবে প্রয়োগ করা হয়েছে।',
            'settings' => $settings,
        ]);
    }

    public function applyPages(Request $request, School $school, WebsiteTemplateApplyService $applyService)
    {
        $data = $request->validate([
            'page_template_ids' => ['required', 'array', 'min:1'],
            'page_template_ids.*' => ['integer', 'exists:website_page_templates,id'],
        ]);

        $applyService->applyPages($school, $data['page_template_ids'], $request->user()->id);

        return response()->json([
            'message' => 'নির্বাচিত পৃষ্ঠাসমূহ সফলভাবে প্রয়োগ করা হয়েছে।',
        ]);
    }
}
