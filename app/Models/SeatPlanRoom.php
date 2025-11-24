<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatPlanRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'seat_plan_id',
        'room_no',
        'title',
        'columns_count',
        'col1_benches',
        'col2_benches',
        'col3_benches',
    ];

    protected $casts = [
        'columns_count' => 'integer',
        'col1_benches' => 'integer',
        'col2_benches' => 'integer',
        'col3_benches' => 'integer',
    ];

    // Relationships
    public function seatPlan(): BelongsTo
    {
        return $this->belongsTo(SeatPlan::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SeatPlanAllocation::class, 'room_id');
    }

    // Accessors
    public function getTotalCapacityAttribute(): int
    {
        return ($this->col1_benches + $this->col2_benches + $this->col3_benches) * 2; // 2 students per bench
    }

    public function getAllocatedCountAttribute(): int
    {
        return $this->allocations()->count();
    }

    public function getAvailableSeatsAttribute(): int
    {
        return $this->total_capacity - $this->allocated_count;
    }
}
