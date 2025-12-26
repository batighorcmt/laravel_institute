<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'fee_category_id', 'invoice_id', 'amount_paid', 'discount_applied', 'fine_applied', 'payment_method', 'collected_by_user_id', 'role', 'status', 'received_at', 'receipt_id', 'external_txn_id', 'idempotency_key',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }
}
