<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'school_id','key','value'
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }

    public function scopeForSchool($q, $schoolId){ return $q->where('school_id', $schoolId); }

    public static function getDecimalPosition($schoolId)
    {
        return (int) (self::where('school_id', $schoolId)->where('key', 'marks_decimal_position')->first()?->value ?? 2);
    }
}
