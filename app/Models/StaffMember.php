<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StaffMember extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'user_id',
        'plain_password',
        'first_name',
        'last_name',
        'first_name_bn',
        'last_name_bn',
        'designation_id',
        'phone',
        'email',
        'address',
        'date_of_birth',
        'joining_date',
        'photo',
        'serial_number',
        'status',
        'show_on_website',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'status' => 'string',
        'show_on_website' => 'boolean',
    ];

    public function designationRef(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staffAttendances()
    {
        return $this->hasMany(StaffAttendance::class, 'user_id', 'user_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFullNameBnAttribute(): string
    {
        return trim(($this->first_name_bn ?? '').' '.($this->last_name_bn ?? ''));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo)) {
            return null;
        }

        return Storage::disk('public')->exists($this->photo)
            ? asset('storage/'.$this->photo)
            : null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
