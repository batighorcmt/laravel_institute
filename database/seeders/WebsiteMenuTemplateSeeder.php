<?php

namespace Database\Seeders;

use App\Models\WebsiteMenuTemplate;
use App\Services\FrontendMenuService;
use Illuminate\Database\Seeder;

class WebsiteMenuTemplateSeeder extends Seeder
{
    public function run(): void
    {
        WebsiteMenuTemplate::updateOrCreate(
            ['slug' => 'standard-menu'],
            [
                'name' => 'স্ট্যান্ডার্ড মেনু',
                'config' => app(FrontendMenuService::class)->defaults(),
                'is_default' => true,
                'sort_order' => 1,
            ]
        );

        WebsiteMenuTemplate::updateOrCreate(
            ['slug' => 'government-style-menu'],
            [
                'name' => 'সরকারি ওয়েবসাইট স্টাইল মেনু',
                'config' => $this->governmentStyleMenu(),
                'is_default' => false,
                'sort_order' => 2,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function governmentStyleMenu(): array
    {
        $page = fn (string $label, string $slug) => $this->item($label, 'page', ['page_slug' => $slug]);

        return [
            'menus' => [
                [
                    'id' => 'menu-govt-primary',
                    'name' => 'প্রধান মেনু',
                    'items' => [
                        $this->item('হোম', 'home'),
                        $this->item('আমাদের সম্পর্কে', 'page', ['page_slug' => 'about'], [
                            $page('প্রতিষ্ঠানের ইতিহাস', 'history'),
                            $page('লক্ষ্য ও উদ্দেশ্য', 'objectives'),
                            $page('ভিশন ও মিশন', 'vision-mission'),
                        ]),
                        $this->item('প্রতিষ্ঠানের তথ্য', 'page', ['page_slug' => 'eiin'], [
                            $page('EIIN', 'eiin'),
                            $page('প্রতিষ্ঠাকাল', 'founding'),
                            $page('অনুমোদন ও স্বীকৃতি', 'recognition'),
                        ]),
                        $this->item('প্রশাসন', 'page', ['page_slug' => 'head-teacher'], [
                            $page('প্রধান শিক্ষক', 'head-teacher'),
                            $page('সহকারী প্রধান শিক্ষক', 'assistant-head-teacher'),
                            $page('শিক্ষকবৃন্দ', 'faculty'),
                            $page('কর্মকর্তা-কর্মচারী', 'staff'),
                            $page('গভর্নিং বডি', 'governing-body'),
                        ]),
                        $this->item('একাডেমিক', 'page', ['page_slug' => 'classes'], [
                            $page('শ্রেণিসমূহ', 'classes'),
                            $page('বিভাগ/গ্রুপ', 'groups'),
                            $page('বিষয়সমূহ', 'subjects'),
                            $page('সিলেবাস', 'syllabus'),
                            $page('ক্লাস রুটিন', 'class-routine'),
                            $page('একাডেমিক ক্যালেন্ডার', 'academic-calendar'),
                            $page('পরীক্ষার রুটিন', 'exam-routine'),
                        ]),
                        $this->item('শিক্ষার্থী', 'page', ['page_slug' => 'student-count'], [
                            $page('শিক্ষার্থী সংখ্যা', 'student-count'),
                            $page('ছেলে-মেয়ের সংখ্যা', 'gender-ratio'),
                            $page('মেধাবী শিক্ষার্থী', 'meritorious-students'),
                            $page('ফলাফল', 'results'),
                        ]),
                        $this->item('ভর্তি', 'page', ['page_slug' => 'admission-info'], [
                            $page('ভর্তি বিজ্ঞপ্তি', 'admission-notice'),
                            $this->item('অনলাইনে আবেদন', 'admission'),
                            $page('ভর্তির যোগ্যতা', 'admission-eligibility'),
                            $page('ভর্তি ফি', 'admission-fees'),
                        ]),
                        $page('নোটিশ', 'notice-board'),
                        $page('গ্যালারি', 'gallery'),
                        $page('ডাউনলোড', 'downloads'),
                        $page('যোগাযোগ', 'contact-us'),
                    ],
                ],
                [
                    'id' => 'menu-govt-footer',
                    'name' => 'ফুটার মেনু',
                    'items' => [
                        $this->item('হোম', 'home'),
                        $page('প্রতিষ্ঠানের ইতিহাস', 'history'),
                        $page('নোটিশ', 'notice-board'),
                        $page('ফলাফল', 'results'),
                        $page('যোগাযোগ', 'contact-us'),
                    ],
                ],
            ],
            'locations' => [
                'header' => 'menu-govt-primary',
                'footer' => 'menu-govt-footer',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    protected function item(string $label, string $type, array $overrides = [], array $children = []): array
    {
        return array_merge([
            'id' => 'item-'.\Illuminate\Support\Str::slug($label).'-'.\Illuminate\Support\Str::random(6),
            'label' => $label,
            'type' => $type,
            'url' => null,
            'section' => null,
            'page_id' => null,
            'page_slug' => null,
            'target' => '_self',
            'children' => $children,
        ], $overrides);
    }
}
