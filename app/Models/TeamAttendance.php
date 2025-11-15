<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamAttendance extends Model
{
    protected $table = 'team_attendance';

    protected $fillable = [
        'school_id','team_id','student_id','class_id','section_id','date','status','remarks','recorded_by'
    ];

    protected $casts = [
        'date' => 'date',
        'status' => 'string'
    ];

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class,'class_id'); }
    public function section(): BelongsTo { return $this->belongsTo(Section::class,'section_id'); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class,'recorded_by'); }
}
