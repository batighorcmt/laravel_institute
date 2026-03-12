<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSetting extends Model
{
    protected $fillable = [
        'school_id', 'page', 'background_path', 'colors', 'memo_format', 'custom_text', 'custom_text_en'
    ];

    protected $casts = [
        'colors' => 'array',
        'memo_format' => 'array',
        'custom_text' => 'array',
        'custom_text_en' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
