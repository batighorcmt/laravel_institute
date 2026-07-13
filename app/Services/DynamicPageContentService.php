<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolFrontendSetting;

class DynamicPageContentService
{
    /** @var array<int, string> */
    public const SUPPORTED_SOURCES = ['teachers', 'notices', 'gallery', 'about', 'contact', 'committee'];

    public function __construct(
        protected FrontendHomepageContentService $homepageContent,
        protected FrontendNoticeService $notices,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(string $dataSource, School $school, ?SchoolFrontendSetting $settings): array
    {
        return match ($dataSource) {
            'teachers' => ['teachers' => $this->homepageContent->teachersForSchool($school->id)],
            'notices' => ['notices' => $this->notices->allBoardNoticesForSchool($school->id)->values()->all()],
            'gallery' => ['gallery' => $this->resolveGallery($school, $settings)],
            'about' => $this->resolveAbout($settings),
            'contact' => $this->resolveContact($school, $settings),
            'committee' => $this->resolveCommittee($settings),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveCommittee(?SchoolFrontendSetting $settings): array
    {
        $content = $this->homepageContent->resolve($settings);

        return [
            'intro' => $settings?->committee_text,
            'members' => $content['committee_members'] ?? [],
        ];
    }

    /**
     * @return list<string>
     */
    protected function resolveGallery(School $school, ?SchoolFrontendSetting $settings): array
    {
        $content = $this->homepageContent->resolve($settings);
        $gallery = $content['gallery'] ?? [];

        return $gallery !== [] ? $gallery : $this->homepageContent->placeholderGallery($school, $settings);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveAbout(?SchoolFrontendSetting $settings): array
    {
        $content = $this->homepageContent->resolve($settings);

        return [
            'about_text' => $settings?->about_text,
            'about_image' => $settings?->about_image,
            'principal_name' => $settings?->principal_name,
            'principal_message' => $settings?->principal_message,
            'principal_image' => $settings?->principal_image,
            'chairman_name' => $settings?->chairman_name,
            'chairman_message' => $settings?->chairman_message,
            'chairman_image' => $settings?->chairman_image,
            'mission' => $content['mission'] ?? null,
            'vision' => $content['vision'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveContact(School $school, ?SchoolFrontendSetting $settings): array
    {
        return [
            'address' => $settings?->contact_address ?: $school->address,
            'email' => $settings?->contact_email ?: $school->email,
            'phone' => $settings?->contact_phone ?: $school->displayPhone(),
            'facebook_url' => $settings?->facebook_url,
            'youtube_url' => $settings?->youtube_url,
        ];
    }
}
