<?php
/**
 * App\Models\Student
 *
 * @property int $id
 * @property int $school_id
 * @property int|null $class_id
 * @property string|null $student_id
 * @property string|null $student_name_en
 * @property string $student_name_bn
 * @property string $date_of_birth
 * @property string $gender
 * @property string $father_name
 * @property string $mother_name
 * @property string $father_name_bn
 * @property string $mother_name_bn
 * @property string $guardian_phone
 * @property string|null $address
 * @property string|null $present_address
 * @property string|null $permanent_address
 * @property string|null $present_village
 * @property string|null $present_para_moholla
 * @property string|null $present_post_office
 * @property string|null $present_upazilla
 * @property string|null $present_district
 * @property string|null $permanent_village
 * @property string|null $permanent_para_moholla
 * @property string|null $permanent_post_office
 * @property string|null $permanent_upazilla
 * @property string|null $permanent_district
 * @property string|null $blood_group
 * @property string|null $photo
 * @property string $admission_date
 * @property string $status
 * @property string|null $previous_school
 * @property string|null $pass_year
 * @property string|null $previous_result
 * @property string|null $previous_remarks
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    protected $fillable = [
        'school_id', 'class_id', 'optional_subject_id', 'admission_id', 'student_id', 'student_name_en','student_name_bn',
        'date_of_birth', 'gender', 'religion', 'father_name', 'mother_name','father_name_bn','mother_name_bn', 'guardian_phone',
        'guardian_relation','guardian_name_en','guardian_name_bn',
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

    protected $appends = ['roll'];

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function optionalSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'optional_subject_id');
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

    public function getRollAttribute()
    {
        return $this->currentEnrollment ? $this->currentEnrollment->roll_no : null;
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function currentEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class)->where('status', 'active')->latest();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class,'team_student')->withTimestamps()->withPivot(['joined_at','status']);
    }

    /**
     * Generate unique student ID
     * New Format: <school_code>S<sequential_5digits>
     * Example: JSSS00001, JSSS00002
     *
     * Note: Kept the second parameter in the signature for backward compatibility,
     * but it is ignored by this new scheme.
     *
     * @param int $schoolId
     * @param int $classNumericValue Ignored
     * @return string
     */
    public static function generateStudentId($schoolId, $classNumericValue): string
    {
        $school = School::find($schoolId);
        $schoolCode = $school ? ($school->code ?? 'SCH') : 'SCH';
        // Year last 2 digits
        $year = date('y');
        $prefix = $schoolCode . $year;

        // Find last serial for this school and year
        $lastStudent = self::where('school_id', $schoolId)
            ->where('student_id', 'LIKE', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(student_id, ' . (strlen($prefix) + 1) . ', 4) AS UNSIGNED) DESC')
            ->first();

        $serial = 1;
        if ($lastStudent && preg_match('/^' . preg_quote($prefix, '/') . '(\d{4})$/', $lastStudent->student_id, $matches)) {
            $serial = intval($matches[1]) + 1;
        }

        return $prefix . str_pad($serial, 4, '0', STR_PAD_LEFT);
    }

    // Return a URL for the student's photo, trying common storage locations.
    public function getPhotoUrlAttribute(): string
    {
        if (empty($this->photo)) {
            return asset('images/default-avatar.svg');
        }

        // 1) Check in students folder (primary location for enrolled students)
        $studentsPath = 'students/' . $this->photo;
        if (Storage::disk('public')->exists($studentsPath)) {
            return asset('storage/' . $studentsPath);
        }

        // 2) If stored directly in public path (rare)
        if (file_exists(public_path($this->photo))) {
            return asset($this->photo);
        }

        // 3) If stored in storage/app/public (accessible via /storage/... when storage:link exists)
        if (file_exists(storage_path('app/public/' . $this->photo))) {
            return asset('storage/' . ltrim($this->photo, '/'));
        }

        // 4) If stored in storage/app (not public) but present, try to serve via storage URL (may require storage:link)
        if (file_exists(storage_path('app/' . $this->photo))) {
            return asset('storage/' . ltrim($this->photo, '/'));
        }

        // 5) As a last resort, if the default filesystem can generate a URL
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

