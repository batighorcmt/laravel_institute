<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subject extends Model
{
    protected $fillable = [
        'school_id', 'name', 'code', 'description', 'status',
        'has_creative','has_mcq','has_practical',
        // mark/pass fields intentionally retained in table but excluded from mass-assignment updates per simplified policy
    ];

    protected $casts = [
        'status' => 'string',
        'has_creative' => 'boolean',
        'has_mcq' => 'boolean',
        'has_practical' => 'boolean',
        // mark/pass casts omitted for now
    ];

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
