<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterschoolEvent extends Model
{
    protected $guarded = ['id'];

    public function subEvents()
    {
        return $this->hasMany(InterschoolSubEvent::class);
    }
}
