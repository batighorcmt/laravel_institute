<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    protected $fillable = [
        'school_id', 'class_id', 'student_id', 'student_name_en','student_name_bn',
        'date_of_birth', 'gender', 'father_name', 'mother_name','father_name_bn','mother_name_bn', 'guardian_phone',
        'guardian_relation','guardian_name_en','guardian_name_bn',
        'address','present_address','permanent_address',
        'present_village','present_para_moholla','present_post_office','present_upazilla','present_district',
        'permanent_village','permanent_para_moholla','permanent_post_office','permanent_upazilla','permanent_district',
        'blood_group', 'photo', 'admission_date', 'status',
        'previous_school','pass_year','previous_result','previous_remarks'
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

    // Return a URL for the student's photo, trying common storage locations.
    public function getPhotoUrlAttribute(): string
    {
        if (empty($this->photo)) {
            return asset('images/default-avatar.svg');
        }

        // 1) If stored directly in public path (rare)
        if (file_exists(public_path($this->photo))) {
            return asset($this->photo);
        }

        // 2) If stored in storage/app/public (accessible via /storage/... when storage:link exists)
        if (file_exists(storage_path('app/public/' . $this->photo))) {
            return asset('storage/' . ltrim($this->photo, '/'));
        }

        // 3) If stored in storage/app (not public) but present, try to serve via storage URL (may require storage:link)
        if (file_exists(storage_path('app/' . $this->photo))) {
            return asset('storage/' . ltrim($this->photo, '/'));
        }

        // 4) As a last resort, if the default filesystem can generate a URL
        try {
            if (Storage::exists($this->photo)) {
                return Storage::url($this->photo);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return asset('images/default-avatar.svg');
    }
}
