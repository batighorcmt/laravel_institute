<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'subject_id',
        'combine_group',
        'teacher_id',
        'creative_full_mark',
        'creative_pass_mark',
        'mcq_full_mark',
        'mcq_pass_mark',
        'practical_full_mark',
        'practical_pass_mark',
        'pass_type',
        'total_full_mark',
        'total_pass_mark',
        'mark_entry_deadline',
        'mark_entry_completed',
        'exam_date',
        'exam_start_time',
        'exam_end_time',
        'display_order',
    ];

    protected $casts = [
        'mark_entry_deadline' => 'datetime',
        'mark_entry_completed' => 'boolean',
        'exam_date' => 'date',
    ];

    // Relationships
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    // Accessors
    public function getHasCreativeAttribute(): bool
    {
        return $this->creative_full_mark > 0;
    }

    public function getHasMcqAttribute(): bool
    {
        return $this->mcq_full_mark > 0;
    }

    public function getHasPracticalAttribute(): bool
    {
        return $this->practical_full_mark > 0;
    }

    // Scopes
    public function scopeForExam($query, $examId)
    {
        return $query->where('exam_id', $examId);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeOrderByDisplay($query)
    {
        return $query->orderBy('display_order');
    }
}
