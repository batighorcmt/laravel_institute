<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPage extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const MODE_DYNAMIC = 'dynamic';

    public const MODE_STATIC = 'static';

    protected $fillable = [
        'school_id',
        'author_id',
        'title',
        'slug',
        'content',
        'content_mode',
        'data_source',
        'page_template_id',
        'status',
        'published_at',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'robots',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function pageTemplate(): BelongsTo
    {
        return $this->belongsTo(WebsitePageTemplate::class, 'page_template_id');
    }

    public function isDynamic(): bool
    {
        return $this->content_mode === self::MODE_DYNAMIC;
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ($this->published_at === null || $this->published_at->lte(now()));
    }
}
