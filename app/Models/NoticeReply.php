<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeReply extends Model
{
    protected $fillable = ['notice_id', 'student_id', 'parent_id', 'voice_path', 'duration'];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}
