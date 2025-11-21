<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionExamSeatRoom extends Model
{
    protected $fillable = [
        'seat_plan_id','room_no','title','columns_count','col1_benches','col2_benches','col3_benches'
    ];

    public function seatPlan(): BelongsTo { return $this->belongsTo(AdmissionExamSeatPlan::class,'seat_plan_id'); }
    public function allocations(): HasMany { return $this->hasMany(AdmissionExamSeatAllocation::class,'room_id'); }
}