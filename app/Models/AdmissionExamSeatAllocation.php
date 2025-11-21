<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionExamSeatAllocation extends Model
{
    protected $fillable = [
        'seat_plan_id','room_id','application_id','col_no','bench_no','position'
    ];

    public function seatPlan(): BelongsTo { return $this->belongsTo(AdmissionExamSeatPlan::class,'seat_plan_id'); }
    public function room(): BelongsTo { return $this->belongsTo(AdmissionExamSeatRoom::class,'room_id'); }
    public function application(): BelongsTo { return $this->belongsTo(AdmissionApplication::class,'application_id'); }
}