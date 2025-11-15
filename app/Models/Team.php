<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    protected $fillable = [
        'school_id','name','type','description','status'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class,'team_student')->withTimestamps()->withPivot(['joined_at','status']);
    }

    public function scopeActive($q){ return $q->where('status','active'); }
    public function scopeForSchool($q,$schoolId){ return $q->where('school_id',$schoolId); }
}
