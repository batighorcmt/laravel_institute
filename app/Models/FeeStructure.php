<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'class_id', 'fee_category_id', 'amount', 'currency', 'effective_from', 'effective_to', 'due_day_of_month', 'due_date', 'active',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }
}
