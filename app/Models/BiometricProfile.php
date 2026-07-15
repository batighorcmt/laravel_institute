<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'user_type', 'student_id', 'teacher_id',
        'biometric_id', 'card_number', 'finger_count', 'status'
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(FingerprintTemplate::class, 'biometric_profile_id');
    }

    public function faceTemplates(): HasMany
    {
        return $this->hasMany(FaceTemplate::class, 'biometric_profile_id');
    }
}
