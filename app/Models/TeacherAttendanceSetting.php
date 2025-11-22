<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendanceSetting extends Model
{
    protected $fillable = [
        'school_id',
        'check_in_start',
        'check_in_end',
        'late_threshold',
        'check_out_start',
        'check_out_end',
        'require_photo',
        'require_location',
    ];

    protected $casts = [
        'require_photo' => 'boolean',
        'require_location' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
