<?php

namespace App\Services;

use App\Models\CmsPost;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Models\Teacher;

class FrontendHomepageContentService
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'mission' => [
                'title' => 'আমাদের মিশন',
                'body' => 'শিক্ষার্থীদের মেধা, মানবিকতা ও নৈতিকতার ভিত্তিতে দক্ষ, দায়িত্বশীল ও আত্মবিশ্বাসী নাগরিক হিসেবে গড়ে তোলা—এটিই আমাদের মূল মিশন।',
            ],
            'vision' => [
                'title' => 'আমাদের ভিশন',
                'body' => 'একটি আধুনিক, প্রযুক্তিনির্ভর ও অনুপ্রেরণামূলক শিক্ষাপ্রতিষ্ঠান হিসেবে আঞ্চলিকভাবে স্বীকৃত হওয়া এবং শিক্ষার মানে নতুন মানদণ্ড স্থাপন করা।',
            ],
            'achievements' => [
                ['year' => '২০২৫', 'title' => 'বোর্ড পরীক্ষায় উৎকর্ষ', 'description' => 'জেলা পর্যায়ে শীর্ষস্থানীয় ফলাফল', 'icon' => 'fa-trophy', 'color' => 'from-amber-400 to-orange-500'],
                ['year' => '২০২৪', 'title' => 'ক্রীড়া প্রতিযোগিতায় চ্যাম্পিয়ন', 'description' => 'আন্তঃবিদ্যালয় ক্রীড়া প্রতিযোগিতায় বিজয়', 'icon' => 'fa-medal', 'color' => 'from-emerald-400 to-teal-500'],
                ['year' => '২০২৩', 'title' => 'বিজ্ঞান মেলা পুরস্কার', 'description' => 'জাতীয় বিজ্ঞান মেলায় বিশেষ পুরস্কার', 'icon' => 'fa-flask', 'color' => 'from-violet-400 to-purple-600'],
                ['year' => '২০২২', 'title' => 'সাংস্কৃতিক অর্জন', 'description' => 'বিভিন্ন সাংস্কৃতিক প্রতিযোগিতায় সাফল্য', 'icon' => 'fa-music', 'color' => 'from-rose-400 to-pink-500'],
            ],
            'facilities' => [
                ['title' => 'আধুনিক ক্লাসরুম', 'description' => 'মাল্টিমিডিয়া ও স্মার্ট লার্নিং সুবিধা', 'icon' => 'fa-chalkboard', 'color' => 'from-indigo-500 to-blue-600'],
                ['title' => 'বিজ্ঞান ল্যাব', 'description' => 'হাতে-কলমে বিজ্ঞান শিক্ষার সুযোগ', 'icon' => 'fa-microscope', 'color' => 'from-cyan-500 to-teal-600'],
                ['title' => 'গ্রন্থাগার', 'description' => 'সমৃদ্ধ বই ও পড়ার পরিবেশ', 'icon' => 'fa-book-open', 'color' => 'from-amber-500 to-yellow-600'],
                ['title' => 'কম্পিউটার ল্যাব', 'description' => 'আইসিটি ভিত্তিক শিক্ষা', 'icon' => 'fa-laptop', 'color' => 'from-purple-500 to-fuchsia-600'],
                ['title' => 'খেলার মাঠ', 'description' => 'শারীরিক ও মানসিক বিকাশ', 'icon' => 'fa-futbol', 'color' => 'from-green-500 to-emerald-600'],
                ['title' => 'নিরাপদ ক্যাম্পাস', 'description' => 'সিসিটিভি ও নিরাপত্তা ব্যবস্থা', 'icon' => 'fa-shield-alt', 'color' => 'from-slate-600 to-slate-800'],
            ],
            'gallery' => [],
            'blog_section' => [
                'title' => 'ব্লগ ও সংবাদ',
                'subtitle' => 'স্কুলের সর্বশেষ সংবাদ ও আপডেট',
            ],
            'committee_members' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(?SchoolFrontendSetting $settings): array
    {
        $defaults = $this->defaults();
        $stored = $settings?->homepage_content;

        if (is_string($stored)) {
            $stored = json_decode($stored, true) ?: [];
        }

        if (! is_array($stored)) {
            $stored = [];
        }

        $content = array_replace_recursive($defaults, $stored);

        $content['gallery'] = collect($content['gallery'] ?? [])
            ->filter()
            ->map(fn ($path) => is_string($path) ? storage_asset($path) : $path)
            ->values()
            ->all();

        return $content;
    }

    /**
     * @return list<array{name: string, designation: string, photo: ?string}>
     */
    public function teachersForSchool(int $schoolId, int $limit = 0): array
    {
        $query = Teacher::query()
            ->with(['user:id,email', 'presentThana:id,name,bn_name', 'presentDistrict:id,name,bn_name'])
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('show_on_website', true)
            ->orderBy('serial_number')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(function (Teacher $teacher) {
                $nameBn = trim(($teacher->first_name_bn ?: '').' '.($teacher->last_name_bn ?: ''));
                $nameEn = trim(($teacher->first_name ?: '').' '.($teacher->last_name ?: ''));

                $address = collect([
                    $teacher->present_village,
                    $teacher->presentThana?->bn_name,
                    $teacher->presentDistrict?->bn_name,
                ])->filter()->implode(', ');

                return [
                    'id' => $teacher->id,
                    'name' => $nameBn ?: ($nameEn ?: 'শিক্ষক'),
                    'name_bn' => $nameBn ?: null,
                    'name_en' => $nameEn ?: null,
                    'designation' => $teacher->designation ?: 'শিক্ষক',
                    'phone' => $teacher->phone ?: null,
                    'email' => $teacher->user?->email ?: null,
                    'address' => $address ?: null,
                    'photo' => $teacher->photo ? storage_asset($teacher->photo) : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, title: string, excerpt: string, image: ?string, url: string, date: ?string}>
     */
    public function blogPostsForSchool(int $schoolId, int $limit = 6): array
    {
        return CmsPost::query()
            ->forSchool($schoolId)
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (CmsPost $post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'excerpt' => $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content ?? ''), 120),
                    'image' => $post->featured_image ? storage_asset($post->featured_image) : null,
                    'url' => route('frontend.blog.show', $post->slug),
                    'date' => $post->published_at?->format('d M Y'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Pick one image at random from the school's "about" gallery so the
     * history section shows a different photo on each page load.
     */
    public function randomAboutImage(?SchoolFrontendSetting $settings, ?string $fallback = null): ?string
    {
        $pool = collect($settings?->about_images ?? [])->filter()->values();

        if ($settings?->about_image) {
            $pool->push($settings->about_image);
        }

        if ($pool->isEmpty()) {
            return $fallback;
        }

        return storage_asset($pool->random());
    }

    /**
     * Placeholder gallery when none configured.
     *
     * @return list<string>
     */
    public function placeholderGallery(School $school, ?SchoolFrontendSetting $settings): array
    {
        $images = [];

        if ($settings?->about_image) {
            $images[] = storage_asset($settings->about_image);
        }

        if (is_array($settings?->about_images)) {
            foreach ($settings->about_images as $path) {
                if ($path) {
                    $images[] = storage_asset($path);
                }
            }
        }

        if ($settings?->hero_image) {
            $images[] = storage_asset($settings->hero_image);
        }

        $heroImages = $settings?->hero_images;
        if (is_array($heroImages)) {
            foreach ($heroImages as $item) {
                $path = is_array($item) ? ($item['image'] ?? null) : $item;
                if ($path) {
                    $images[] = storage_asset($path);
                }
            }
        }

        return array_values(array_unique(array_filter($images)));
    }
}
