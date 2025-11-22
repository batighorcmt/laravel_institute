<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraClassEnrollment extends Model
{
    protected $fillable = [
        'extra_class_id',
        'student_id',
        'assigned_section_id',
        'enrolled_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'enrolled_date' => 'date',
        'status' => 'string',
    ];

    public function extraClass(): BelongsTo
    {
        return $this->belongsTo(ExtraClass::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignedSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'assigned_section_id');
    }
}
