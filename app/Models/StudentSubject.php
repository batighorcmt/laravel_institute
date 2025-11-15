<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_enrollment_id',
        'subject_id',
        'is_optional',
        'status',
    ];

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
