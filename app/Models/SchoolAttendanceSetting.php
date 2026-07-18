<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolAttendanceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_entry_start',
        'student_entry_end',
        'student_late_threshold',
        'student_exit_start',
        'student_exit_end',
        'teacher_check_in_start',
        'teacher_check_in_end',
        'teacher_late_threshold',
        'teacher_check_out_start',
        'teacher_check_out_end',
        'require_photo',
        'require_location',
    ];

    protected $casts = [
        'require_photo' => 'boolean',
        'require_location' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
