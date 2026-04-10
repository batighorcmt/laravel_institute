<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = [
        'school_id', 'name', 'bangla_name', 'code', 'description', 'status',
        'has_creative','has_mcq','has_practical',
        // mark/pass fields intentionally retained in table but excluded from mass-assignment updates per simplified policy
    ];

    protected $casts = [
        'status' => 'string',
        'has_creative' => 'boolean',
        'has_mcq' => 'boolean',
        'has_practical' => 'boolean',
        // mark/pass casts omitted for now
    ];

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Class mappings (via class_subjects pivot table).
     * Used by PrincipalStudentController and SchoolMetaController for filtering subjects by class.
     */
    public function classMappings(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'subject_id');
    }

    /**
     * Classes that this subject is mapped to.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subjects', 'subject_id', 'class_id')
                    ->withPivot(['order_no', 'status', 'is_optional', 'offered_mode'])
                    ->withTimestamps();
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
