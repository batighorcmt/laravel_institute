<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteMenuTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'config',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function schoolFrontendSettings(): HasMany
    {
        return $this->hasMany(SchoolFrontendSetting::class, 'applied_menu_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
