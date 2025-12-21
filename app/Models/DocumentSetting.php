<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSetting extends Model
{
    protected $fillable = [
        'school_id', 'page', 'background_path', 'colors'
    ];

    protected $casts = [
        'colors' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
