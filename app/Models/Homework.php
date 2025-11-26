<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Homework extends Model
{
    protected $table = 'homeworks';
    
    protected $fillable = [
        'school_id',
        'class_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'homework_date',
        'submission_date',
        'title',
        'description',
        'attachment',
    ];

    protected $casts = [
        'homework_date' => 'date',
        'submission_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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
        return $this->belongsTo(Teacher::class);
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('homework_date', $date);
    }

    public function scopeForClass($query, $classId, $sectionId = null)
    {
        $query->where('class_id', $classId);
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
        return $query;
    }
}
