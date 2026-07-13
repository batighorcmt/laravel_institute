<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsitePageTemplate extends Model
{
    public const MODE_DYNAMIC = 'dynamic';

    public const MODE_STATIC = 'static';

    protected $fillable = [
        'key',
        'title',
        'title_bn',
        'default_slug',
        'content_mode',
        'data_source',
        'default_content',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function cmsPages(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'page_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
