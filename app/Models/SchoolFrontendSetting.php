<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolFrontendSetting extends Model
{
    protected $fillable = [
        'school_id',
        'theme_id',
        'theme_overrides',
        'applied_menu_template_id',
        'applied_at',
        'hero_title',
        'hero_subtitle',
        'hero_image',
        'hero_images',
        'about_text',
        'about_image',
        'principal_name',
        'principal_message',
        'principal_image',
        'principal_feature_image',
        'principal_title',
        'principal_designation',
        'chairman_name',
        'chairman_message',
        'chairman_image',
        'chairman_feature_image',
        'chairman_title',
        'chairman_designation',
        'about_images',
        'facebook_url',
        'youtube_url',
        'marquee_text',
        'contact_address',
        'contact_email',
        'contact_phone',
        'contact_mobile',
        'contact_website',
        'dshe_info_center',
        'dshe_info_mobile',
        'gro_name',
        'gro_designation',
        'gro_mobile',
        'office_hours',
        'map_embed_url',
        'committee_text',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'homepage_content',
        'frontend_menus',
    ];

    protected $casts = [
        'hero_images' => 'array',
        'about_images' => 'array',
        'homepage_content' => 'array',
        'frontend_menus' => 'array',
        'theme_overrides' => 'array',
        'applied_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function theme()
    {
        return $this->belongsTo(WebsiteTheme::class, 'theme_id');
    }

    public function appliedMenuTemplate()
    {
        return $this->belongsTo(WebsiteMenuTemplate::class, 'applied_menu_template_id');
    }
}
