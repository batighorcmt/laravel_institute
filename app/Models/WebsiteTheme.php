<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteTheme extends Model
{
    public const TEMPLATE_ONE = 'theme-1';

    public const TEMPLATE_TWO = 'theme-2';

    protected $fillable = [
        'name',
        'slug',
        'template_key',
        'description',
        'colors',
        'font_family',
        'preview_image',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'colors' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function schoolFrontendSettings(): HasMany
    {
        return $this->hasMany(SchoolFrontendSetting::class, 'theme_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
