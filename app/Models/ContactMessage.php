<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }
}
