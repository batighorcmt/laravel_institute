<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id','school_id','academic_year','class_id','section_id','group_id','roll_no','status'
    ];

    protected $casts = [
        'academic_year' => 'integer',
        'roll_no' => 'integer'
    ];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class, 'academic_year'); }
    public function class(): BelongsTo { return $this->belongsTo(SchoolClass::class,'class_id'); }
    public function section(): BelongsTo { return $this->belongsTo(Section::class); }
    public function group(): BelongsTo { return $this->belongsTo(Group::class); }

    public function subjects()
    {
        return $this->hasMany(StudentSubject::class, 'student_enrollment_id');
    }
}
