<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use BelongsToSchool;

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
        'designation_id',
        'initials',
        'serial_number',
        'date_of_birth',
        'joining_date',
        'academic_info',
        'qualification',
        'photo',
        'signature',
        'status',
        'job_type',
        'show_on_website',
        'present_division_id',
        'present_district_id',
        'present_thana_id',
        'present_post_office',
        'present_post_office_en',
        'present_village',
        'present_village_en',
        'permanent_division_id',
        'permanent_district_id',
        'permanent_thana_id',
        'permanent_post_office',
        'permanent_post_office_en',
        'permanent_village',
        'permanent_village_en',
        'biometric_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'status' => 'string',
        'show_on_website' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* Managed by BelongsToSchool trait
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
    */

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
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFullNameBnAttribute(): string
    {
        return trim(($this->first_name_bn ?? '').' '.($this->last_name_bn ?? ''));
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

    public function noticeTargets()
    {
        return $this->morphMany(NoticeTarget::class, 'targetable');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo)) {
            return null;
        }

        // 1) Check in teachers folder
        $path = 'teachers/'.$this->photo;
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        // 2) If stored directly in public path
        if (file_exists(public_path($this->photo))) {
            return asset($this->photo);
        }

        // 3) If stored in storage/app/public (accessible via /storage/...)
        if (file_exists(storage_path('app/public/'.$this->photo))) {
            return asset('storage/'.ltrim($this->photo, '/'));
        }

        // 4) Fallback to students folder
        $studentsPath = 'students/'.$this->photo;
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($studentsPath)) {
            return asset('storage/'.$studentsPath);
        }

        return null;
    }

    public function presentDivision(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'present_division_id');
    }

    public function presentDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'present_district_id');
    }

    public function presentThana(): BelongsTo
    {
        return $this->belongsTo(Thana::class, 'present_thana_id');
    }

    public function permanentDivision(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'permanent_division_id');
    }

    public function permanentDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'permanent_district_id');
    }

    public function permanentThana(): BelongsTo
    {
        return $this->belongsTo(Thana::class, 'permanent_thana_id');
    }

    public function designationRef(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
}
