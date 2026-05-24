<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use Illuminate\Support\Str;

class FrontendMenuService
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'menus' => [
                [
                    'id' => 'menu-primary',
                    'name' => 'Primary Menu',
                    'items' => $this->defaultPrimaryItems(),
                ],
                [
                    'id' => 'menu-footer',
                    'name' => 'Footer Menu',
                    'items' => [
                        $this->makeItem('home', 'হোম', 'home'),
                        $this->makeItem('blog', 'ব্লগ', 'blog'),
                        $this->makeItem('contact', 'যোগাযোগ', 'section', section: 'contact'),
                    ],
                ],
            ],
            'locations' => [
                'header' => 'menu-primary',
                'footer' => 'menu-footer',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function defaultPrimaryItems(): array
    {
        return [
            $this->makeItem('home', 'হোম', 'home'),
            $this->makeItem('about', 'পরিচিতি', 'section', section: 'about'),
            $this->makeItem('mission', 'মিশন', 'section', section: 'mission'),
            $this->makeItem('achievements', 'অর্জন', 'section', section: 'achievements'),
            $this->makeItem('faculty', 'শিক্ষক', 'section', section: 'faculty'),
            $this->makeItem('facilities', 'সুবিধা', 'section', section: 'facilities'),
            $this->makeItem('blog', 'ব্লগ', 'blog'),
            $this->makeItem('gallery', 'গ্যালারি', 'section', section: 'gallery'),
            $this->makeItem('contact', 'যোগাযোগ', 'section', section: 'contact'),
        ];
    }

    /**
     * @return list<array{id: string, label: string, value: string}>
     */
    public function homepageSections(): array
    {
        return [
            ['id' => 'home', 'label' => 'হোম', 'value' => 'home'],
            ['id' => 'about', 'label' => 'পরিচিতি', 'value' => 'about'],
            ['id' => 'mission', 'label' => 'মিশন ও ভিশন', 'value' => 'mission'],
            ['id' => 'achievements', 'label' => 'গৌরবের অর্জন', 'value' => 'achievements'],
            ['id' => 'faculty', 'label' => 'শিক্ষকমণ্ডলী', 'value' => 'faculty'],
            ['id' => 'facilities', 'label' => 'স্কুলের সুবিধাসমূহ', 'value' => 'facilities'],
            ['id' => 'blog', 'label' => 'ব্লগ ও সংবাদ', 'value' => 'blog'],
            ['id' => 'gallery', 'label' => 'ফটো গ্যালারী', 'value' => 'gallery'],
            ['id' => 'principal', 'label' => 'অধ্যক্ষের বাণী', 'value' => 'principal'],
            ['id' => 'contact', 'label' => 'যোগাযোগ', 'value' => 'contact'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveStored(?SchoolFrontendSetting $settings): array
    {
        $stored = $settings?->frontend_menus;

        if (is_string($stored)) {
            $stored = json_decode($stored, true) ?: [];
        }

        if (! is_array($stored) || empty($stored['menus'])) {
            return $this->defaults();
        }

        $defaults = $this->defaults();

        return [
            'menus' => $stored['menus'],
            'locations' => array_merge($defaults['locations'], $stored['locations'] ?? []),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forLocation(?SchoolFrontendSetting $settings, School $school, string $location): array
    {
        $config = $this->resolveStored($settings);
        $menuId = $config['locations'][$location] ?? null;

        if (! $menuId) {
            return [];
        }

        $menu = collect($config['menus'] ?? [])->firstWhere('id', $menuId);
        $items = is_array($menu['items'] ?? null) ? $menu['items'] : [];

        return $this->resolveItems($items, $school);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function resolveItems(array $items, School $school): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && ! empty($item['label']))
            ->map(function (array $item) use ($school) {
                $children = is_array($item['children'] ?? null) ? $item['children'] : [];

                return [
                    'id' => $item['id'] ?? Str::uuid()->toString(),
                    'label' => trim((string) ($item['label'] ?? '')),
                    'type' => $item['type'] ?? 'custom',
                    'url' => $this->resolveUrl($item, $school),
                    'target' => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                    'children' => $this->resolveItems($children, $school),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function resolveUrl(array $item, School $school): string
    {
        $type = $item['type'] ?? 'custom';

        return match ($type) {
            'home' => url('/'),
            'blog' => url('/blog'),
            'admission' => url('/admission/'.$school->code),
            'section' => url('/#'.($item['section'] ?? 'home')),
            'page' => $this->resolvePageUrl($item, $school),
            default => $this->normalizeCustomUrl((string) ($item['url'] ?? '#')),
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function resolvePageUrl(array $item, School $school): string
    {
        $pageId = $item['page_id'] ?? null;

        if ($pageId) {
            $page = CmsPage::forSchool($school->id)->published()->find($pageId);
            if ($page?->slug) {
                return url('/'.$page->slug);
            }
        }

        $slug = trim((string) ($item['page_slug'] ?? ''));

        return $slug !== '' ? url('/'.$slug) : url('/');
    }

    protected function normalizeCustomUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '' || $url === '#') {
            return '#';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) {
            return $url;
        }

        return url('/'.$url);
    }

    /**
     * @return list<array{id: int, title: string, slug: string}>
     */
    public function pagesForSchool(int $schoolId): array
    {
        return CmsPage::forSchool($schoolId)
            ->published()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'slug'])
            ->map(fn (CmsPage $page) => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeItem(string $id, string $label, string $type, ?string $section = null, ?int $pageId = null, ?string $url = null): array
    {
        return array_filter([
            'id' => $id,
            'label' => $label,
            'type' => $type,
            'section' => $section,
            'page_id' => $pageId,
            'url' => $url,
            'target' => '_self',
            'children' => [],
        ], fn ($value) => $value !== null);
    }
}
