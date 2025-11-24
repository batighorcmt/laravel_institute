<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mark extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'exam_subject_id',
        'student_id',
        'subject_id',
        'creative_marks',
        'mcq_marks',
        'practical_marks',
        'total_marks',
        'letter_grade',
        'grade_point',
        'pass_status',
        'is_absent',
        'remarks',
        'entered_by',
        'entered_at',
    ];

    protected $casts = [
        'creative_marks' => 'decimal:2',
        'mcq_marks' => 'decimal:2',
        'practical_marks' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'grade_point' => 'decimal:2',
        'is_absent' => 'boolean',
        'entered_at' => 'datetime',
    ];

    // Relationships
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSubject(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    // Scopes
    public function scopeForExam($query, $examId)
    {
        return $query->where('exam_id', $examId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopePassed($query)
    {
        return $query->where('pass_status', 'pass');
    }

    public function scopeFailed($query)
    {
        return $query->where('pass_status', 'fail');
    }

    public function scopeAbsent($query)
    {
        return $query->where('pass_status', 'absent');
    }
}
