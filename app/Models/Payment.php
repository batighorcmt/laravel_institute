<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'academic_year_id',
        'payment_number',
        'fee_category_id',
        'invoice_id',
        'amount_paid',
        'discount_applied',
        'fine_applied',
        'payment_method',
        'collected_by_user_id',
        'role',
        'status',
        'received_at',
        'receipt_id',
        'external_txn_id',
        'idempotency_key',
        'tran_id',
        'meta',
        'gateway_response',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function paymentItems()
    {
        return $this->hasMany(PaymentItem::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    protected $casts = [
        'received_at' => 'datetime',
        'meta' => 'array',
        'gateway_response' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by_user_id');
    }
}
