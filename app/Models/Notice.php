<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    protected $fillable = [
        'school_id', 'title', 'body', 'publish_at', 'expiry_at', 'status', 'created_by',
        'audience_type', 'audience_channels', 'reply_required', 'attachment_path',
        'show_on_frontend_marquee', 'show_on_frontend_board',
    ];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'expiry_at' => 'datetime',
            'reply_required' => 'boolean',
            'show_on_frontend_marquee' => 'boolean',
            'show_on_frontend_board' => 'boolean',
            'audience_channels' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public function resolvedAudienceChannels(): array
    {
        if (is_array($this->audience_channels) && $this->audience_channels !== []) {
            return array_values($this->audience_channels);
        }

        $channels = [];

        if (in_array($this->audience_type, ['all', 'teachers'], true)) {
            $channels[] = 'teachers';
        }

        if (in_array($this->audience_type, ['all', 'students'], true)) {
            $channels[] = 'students';
        }

        if ($this->show_on_frontend_board || $this->show_on_frontend_marquee) {
            $channels[] = 'website';
        }

        return array_values(array_unique($channels));
    }

    public function scopeVisibleInApp($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('audience_channels')
                ->orWhereJsonContains('audience_channels', 'teachers')
                ->orWhereJsonContains('audience_channels', 'students');
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets()
    {
        return $this->hasMany(NoticeTarget::class);
    }

    public function reads()
    {
        return $this->hasMany(NoticeRead::class);
    }

    public function replies()
    {
        return $this->hasMany(NoticeReply::class);
    }

    // Scopes
    public function scopePublished($q)
    {
        return $q->where('status', 'published')->where(function ($qq) {
            $qq->whereNull('publish_at')->orWhere('publish_at', '<=', now());
        });
    }

    public function scopeForSchool($q, $schoolId)
    {
        return $q->where(function ($qq) use ($schoolId) {
            $qq->whereNull('school_id')->orWhere('school_id', $schoolId);
        });
    }

    public function scopeActive($q)
    {
        return $q->where(function ($qq) {
            $qq->whereNull('expiry_at')->orWhere('expiry_at', '>', now());
        });
    }
}
