<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\StoreCmsPostRequest;
use App\Http\Requests\Principal\UpdateCmsPostRequest;
use App\Models\CmsPost;
use App\Models\School;
use App\Services\CmsSlugService;
use Illuminate\Http\Request;

class CmsPostController extends Controller
{
    public function index(School $school)
    {
        $posts = CmsPost::forSchool($school->id)
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('principal.frontend.cms.posts.index', compact('school', 'posts'));
    }

    public function create(School $school)
    {
        $post = new CmsPost(['status' => CmsPost::STATUS_DRAFT, 'robots' => 'index, follow']);

        return view('principal.frontend.cms.posts.form', [
            'school' => $school,
            'post' => $post,
            'isEdit' => false,
        ]);
    }

    public function store(StoreCmsPostRequest $request, School $school, CmsSlugService $slugService)
    {
        $data = $request->validated();
        $slug = $slugService->makeUniqueSlug($data['title'], $data['slug'] ?? null, new CmsPost, $school->id);

        $post = CmsPost::create([
            'school_id' => $school->id,
            'author_id' => $request->user()->id,
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'] ?? null,
            'featured_image' => $this->storeFeaturedImage($request, $school->id),
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? ($data['status'] === CmsPost::STATUS_PUBLISHED ? now() : null),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'robots' => $data['robots'] ?? 'index, follow',
            'og_image' => $this->storeOgImage($request, $school->id),
        ]);

        return redirect()
            ->route('principal.institute.frontend.posts.edit', [$school, $post])
            ->with('success', 'ব্লগ পোস্ট তৈরি হয়েছে।');
    }

    public function edit(School $school, CmsPost $post)
    {
        abort_unless($post->school_id === $school->id, 404);

        return view('principal.frontend.cms.posts.form', [
            'school' => $school,
            'post' => $post,
            'isEdit' => true,
        ]);
    }

    public function update(UpdateCmsPostRequest $request, School $school, CmsPost $post, CmsSlugService $slugService)
    {
        abort_unless($post->school_id === $school->id, 404);

        $data = $request->validated();
        $slug = $slugService->makeUniqueSlug($data['title'], $data['slug'] ?? $post->slug, new CmsPost, $school->id, $post->id);

        $post->update([
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'] ?? null,
            'featured_image' => $this->storeFeaturedImage($request, $school->id) ?? $post->featured_image,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? ($data['status'] === CmsPost::STATUS_PUBLISHED && ! $post->published_at ? now() : $post->published_at),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'robots' => $data['robots'] ?? 'index, follow',
            'og_image' => $this->storeOgImage($request, $school->id) ?? $post->og_image,
        ]);

        return redirect()
            ->route('principal.institute.frontend.posts.edit', [$school, $post])
            ->with('success', 'ব্লগ পোস্ট আপডেট হয়েছে।');
    }

    public function destroy(School $school, CmsPost $post)
    {
        abort_unless($post->school_id === $school->id, 404);
        $post->delete();

        return redirect()
            ->route('principal.institute.frontend.posts.index', $school)
            ->with('success', 'ব্লগ পোস্ট মুছে ফেলা হয়েছে।');
    }

    protected function storeFeaturedImage(Request $request, int $schoolId): ?string
    {
        if (! $request->hasFile('featured_image')) {
            return null;
        }

        return $request->file('featured_image')->store('cms/'.$schoolId.'/featured', 'public');
    }

    protected function storeOgImage(Request $request, int $schoolId): ?string
    {
        if (! $request->hasFile('og_image')) {
            return null;
        }

        return $request->file('og_image')->store('cms/'.$schoolId.'/og', 'public');
    }
}
