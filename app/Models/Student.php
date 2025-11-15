<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'school_id', 'class_id', 'student_id', 'student_name_en','student_name_bn',
        'date_of_birth', 'gender', 'father_name', 'mother_name','father_name_bn','mother_name_bn', 'guardian_phone',
        'address', 'blood_group', 'photo', 'admission_date', 'status'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'status' => 'string',
    ];

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->student_name_bn ?: $this->student_name_en ?: '');
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age ?? 0;
    }

    // Boot method to generate student ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->student_id)) {
                $student->student_id = static::generateStudentId($student->school_id);
            }
        });
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class,'team_student')->withTimestamps()->withPivot(['joined_at','status']);
    }

    public static function generateStudentId($schoolId): string
    {
        $school = School::find($schoolId);
        $year = date('Y');
        $count = static::where('school_id', $schoolId)
            ->whereYear('created_at', $year)
            ->count() + 1;
        
        return $school->code . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
