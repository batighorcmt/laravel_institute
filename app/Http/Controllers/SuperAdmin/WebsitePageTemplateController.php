<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\WebsitePageTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebsitePageTemplateController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'pageTemplates' => WebsitePageTemplate::orderBy('sort_order')->orderBy('id')->get(),
            ]);
        }

        return view('superadmin.website.page-templates.index');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $pageTemplate = WebsitePageTemplate::create($data);

        return response()->json([
            'message' => 'পৃষ্ঠা টেমপ্লেট সফলভাবে যুক্ত করা হয়েছে।',
            'pageTemplate' => $pageTemplate,
        ]);
    }

    public function update(Request $request, WebsitePageTemplate $pageTemplate)
    {
        $data = $this->validated($request, $pageTemplate);

        $pageTemplate->update($data);

        return response()->json([
            'message' => 'পৃষ্ঠা টেমপ্লেট আপডেট হয়েছে।',
            'pageTemplate' => $pageTemplate->fresh(),
        ]);
    }

    public function toggle(WebsitePageTemplate $pageTemplate)
    {
        $pageTemplate->update(['is_active' => ! $pageTemplate->is_active]);

        return response()->json([
            'message' => $pageTemplate->is_active ? 'টেমপ্লেট চালু করা হয়েছে।' : 'টেমপ্লেট বাতিল করা হয়েছে।',
            'pageTemplate' => $pageTemplate->fresh(),
        ]);
    }

    public function destroy(WebsitePageTemplate $pageTemplate)
    {
        if ($pageTemplate->cmsPages()->exists()) {
            return response()->json([
                'message' => 'এই টেমপ্লেট থেকে ইতিমধ্যে পৃষ্ঠা তৈরি হয়েছে, তাই মুছে ফেলা যাবে না। প্রয়োজনে টেমপ্লেটটি বাতিল করুন।',
            ], 422);
        }

        $pageTemplate->delete();

        return response()->json(['message' => 'পৃষ্ঠা টেমপ্লেট মুছে ফেলা হয়েছে।']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?WebsitePageTemplate $pageTemplate = null): array
    {
        // Super admin only defines the page's identity (title/slug). The Principal
        // decides how each page's content is shown (dynamic/static + data source)
        // after applying the template, via the existing CMS Pages screen.
        return $request->validate([
            'key' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('website_page_templates', 'key')->ignore($pageTemplate?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'default_slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);
    }
}
