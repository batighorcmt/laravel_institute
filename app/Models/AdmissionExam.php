<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionExam extends Model
{
    protected $fillable = [
        'school_id','name','type','overall_pass_mark','exam_date','status'
    ];

    protected $casts = [
        'exam_date'=>'date'
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function subjects(): HasMany { return $this->hasMany(AdmissionExamSubject::class,'exam_id'); }
    public function marks(): HasMany { return $this->hasMany(AdmissionExamMark::class,'exam_id'); }
    public function results(): HasMany { return $this->hasMany(AdmissionExamResult::class,'exam_id'); }
    public function seatPlans(): HasMany { return $this->hasMany(AdmissionExamSeatPlan::class,'exam_id'); }
}