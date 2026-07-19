<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    // Explicit table name because migration created a singular table `attendance`
    protected $table = 'attendance';
    protected $fillable = [
        'student_id', 'school_id', 'class_id', 'section_id', 'date', 'status', 'remarks', 'recorded_by', 'entry_time', 'exit_time', 'medium'
    ];

    protected $casts = [
        'date' => 'date',
        'status' => 'string'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
