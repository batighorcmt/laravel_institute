<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id','day_number','day_name','status'
    ];

    public function scopeActive($query){ return $query->where('status','active'); }
    public function scopeForSchool($query, $schoolId) { return $query->where('school_id', $schoolId); }
    public function school(){ return $this->belongsTo(School::class); }
}
