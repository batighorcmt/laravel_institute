<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'username', 'first_name', 'last_name', 'first_name_bn', 'last_name_bn', 'email', 'password', 'plain_password',
        'phone', 'address', 'date_of_birth', 'joining_date', 'gender', 'photo', 'signature', 'qualification', 'academic_info', 'status',
        'father_name_bn','father_name_en','mother_name_bn','mother_name_en'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'status' => 'string',
        ];
    }

    // Relationships
    public function schoolRoles(): HasMany
    {
        return $this->hasMany(UserSchoolRole::class);
    }

    public function activeSchoolRoles(): HasMany
    {
        return $this->hasMany(UserSchoolRole::class)->where('status', 'active');
    }

    // Helper methods
    public function hasRole($roleName, $schoolId = null): bool
    {
        $query = $this->activeSchoolRoles()->whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    public function isPrincipal($schoolId = null): bool
    {
        return $this->hasRole(Role::PRINCIPAL, $schoolId);
    }

    public function isTeacher($schoolId = null): bool
    {
        return $this->hasRole(Role::TEACHER, $schoolId);
    }

    public function isParent($schoolId = null): bool
    {
        return $this->hasRole(Role::PARENT, $schoolId);
    }

    public function getSchoolsForRole($roleName)
    {
        return $this->activeSchoolRoles()
            ->whereHas('role', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            })
            ->with('school')
            ->get()
            ->pluck('school');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: $this->name;
    }

    /**
     * Return the first active school (principal context) or null.
     */
    public function primarySchool(): ?School
    {
        $pivot = $this->activeSchoolRoles()->with('school')->first();
        return $pivot?->school;
    }

    /**
     * Teacher attendance records
     */
    public function teacherAttendances()
    {
        return $this->hasMany(TeacherAttendance::class);
    }

    /**
     * Teacher profile (if user is a teacher)
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }
}
