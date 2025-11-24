<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatPlanAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'seat_plan_id',
        'room_id',
        'student_id',
        'col_no',
        'bench_no',
        'position',
    ];

    protected $casts = [
        'col_no' => 'integer',
        'bench_no' => 'integer',
    ];

    // Relationships
    public function seatPlan(): BelongsTo
    {
        return $this->belongsTo(SeatPlan::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(SeatPlanRoom::class, 'room_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Accessors
    public function getSeatNumberAttribute(): string
    {
        return "C{$this->col_no}-B{$this->bench_no}-{$this->position}";
    }

    // Scopes
    public function scopeForSeatPlan($query, $seatPlanId)
    {
        return $query->where('seat_plan_id', $seatPlanId);
    }

    public function scopeForRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
