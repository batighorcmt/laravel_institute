<?php

namespace App\Services;

use App\Models\Notice;
use Illuminate\Support\Collection;

class FrontendNoticeService
{
    /**
     * @return Collection<int, array{id: int, title: string, publish_at: ?string, publish_at_label: ?string, attachment_url: ?string, attachment_name: ?string, download_url: ?string}>
     */
    public function boardNoticesForSchool(int $schoolId, int $limit = 10): Collection
    {
        return $this->baseQuery($schoolId)
            ->where('show_on_frontend_board', true)
            ->limit($limit)
            ->get()
            ->map(fn (Notice $notice) => $this->mapNotice($notice));
    }

    /**
     * @return Collection<int, array{id: int, title: string, publish_at: ?string, publish_at_label: ?string, attachment_url: ?string, attachment_name: ?string, download_url: ?string}>
     */
    public function allBoardNoticesForSchool(int $schoolId): Collection
    {
        return $this->baseQuery($schoolId)
            ->where('show_on_frontend_board', true)
            ->get()
            ->map(fn (Notice $notice) => $this->mapNotice($notice));
    }

    /**
     * @return Collection<int, array{id: int, title: string, publish_at: ?string}>
     */
    public function marqueeNoticesForSchool(int $schoolId, int $limit = 8): Collection
    {
        return $this->baseQuery($schoolId)
            ->where('show_on_frontend_marquee', true)
            ->limit($limit)
            ->get(['id', 'title', 'publish_at'])
            ->map(fn (Notice $notice) => [
                'id' => $notice->id,
                'title' => $notice->title,
                'publish_at' => $notice->publish_at?->toDateTimeString(),
            ]);
    }

    public function findDownloadableNotice(int $schoolId, int $noticeId): ?Notice
    {
        return $this->baseQuery($schoolId)
            ->where('show_on_frontend_board', true)
            ->whereKey($noticeId)
            ->first();
    }

    /**
     * @return array{id: int, title: string, publish_at: ?string, publish_at_label: ?string, attachment_url: ?string, attachment_name: ?string, download_url: ?string}
     */
    protected function mapNotice(Notice $notice): array
    {
        $hasAttachment = (bool) $notice->attachment_path;

        return [
            'id' => $notice->id,
            'title' => $notice->title,
            'body' => $notice->body,
            'publish_at' => $notice->publish_at?->toDateTimeString(),
            'publish_at_label' => $notice->publish_at?->format('d M Y'),
            'attachment_url' => $hasAttachment ? storage_asset($notice->attachment_path) : null,
            'attachment_name' => $hasAttachment ? basename($notice->attachment_path) : null,
            'download_url' => $hasAttachment ? route('frontend.notices.download', $notice) : null,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Notice>
     */
    protected function baseQuery(int $schoolId)
    {
        return Notice::query()
            ->forSchool($schoolId)
            ->published()
            ->active()
            ->orderByDesc('publish_at')
            ->orderByDesc('id');
    }
}
