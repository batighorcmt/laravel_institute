<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\StoreCmsPageRequest;
use App\Http\Requests\Principal\UpdateCmsPageRequest;
use App\Models\CmsPage;
use App\Models\School;
use App\Services\CmsSlugService;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    public function index(School $school)
    {
        $pages = CmsPage::forSchool($school->id)
            ->with('author:id,name')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('principal.frontend.cms.pages.index', compact('school', 'pages'));
    }

    public function create(School $school)
    {
        $page = new CmsPage(['status' => CmsPage::STATUS_DRAFT, 'robots' => 'index, follow']);

        return view('principal.frontend.cms.pages.form', [
            'school' => $school,
            'page' => $page,
            'isEdit' => false,
        ]);
    }

    public function store(StoreCmsPageRequest $request, School $school, CmsSlugService $slugService)
    {
        $data = $request->validated();
        $slug = $slugService->makeUniqueSlug($data['title'], $data['slug'] ?? null, new CmsPage, $school->id);

        $page = CmsPage::create([
            'school_id' => $school->id,
            'author_id' => $request->user()->id,
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? ($data['status'] === CmsPage::STATUS_PUBLISHED ? now() : null),
            'sort_order' => $data['sort_order'] ?? 0,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'robots' => $data['robots'] ?? 'index, follow',
            'og_image' => $this->storeOgImage($request, $school->id),
        ]);

        return redirect()
            ->route('principal.institute.frontend.pages.edit', [$school, $page])
            ->with('success', 'পৃষ্ঠা সফলভাবে তৈরি হয়েছে।');
    }

    public function edit(School $school, CmsPage $page)
    {
        abort_unless($page->school_id === $school->id, 404);

        return view('principal.frontend.cms.pages.form', [
            'school' => $school,
            'page' => $page,
            'isEdit' => true,
        ]);
    }

    public function update(UpdateCmsPageRequest $request, School $school, CmsPage $page, CmsSlugService $slugService)
    {
        abort_unless($page->school_id === $school->id, 404);

        $data = $request->validated();
        $slug = $slugService->makeUniqueSlug($data['title'], $data['slug'] ?? $page->slug, new CmsPage, $school->id, $page->id);

        $page->update([
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? ($data['status'] === CmsPage::STATUS_PUBLISHED && ! $page->published_at ? now() : $page->published_at),
            'sort_order' => $data['sort_order'] ?? 0,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'robots' => $data['robots'] ?? 'index, follow',
            'og_image' => $this->storeOgImage($request, $school->id) ?? $page->og_image,
        ]);

        return redirect()
            ->route('principal.institute.frontend.pages.edit', [$school, $page])
            ->with('success', 'পৃষ্ঠা আপডেট হয়েছে।');
    }

    public function destroy(School $school, CmsPage $page)
    {
        abort_unless($page->school_id === $school->id, 404);
        $page->delete();

        return redirect()
            ->route('principal.institute.frontend.pages.index', $school)
            ->with('success', 'পৃষ্ঠা মুছে ফেলা হয়েছে।');
    }

    protected function storeOgImage(Request $request, int $schoolId): ?string
    {
        if (! $request->hasFile('og_image')) {
            return null;
        }

        return $request->file('og_image')->store('cms/'.$schoolId.'/og', 'public');
    }
}
