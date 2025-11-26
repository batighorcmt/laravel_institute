<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class RoutineEntry extends Model
{
    protected $fillable = [
        'school_id','class_id','section_id','day_of_week','period_number','subject_id','teacher_id','start_time','end_time','room','remarks'
    ];

    protected $casts = [
        'period_number' => 'integer',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function class(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function section(): BelongsTo { return $this->belongsTo(Section::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }

    public function scopeForSchool(Builder $q, $schoolId): Builder { return $q->where('school_id', $schoolId); }
    public function scopeForClassSection(Builder $q, $classId, $sectionId): Builder { return $q->where('class_id',$classId)->where('section_id',$sectionId); }
    public function scopeOrdered(Builder $q): Builder { return $q->orderBy('day_of_week')->orderBy('period_number'); }
}
