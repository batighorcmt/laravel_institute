<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One start/end time per (school, class, section, period_number) — set once
 * so routine entries across all seven days reuse it instead of the principal
 * retyping the same time for every day×period cell.
 */
class ClassPeriodTime extends Model
{
    protected $fillable = ['school_id', 'class_id', 'section_id', 'period_number', 'start_time', 'end_time'];

    public function scopeForSchool(Builder $q, $schoolId): Builder
    {
        return $q->where('school_id', $schoolId);
    }

    public function scopeForClassSection(Builder $q, $classId, $sectionId): Builder
    {
        return $q->where('class_id', $classId)->where('section_id', $sectionId);
    }
}
