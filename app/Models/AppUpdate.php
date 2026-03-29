<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUpdate extends Model
{
    protected $fillable = [
        'version_code',
        'version_name',
        'apk_url',
        'release_notes',
        'is_mandatory',
        'is_active',
    ];
}
