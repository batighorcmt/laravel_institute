<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SeatPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'shift',
        'status',
    ];

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function seatPlanClasses(): HasMany
    {
        return $this->hasMany(SeatPlanClass::class);
    }

    public function classes(): HasManyThrough
    {
        return $this->hasManyThrough(
            SchoolClass::class,
            SeatPlanClass::class,
            'seat_plan_id',
            'id',
            'id',
            'class_id'
        );
    }

    public function seatPlanExams(): HasMany
    {
        return $this->hasMany(SeatPlanExam::class);
    }

    public function exams(): HasManyThrough
    {
        return $this->hasManyThrough(
            Exam::class,
            SeatPlanExam::class,
            'seat_plan_id',
            'id',
            'id',
            'exam_id'
        );
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(SeatPlanRoom::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SeatPlanAllocation::class);
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
