<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionPayment extends Model
{
    protected $fillable = [
        'admission_application_id','amount','payment_method','tran_id','invoice_no','status','gateway_response','gateway_status','fee_type'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'string',
        'gateway_response' => 'array',
        'gateway_status' => 'string',
        'fee_type' => 'string',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class,'admission_application_id');
    }
}
