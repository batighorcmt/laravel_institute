<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FineWaiver extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'applied_payment_id', 'amount', 'approved_by', 'reason',
    ];
}
