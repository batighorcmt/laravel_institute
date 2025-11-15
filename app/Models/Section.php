<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = ['school_id','class_id','name','class_teacher_name','status'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function scopeForSchool($q, $schoolId)
    {
        // Qualify column to avoid ambiguity after joins
        return $q->where('sections.school_id', $schoolId);
    }

    public function scopeOrdered($q)
    {
        // Order by related class numeric_value, then by section name
        return $q->leftJoin('classes','classes.id','=','sections.class_id')
                 ->orderBy('classes.numeric_value')
                 ->orderBy('sections.name')
                 ->select('sections.*');
    }
}
