<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolStatsSetting extends Model
{
    public const MODE_DYNAMIC = 'dynamic';

    public const MODE_STATIC = 'static';

    protected $fillable = [
        'school_id',
        'mode',
        'static_students_count',
        'static_teachers_count',
        'static_staff_count',
        'static_classes_count',
        'static_founding_year',
    ];

    protected $casts = [
        'static_students_count' => 'integer',
        'static_teachers_count' => 'integer',
        'static_staff_count' => 'integer',
        'static_classes_count' => 'integer',
        'static_founding_year' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isStatic(): bool
    {
        return $this->mode === self::MODE_STATIC;
    }
}
