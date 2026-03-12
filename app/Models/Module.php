<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'status'];

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_modules')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }
}
