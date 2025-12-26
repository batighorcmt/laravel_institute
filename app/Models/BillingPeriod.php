<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'month', 'academic_year_id', 'status',
    ];

    protected $casts = [
        'month' => 'string',
    ];
}
