<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'fee_category_id', 'type', 'value', 'scope', 'start_month', 'end_month', 'approved_by', 'reason',
    ];
}
