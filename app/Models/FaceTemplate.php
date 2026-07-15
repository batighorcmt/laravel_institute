<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometric_profile_id', 'template_data', 'algorithm', 'device_source'
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(BiometricProfile::class, 'biometric_profile_id');
    }
}
