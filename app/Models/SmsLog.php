<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'school_id','sent_by_user_id','recipient_type','recipient_category','recipient_id','recipient_name','recipient_role',
        'roll_number','class_name','section_name','recipient_number','message','status','response','message_type'
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class,'sent_by_user_id'); }
    public function scopeForSchool($q,$schoolId){ return $q->where('school_id',$schoolId); }
}
