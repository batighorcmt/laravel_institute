<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonEvaluationRecord extends Model
{
    protected $fillable = [
        'lesson_evaluation_id',
        'student_id',
        'status',
        'remarks',
    ];

    public function lessonEvaluation(): BelongsTo
    {
        return $this->belongsTo(LessonEvaluation::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Status labels in Bangla
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'completed' => 'পড়া হয়েছে',
            'partial' => 'আংশিক হয়েছে',
            'not_done' => 'পড়া হয়নি',
            'absent' => 'অনুপস্থিত',
            default => $this->status,
        };
    }

    // Status badge color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'success',
            'partial' => 'warning',
            'not_done' => 'danger',
            'absent' => 'secondary',
            default => 'info',
        };
    }
}
