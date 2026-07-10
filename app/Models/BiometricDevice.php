<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'device_group_id', 'device_name', 'brand', 'model', 
        'serial_number', 'ip_address', 'port', 'location', 'agent_id', 
        'status', 'last_seen', 'last_sync_time'
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'last_sync_time' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function deviceGroup(): BelongsTo
    {
        return $this->belongsTo(BiometricDeviceGroup::class, 'device_group_id');
    }

    public function heartbeats(): HasMany
    {
        return $this->hasMany(DeviceHeartbeat::class, 'device_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(BiometricSyncLog::class, 'device_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(BiometricAttendanceLog::class, 'device_id');
    }
}
