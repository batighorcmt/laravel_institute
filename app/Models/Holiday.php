<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id','title','date','description','status'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function scopeActive($query){ return $query->where('status','active'); }
    public function scopeForSchool($query, $schoolId) { return $query->where('school_id', $schoolId); }
    public function school(){ return $this->belongsTo(School::class); }
}
