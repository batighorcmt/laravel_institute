<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected $fillable = [
        'school_id',
        'type',
        'category',
        'amount',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
