<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricAttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'device_id', 'biometric_id', 'punch_time', 
        'punch_type', 'sync_status'
    ];

    protected $casts = [
        'punch_time' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }
}
