<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AdmissionClassSetting extends Model
{
    protected $fillable = [
        'school_id','academic_year_id','class_code','fee_amount','deadline','active'
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'deadline' => 'date',
        'active' => 'boolean',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class,'academic_year_id'); }

    public function scopeForSchoolYear(Builder $q, int $schoolId, ?int $yearId): Builder
    {
        return $q->where('school_id',$schoolId)
                 ->when($yearId, fn($qq)=>$qq->where('academic_year_id',$yearId));
    }

    public function scopeActive(Builder $q): Builder { return $q->where('active',true); }
    public function scopeNotExpired(Builder $q): Builder { return $q->where(function($qq){ $qq->whereNull('deadline')->orWhereDate('deadline','>=',today()); }); }
}
