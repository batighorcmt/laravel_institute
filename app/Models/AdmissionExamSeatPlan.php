<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdmissionExamSeatPlan extends Model
{
    protected $fillable = [
        'exam_id','school_id','name','shift','status'
    ];

    public function exam(): BelongsTo { return $this->belongsTo(AdmissionExam::class,'exam_id'); }
    public function school(): BelongsTo { return $this->belongsTo(School::class,'school_id'); }
    public function rooms(): HasMany { return $this->hasMany(AdmissionExamSeatRoom::class,'seat_plan_id'); }
    public function allocations(): HasMany { return $this->hasMany(AdmissionExamSeatAllocation::class,'seat_plan_id'); }
    public function exams(): BelongsToMany { return $this->belongsToMany(AdmissionExam::class, 'admission_exam_seat_plan_exams', 'seat_plan_id', 'exam_id'); }
}