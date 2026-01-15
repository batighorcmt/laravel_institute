<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTemplate extends Model
{
    protected $fillable = [
        'school_id','title','content','type'
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }

    public function scopeForSchool($q,$schoolId){ return $q->where('school_id',$schoolId); }
}
