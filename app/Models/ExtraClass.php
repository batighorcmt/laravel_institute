<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraClass extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'name',
        'description',
        'schedule',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ExtraClassEnrollment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ExtraClassAttendance::class);
    }
}
