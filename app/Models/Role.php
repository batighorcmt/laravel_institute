<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name', 'display_name', 'description', 'permissions'
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    // Constants for role names
    const SUPER_ADMIN = 'super_admin';
    const PRINCIPAL = 'principal';
    const TEACHER = 'teacher';
    const PARENT = 'parent';
    const APPLICANT = 'applicant';

    // Relationships
    public function userSchoolRoles(): HasMany
    {
        return $this->hasMany(UserSchoolRole::class);
    }

    // Helper methods
    public function hasPermission($permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public static function getSuperAdminRole()
    {
        return static::where('name', self::SUPER_ADMIN)->first();
    }
}
