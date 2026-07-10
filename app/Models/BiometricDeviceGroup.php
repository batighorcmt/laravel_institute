<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDeviceGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'name', 'description'
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(BiometricDevice::class, 'device_group_id');
    }
}
