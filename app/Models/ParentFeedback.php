<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentFeedback extends Model
{
    protected $table = 'parent_feedbacks';

    protected $fillable = [
        'school_id',
        'user_id',
        'student_id',
        'subject',
        'message',
        'status',
        'reply',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
