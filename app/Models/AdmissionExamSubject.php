<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionExamSubject extends Model
{
    protected $fillable = [
        'exam_id','subject_name','full_mark','pass_mark','display_order'
    ];

    public function exam(): BelongsTo { return $this->belongsTo(AdmissionExam::class,'exam_id'); }
    public function marks(): HasMany { return $this->hasMany(AdmissionExamMark::class,'subject_id'); }
}