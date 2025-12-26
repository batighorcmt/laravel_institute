<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Services\ReceiptService;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|integer',
            'fee_category_id' => 'required|integer|exists:fee_categories,id',
            'amount_paid' => 'required|numeric|min:0',
            'discount_applied' => 'nullable|numeric|min:0',
            'fine_applied' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bkash,nagad,bank',
            'role' => 'nullable|in:teacher,cashier,headmaster,online',
        ]);

        $method = $data['payment_method'] ?? 'cash';
        $status = $method === 'cash' ? 'settled' : 'pending';

        $payment = Payment::create(array_merge($data, [
            'payment_method' => $method,
            'collected_by_user_id' => optional($request->user())->id,
            'status' => $status,
            'received_at' => now(),
        ]));

        // Auto-issue receipt for settled payments
        if ($payment->status === 'settled' && ! $payment->receipt_id) {
            $receipt = (new ReceiptService())->issue($payment->student_id, (float)$payment->amount_paid, optional($request->user())->id);
            $payment->receipt_id = $receipt->id;
            $payment->save();
        }

        return response()->json(['payment' => $payment], 201);
    }
}
