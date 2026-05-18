<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterschoolSubEvent extends Model
{
    protected $guarded = ['id'];

    public function event()
    {
        return $this->belongsTo(InterschoolEvent::class, 'interschool_event_id');
    }
}
