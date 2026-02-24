<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamRoomInvigilation extends Model
{
    protected $fillable = [
        'school_id',
        'duty_date',
        'seat_plan_id',
        'seat_plan_room_id',
        'teacher_id',
        'assigned_by'
    ];

    protected $casts = [
        'duty_date' => 'date',
    ];

    public function seatPlan()
    {
        return $this->belongsTo(SeatPlan::class);
    }

    public function room()
    {
        return $this->belongsTo(SeatPlanRoom::class, 'seat_plan_room_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
