<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\Notice;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Models\SchoolStatsSetting;
use App\Services\CmsSlugService;
use App\Services\DynamicPageContentService;
use App\Services\FrontendHomepageContentService;
use App\Services\FrontendMenuService;
use App\Services\FrontendNoticeService;
use App\Services\SchoolStatsResolver;
use App\Services\WebsiteThemeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FrontendWebController extends Controller
{
    public function index(Request $request)
    {
        $domain = preg_replace('/^www\./', '', $request->getHost());
        $superAdminDomain = env('SUPER_ADMIN_DOMAIN', 'institute.batighorbd.com');

        $schoolId = config('school.id');

        if ($domain === $superAdminDomain || ! $schoolId || ! $school = School::find($schoolId)) {
            return view('welcome');
        }

        if (! $school->hasModule('frontend_website')) {
            return view('welcome');
        }

        $settings = SchoolFrontendSetting::where('school_id', $school->id)->first();
        $frontendNotices = app(FrontendNoticeService::class);
        $homepageService = app(FrontendHomepageContentService::class);
        $homepageContent = $homepageService->resolve($settings);

        if (empty($homepageContent['gallery'])) {
            $homepageContent['gallery'] = $homepageService->placeholderGallery($school, $settings);
        }

        $chromeData = $this->frontendChromeData($school, $settings);

        $statsSettings = SchoolStatsSetting::where('school_id', $school->id)->first();
        $stats = app(SchoolStatsResolver::class)->resolve($school, $statsSettings);

        $viewData = array_merge($chromeData, [
            'settings' => $settings,
            'homepageContent' => $homepageContent,
            'teachers' => $homepageService->teachersForSchool($school->id, 0),
            'blogPosts' => $homepageService->blogPostsForSchool($school->id),
            'boardNotices' => $frontendNotices->boardNoticesForSchool($school->id)->values()->all(),
            'allBoardNotices' => $frontendNotices->allBoardNoticesForSchool($school->id)->values()->all(),
            'stats' => $stats,
        ]);

        $view = $chromeData['templateKey'] === \App\Models\WebsiteTheme::TEMPLATE_TWO
            ? 'frontend.themes.theme2.index'
            : 'frontend.index';

        return view($view, $viewData);
    }

    public function downloadNotice(Notice $notice): StreamedResponse
    {
        $school = $this->resolveSchoolOrAbort();

        $downloadable = app(FrontendNoticeService::class)->findDownloadableNotice($school->id, $notice->id);

        if (! $downloadable?->attachment_path || ! Storage::disk('public')->exists($downloadable->attachment_path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $downloadable->attachment_path,
            basename($downloadable->attachment_path)
        );
    }

    public function blogIndex(Request $request): View
    {
        $school = $this->resolveSchoolOrAbort();

        $posts = CmsPost::forSchool($school->id)
            ->published()
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12);

        return view('frontend.blog.index', $this->cmsViewData($school, [
            'posts' => $posts,
            'seoTitle' => ($school->name_bn ?? $school->name).' — ব্লগ',
            'seoDescription' => 'স্কুলের সর্বশেষ সংবাদ ও ব্লগ পোস্ট।',
            'ogType' => 'website',
        ]));
    }

    public function blogShow(Request $request, string $slug): View
    {
        $school = $this->resolveSchoolOrAbort();

        $post = CmsPost::forSchool($school->id)
            ->where('slug', $slug)
            ->published()
            ->with('author:id,name')
            ->firstOrFail();

        return view('frontend.blog.show', $this->cmsViewData($school, [
            'post' => $post,
            'seoTitle' => $post->meta_title ?: $post->title,
            'seoDescription' => $post->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->content ?? ''), 160),
            'seoKeywords' => $post->meta_keywords,
            'seoRobots' => $post->robots ?? 'index, follow',
            'seoOgImage' => $post->og_image ?: $post->featured_image,
            'ogType' => 'article',
        ]));
    }

    public function cmsPage(Request $request, string $slug): View
    {
        if (in_array($slug, CmsSlugService::RESERVED_SLUGS, true)) {
            abort(404);
        }

        $school = $this->resolveSchoolOrAbort();

        $page = CmsPage::forSchool($school->id)
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        $settings = SchoolFrontendSetting::where('school_id', $school->id)->first();
        $dynamicData = [];
        if ($page->isDynamic() && $page->data_source) {
            $dynamicData = app(DynamicPageContentService::class)->resolve($page->data_source, $school, $settings);
        }

        return view('frontend.page', $this->cmsViewData($school, [
            'page' => $page,
            'dynamicData' => $dynamicData,
            'seoTitle' => $page->meta_title ?: $page->title,
            'seoDescription' => $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->content ?? ''), 160),
            'seoKeywords' => $page->meta_keywords,
            'seoRobots' => $page->robots ?? 'index, follow',
            'seoOgImage' => $page->og_image,
            'ogType' => 'article',
        ]));
    }

    protected function resolveSchoolOrAbort(): School
    {
        $schoolId = config('school.id');
        $school = $schoolId ? School::find($schoolId) : null;

        if (! $school || ! $school->hasModule('frontend_website')) {
            abort(404);
        }

        return $school;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function cmsViewData(School $school, array $extra = []): array
    {
        $settings = SchoolFrontendSetting::where('school_id', $school->id)->first();

        return array_merge($this->frontendChromeData($school, $settings), [
            'siteSettings' => $settings,
        ], $extra);
    }

    /**
     * @return array<string, mixed>
     */
    protected function frontendChromeData(School $school, ?SchoolFrontendSetting $settings): array
    {
        $menuService = app(FrontendMenuService::class);
        $frontendNotices = app(FrontendNoticeService::class);
        $templateKey = $settings?->theme?->template_key ?? \App\Models\WebsiteTheme::TEMPLATE_ONE;

        return [
            'school' => $school,
            'schoolPayload' => $this->schoolPayload($school),
            'settingsPayload' => $this->frontendSettingsPayload($settings),
            'headerMenu' => $menuService->forLocation($settings, $school, 'header'),
            'footerMenu' => $menuService->forLocation($settings, $school, 'footer'),
            'marqueeNotices' => $frontendNotices->marqueeNoticesForSchool($school->id)->values()->all(),
            'storageBase' => '/storage',
            'themeColors' => app(WebsiteThemeResolver::class)->resolveColors($settings),
            'templateKey' => $templateKey,
            'cmsLayout' => $templateKey === \App\Models\WebsiteTheme::TEMPLATE_TWO
                ? 'frontend.themes.theme2.cms-layout'
                : 'frontend.cms-layout',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function schoolPayload(School $school): array
    {
        $payload = $school->only(['id', 'name', 'name_bn', 'code', 'eiin', 'phone', 'email', 'domain', 'logo', 'address', 'address_bn', 'founding_year']);
        if (! empty($payload['logo'])) {
            $payload['logo'] = storage_asset($payload['logo']);
        }
        $payload['phone'] = $school->displayPhone();

        return $payload;
    }

    /**
     * @return array<string, mixed>|object
     */
    protected function frontendSettingsPayload(?SchoolFrontendSetting $settings): array|object
    {
        if (! $settings) {
            return new \stdClass;
        }

        $payload = $settings->toArray();

        foreach (['hero_image', 'about_image', 'principal_image', 'chairman_image'] as $field) {
            if (! empty($payload[$field])) {
                $payload[$field] = storage_asset($payload[$field]);
            }
        }

        $heroImages = $settings->hero_images;
        if (is_string($heroImages)) {
            $heroImages = json_decode($heroImages, true) ?: [];
        }
        if (! is_array($heroImages)) {
            $heroImages = [];
        }

        $payload['hero_images'] = collect($heroImages)->map(function ($item) {
            if (is_string($item)) {
                return [
                    'image' => storage_asset($item),
                    'title' => '',
                    'subtitle' => '',
                    'active' => true,
                ];
            }
            if (is_array($item) && ! empty($item['image'])) {
                $item['image'] = storage_asset($item['image']);
            }

            return $item;
        })->all();

        return $payload;
    }
}
