<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Services\CmsSlugService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontendWebController extends Controller
{
    public function index(Request $request)
    {
        $domain = preg_replace('/^www\./', '', $request->getHost());
        $superAdminDomain = 'institute.batighorbd.com';

        if ($domain === $superAdminDomain) {
            return redirect('/login');
        }

        $schoolId = config('school.id');

        if (! $schoolId && app()->environment('local') && ($domain === 'localhost' || $domain === '127.0.0.1')) {
            return redirect('/login');
        }

        $school = School::find($schoolId);

        if (! $school) {
            return redirect('/login');
        }

        if (! $school->hasModule('frontend_website')) {
            return redirect('/login');
        }

        $settings = SchoolFrontendSetting::where('school_id', $school->id)->first();

        return view('frontend.index', [
            'school' => $school,
            'settings' => $settings,
        ]);
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

        return view('frontend.page', $this->cmsViewData($school, [
            'page' => $page,
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
        return array_merge([
            'school' => $school,
            'siteSettings' => SchoolFrontendSetting::where('school_id', $school->id)->first(),
        ], $extra);
    }
}
