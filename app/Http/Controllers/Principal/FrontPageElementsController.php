<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\UpdateFrontPageElementsRequest;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Services\FrontendHomepageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FrontPageElementsController extends Controller
{
    public function __construct(
        protected FrontendHomepageContentService $homepageContent
    ) {}

    public function index(School $school): View
    {
        return view('principal.frontend.front-page-elements', compact('school'));
    }

    public function getData(School $school): JsonResponse
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
        $raw = $settings->homepage_content;
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        $content = array_replace_recursive($this->homepageContent->defaults(), $raw);

        return response()->json([
            'homepage_content' => $content,
        ]);
    }

    public function updateData(UpdateFrontPageElementsRequest $request, School $school): JsonResponse
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
        $raw = $settings->homepage_content;
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (! is_array($raw)) {
            $raw = [];
        }
        $current = array_replace_recursive($this->homepageContent->defaults(), $raw);
        $validated = $request->validated();

        $content = [
            'mission' => [
                'title' => $validated['mission']['title'] ?? $current['mission']['title'],
                'body' => $validated['mission']['body'] ?? $current['mission']['body'],
            ],
            'vision' => [
                'title' => $validated['vision']['title'] ?? $current['vision']['title'],
                'body' => $validated['vision']['body'] ?? $current['vision']['body'],
            ],
            'blog_section' => [
                'title' => $validated['blog_section']['title'] ?? ($current['blog_section']['title'] ?? 'ব্লগ ও সংবাদ'),
                'subtitle' => $validated['blog_section']['subtitle'] ?? ($current['blog_section']['subtitle'] ?? ''),
            ],
            'achievements' => $validated['achievements'] ?? $current['achievements'],
            'facilities' => $validated['facilities'] ?? $current['facilities'],
            'committee_members' => $validated['committee_members'] ?? $current['committee_members'] ?? [],
        ];

        if ($request->hasFile('committee_member_photos')) {
            $existingMembers = $current['committee_members'] ?? [];
            foreach ($request->file('committee_member_photos') as $idx => $file) {
                if (! $file) {
                    continue;
                }

                $oldPhoto = $existingMembers[$idx]['photo'] ?? null;
                if ($oldPhoto) {
                    Storage::disk('public')->delete($oldPhoto);
                }

                $content['committee_members'][$idx]['photo'] = $file->store('frontend/'.$school->id.'/committee', 'public');
            }
        }

        $settings->update(['homepage_content' => $content]);

        return response()->json([
            'message' => 'ফ্রন্টপেজ উপাদান সফলভাবে আপডেট হয়েছে।',
            'homepage_content' => $this->homepageContent->resolve($settings->fresh()),
        ]);
    }
}
