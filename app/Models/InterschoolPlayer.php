<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterschoolPlayer extends Model
{
    protected $guarded = ['id'];

    public function seasonEvent()
    {
        return $this->belongsTo(InterschoolSeasonEvent::class, 'interschool_season_event_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
