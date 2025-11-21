<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionExamMark extends Model
{
    protected $fillable = [
        'exam_id','subject_id','application_id','obtained_mark'
    ];

    public function exam(): BelongsTo { return $this->belongsTo(AdmissionExam::class,'exam_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(AdmissionExamSubject::class,'subject_id'); }
    public function application(): BelongsTo { return $this->belongsTo(AdmissionApplication::class,'application_id'); }
}