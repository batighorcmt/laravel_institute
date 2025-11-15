<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    protected $fillable = ['school_id','name','start_time','end_time','status'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeForSchool($q, $schoolId)
    {
        return $q->where('school_id', $schoolId);
    }
}
