<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFee extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'fee_structure_id',
        'month',
        'amount',
        'paid_amount',
        'status',
        'due_date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function paymentItems()
    {
        return $this->hasMany(PaymentItem::class);
    }
}
