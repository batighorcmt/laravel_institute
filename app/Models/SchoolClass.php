<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SchoolClass extends Model
{
    protected $table = 'classes';
    
    protected $fillable = [
        'school_id', 'name', 'numeric_value', 'capacity', 'class_teacher_id', 'status'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'numeric_value' => 'integer',
        'status' => 'string',
    ];

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'class_teacher_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function subjectMappings(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_id', 'subject_id')
            ->withPivot(['group_id','is_optional','order_no','status'])
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

    public function scopeOrdered($query)
    {
        return $query->orderBy('numeric_value')->orderBy('name');
    }

    // Accessor
    public function getFullNameAttribute(): string
    {
        return $this->name . ' (' . $this->numeric_value . ')';
    }

    public function usesGroups(): bool
    {
        return (int) $this->numeric_value >= 9;
    }
}
