<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicYear extends Model
{
    protected $fillable = ['school_id','name','start_date','end_date','is_current'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }

    public function scopeForSchool($q,$schoolId){ return $q->where('school_id',$schoolId); }
    public function scopeCurrent($q){ return $q->where('is_current',true); }
}
