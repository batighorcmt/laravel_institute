<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonEvaluation extends Model
{
    protected $fillable = [
        'school_id',
        'teacher_id',
        'class_id',
        'section_id',
        'subject_id',
        'routine_entry_id',
        'evaluation_date',
        'evaluation_time',
        'notes',
        'status',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'evaluation_time' => 'datetime:H:i',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function class(): BelongsTo
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

    public function routineEntry(): BelongsTo
    {
        return $this->belongsTo(RoutineEntry::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(LessonEvaluationRecord::class);
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('evaluation_date', $date);
    }

    // Get completion statistics
    public function getCompletionStats()
    {
        // Only consider records whose related student is active
        $base = $this->records()->whereHas('student', fn($q) => $q->where('status','active'));
        $total = $base->count();
        $completed = (clone $base)->where('status', 'completed')->count();
        $partial = (clone $base)->where('status', 'partial')->count();
        $notDone = (clone $base)->where('status', 'not_done')->count();
        $absent = (clone $base)->where('status', 'absent')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'partial' => $partial,
            'not_done' => $notDone,
            'absent' => $absent,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }
}
