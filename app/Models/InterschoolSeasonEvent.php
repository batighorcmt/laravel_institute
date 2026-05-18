<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterschoolSeasonEvent extends Model
{
    protected $guarded = ['id'];

    public function season()
    {
        return $this->belongsTo(InterschoolSeason::class, 'interschool_season_id');
    }

    public function event()
    {
        return $this->belongsTo(InterschoolEvent::class, 'interschool_event_id');
    }

    public function subEvent()
    {
        return $this->belongsTo(InterschoolSubEvent::class, 'interschool_sub_event_id');
    }

    public function players()
    {
        return $this->hasMany(InterschoolPlayer::class, 'interschool_season_event_id');
    }
}
