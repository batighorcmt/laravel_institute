<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_user_id', 'date', 'total_amount', 'note',
    ];
}
