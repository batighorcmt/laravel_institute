<?php

namespace App\Services;

use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Models\StaffMember;
use Illuminate\Support\Carbon;

class DynamicPageContentService
{
    /** @var array<int, string> */
    public const SUPPORTED_SOURCES = ['teachers', 'staff', 'notices', 'gallery', 'about', 'contact', 'committee'];

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
            'staff' => ['staff' => $this->staffForSchool($school->id)],
            'notices' => ['notices' => $this->notices->allBoardNoticesForSchool($school->id)->values()->all()],
            'gallery' => ['gallery' => $this->resolveGallery($school, $settings)],
            'about' => $this->resolveAbout($settings),
            'contact' => $this->resolveContact($school, $settings),
            'committee' => $this->resolveCommittee($settings),
            default => [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function staffForSchool(int $schoolId): array
    {
        return StaffMember::where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('show_on_website', true)
            ->with('designationRef:id,name_en,name_bn')
            ->orderByRaw('COALESCE(serial_number, 999999)')
            ->orderBy('id')
            ->get()
            ->map(fn (StaffMember $staff) => [
                'id' => $staff->id,
                'name' => $staff->full_name_bn ?: ($staff->full_name ?: 'কর্মচারী'),
                'designation' => $staff->designationRef ? ($staff->designationRef->name_bn ?: $staff->designationRef->name_en) : 'কর্মচারী',
                'phone' => $staff->phone,
                'photo' => $staff->photo_url,
            ])
            ->values()
            ->all();
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
     * @return array<string, mixed>
     */
    protected function resolveGallery(School $school, ?SchoolFrontendSetting $settings): array
    {
        $latestImages = GalleryImage::where('school_id', $school->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        if ($latestImages->isEmpty()) {
            $fallback = $this->homepageContent->placeholderGallery($school, $settings);

            return [
                'latest' => collect($fallback)->map(fn ($url, $i) => ['id' => 'placeholder-'.$i, 'url' => $url])->values()->all(),
                'albums' => [],
                'last_updated' => null,
            ];
        }

        $albums = GalleryAlbum::where('school_id', $school->id)
            ->withCount('images')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (GalleryAlbum $album) => $this->presentAlbum($album))
            ->values()
            ->all();

        $lastUpdated = GalleryImage::where('school_id', $school->id)->max('created_at');

        return [
            'latest' => $latestImages->map(fn (GalleryImage $img) => ['id' => $img->id, 'url' => storage_asset($img->path)])->values()->all(),
            'albums' => $albums,
            'last_updated' => $lastUpdated ? Carbon::parse($lastUpdated)->translatedFormat('d F Y, h:i A') : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveAlbum(School $school, GalleryAlbum $album): array
    {
        $images = $album->images()
            ->where('school_id', $school->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (GalleryImage $img) => ['id' => $img->id, 'url' => storage_asset($img->path)])
            ->values()
            ->all();

        return [
            'album' => $this->presentAlbum($album),
            'images' => $images,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentAlbum(GalleryAlbum $album): array
    {
        $thumbs = $album->images()
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(fn (GalleryImage $img) => storage_asset($img->path))
            ->values()
            ->all();

        return [
            'id' => $album->id,
            'name' => $album->name,
            'description' => $album->description,
            'images_count' => $album->images_count ?? $album->images()->count(),
            'thumbnails' => $thumbs,
            'created_at' => $album->created_at?->translatedFormat('d F Y'),
        ];
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
