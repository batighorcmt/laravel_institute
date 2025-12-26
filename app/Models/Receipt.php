<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number', 'student_id', 'total_amount', 'printed_at', 'issued_by_user_id',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];
}
