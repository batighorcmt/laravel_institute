<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NoticeTarget extends Model
{
    protected $fillable = ['notice_id', 'targetable_id', 'targetable_type'];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    /**
     * Get the targeted model (Teacher, Student, SchoolClass, or Section).
     */
    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }
}
