<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name', 'code', 'address', 'phone', 'email', 'website', 
        'description', 'logo', 'status', 'admissions_enabled',
        'admission_academic_year_id'
    ];

    protected $casts = [
        'status' => 'string',
        'admissions_enabled' => 'boolean',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(UserSchoolRole::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function admissionAcademicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'admission_academic_year_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
