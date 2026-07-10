<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FingerprintTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometric_profile_id', 'finger_name', 'template_data', 
        'algorithm', 'device_source', 'encrypted'
    ];

    protected $casts = [
        'encrypted' => 'boolean',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(BiometricProfile::class, 'biometric_profile_id');
    }
}
