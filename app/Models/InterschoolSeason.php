<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterschoolSeason extends Model
{
    protected $guarded = ['id'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function seasonEvents()
    {
        return $this->hasMany(InterschoolSeasonEvent::class);
    }
}
