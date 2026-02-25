<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamRoomAttendance extends Model
{
    protected $fillable = [
        'school_id',
        'duty_date',
        'plan_id',
        'room_id',
        'student_id',
        'status'
    ];

    protected $casts = [
        'duty_date' => 'date',
    ];

    public function seatPlan()
    {
        return $this->belongsTo(SeatPlan::class, 'plan_id');
    }

    public function room()
    {
        return $this->belongsTo(SeatPlanRoom::class, 'room_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
