<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolFrontendSetting extends Model
{
    protected $fillable = [
        'school_id',
        'hero_title',
        'hero_subtitle',
        'hero_image',
        'about_text',
        'about_image',
        'principal_name',
        'principal_message',
        'principal_image',
        'facebook_url',
        'youtube_url',
        'marquee_text',
        'contact_address',
        'contact_email',
        'contact_phone',
        'committee_text',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
