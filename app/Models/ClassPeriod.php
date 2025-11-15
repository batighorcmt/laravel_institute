<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ClassPeriod extends Model
{
    protected $fillable = ['school_id','class_id','section_id','period_count'];

    public function scopeForSchool(Builder $q, $schoolId): Builder { return $q->where('school_id', $schoolId); }
    public function scopeForClassSection(Builder $q, $classId, $sectionId): Builder { return $q->where('class_id',$classId)->where('section_id',$sectionId); }
}
