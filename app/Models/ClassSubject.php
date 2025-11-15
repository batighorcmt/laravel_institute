<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubject extends Model
{
    protected $fillable = [
        'school_id','class_id','group_id','subject_id','is_optional','offered_mode','order_no','status'
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'order_no' => 'integer',
        'offered_mode' => 'string',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scopeForSchool($q, $schoolId)
    {
        return $q->where('school_id', $schoolId);
    }
}
