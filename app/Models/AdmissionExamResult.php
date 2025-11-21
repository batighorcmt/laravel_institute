<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionExamResult extends Model
{
    protected $fillable = [
        'exam_id','application_id','total_obtained','is_pass','failed_subjects_count'
    ];

    protected $casts = [
        'is_pass'=>'boolean'
    ];

    public function exam(): BelongsTo { return $this->belongsTo(AdmissionExam::class,'exam_id'); }
    public function application(): BelongsTo { return $this->belongsTo(AdmissionApplication::class,'application_id'); }
}