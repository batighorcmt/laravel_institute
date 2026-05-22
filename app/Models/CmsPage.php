<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPage extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'school_id',
        'author_id',
        'title',
        'slug',
        'content',
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
