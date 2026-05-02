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

    public function getPartsCount(): int
    {
        $message = (string)$this->message;
        if ($message === '') return 0;

        // Check for non-ASCII (Unicode) characters. 
        // In many DB setups, if LENGTH != CHAR_LENGTH it's multi-byte (Unicode).
        // Here we use mb_strlen for characters.
        $isUnicode = mb_strlen($message, 'UTF-8') != strlen($message);
        $length = mb_strlen($message, 'UTF-8');

        if ($isUnicode) {
            if ($length <= 70) return 1;
            return (int) ceil($length / 67);
        } else {
            if ($length <= 160) return 1;
            return (int) ceil($length / 153);
        }
    }
}
