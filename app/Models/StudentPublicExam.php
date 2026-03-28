<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPublicExam extends Model
{
    protected $fillable = [
        'student_id',
        'school_id',
        'exam_name',
        'board',
        'roll_no',
        'reg_no',
        'exam_year',
        'session',
        'candidate_type',
        'center_name',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
