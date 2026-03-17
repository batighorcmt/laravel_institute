<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherDeposit extends Model
{
    protected $fillable = [
        'teacher_id',
        'cashier_id',
        'amount',
        'deposit_date',
        'status',
        'remarks',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
