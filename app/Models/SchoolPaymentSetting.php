<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolPaymentSetting extends Model
{
    protected $fillable = [
        'school_id','provider','store_id','store_password','sandbox','active','meta'
    ];

    protected $casts = [
        'sandbox' => 'boolean',
        'active' => 'boolean',
        'meta' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
