<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLeave extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'class_id',
        'section_id',
        'type',
        'title',
        'reason',
        'start_date',
        'end_date',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeForSchool($q, $schoolId)
    {
        return $q->where('school_id', $schoolId);
    }

    // Every date in [start_date, end_date] inclusive, as Y-m-d strings.
    public function dateRange(): array
    {
        $dates = [];
        $cursor = $this->start_date->copy();
        while ($cursor->lte($this->end_date)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }
        return $dates;
    }
}
