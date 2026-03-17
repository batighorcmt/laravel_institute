<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentItem extends Model
{
    protected $fillable = [
        'school_id',
        'payment_id',
        'student_fee_id',
        'amount',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function studentFee()
    {
        return $this->belongsTo(StudentFee::class);
    }
}
