<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRecord extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'type', 'memo_no', 'issued_at', 'data', 'code'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'data' => 'array',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
