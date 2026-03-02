<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'school_id',
        'type',
        'name',
        'content',
        'is_active',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
