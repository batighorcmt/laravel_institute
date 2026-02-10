<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'first_name',
        'last_name',
        'first_name_bn',
        'last_name_bn',
        'father_name_bn',
        'father_name_en',
        'mother_name_bn',
        'mother_name_en',
        'phone',
        'plain_password',
        'designation',
        'initials',
        'serial_number',
        'date_of_birth',
        'joining_date',
        'academic_info',
        'qualification',
        'photo',
        'signature',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'status' => 'string',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacherAttendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class, 'user_id', 'user_id');
    }

    public function teacherLeaves(): HasMany
    {
        return $this->hasMany(TeacherLeave::class);
    }

    // Accessor for full name
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFullNameBnAttribute(): string
    {
        return trim(($this->first_name_bn ?? '') . ' ' . ($this->last_name_bn ?? ''));
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

    
}
