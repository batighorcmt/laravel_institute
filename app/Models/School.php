<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $name_bn
 * @property string|null $code
 * @property string|null $address
 * @property string|null $address_bn
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $logo
 * @property int|null $admission_academic_year_id
 */
class School extends Model
{
    protected $fillable = [
        'name', 'name_bn', 'code', 'address', 'address_bn', 'phone', 'email', 'website', 
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

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
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

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function weeklyHolidays(): HasMany
    {
        return $this->hasMany(WeeklyHoliday::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
