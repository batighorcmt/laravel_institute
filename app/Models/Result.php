<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'class_id',
        'section_id',
        'total_marks',
        'total_possible_marks',
        'percentage',
        'gpa',
        'letter_grade',
        'result_status',
        'failed_subjects_count',
        'absent_subjects_count',
        'class_position',
        'section_position',
        'merit_position',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'total_possible_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
        'gpa' => 'decimal:2',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
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

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopePassed($query)
    {
        return $query->where('result_status', 'pass');
    }

    public function scopeFailed($query)
    {
        return $query->where('result_status', 'fail');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrderByMerit($query)
    {
        return $query->orderBy('gpa', 'desc')->orderBy('total_marks', 'desc');
    }
}
