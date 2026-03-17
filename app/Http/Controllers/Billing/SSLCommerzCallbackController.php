<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\StudentFee;
use App\Models\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptService;

class SSLCommerzCallbackController extends Controller
{
    /**
     * Handle successful payment redirect
     */
    public function success(Request $request)
    {
        $tranId = $request->get('tran_id');
        $payment = Payment::where('tran_id', $tranId)->first();

        if (!$payment) {
            $url = route('billing.collect');
            return "<html><body onload=\"window.location.href='$url'\">Redirecting...</body></html>";
        }

        if ($payment->status !== 'settled') {
            $this->settlePayment($payment, $request->all());
        }

        $url = route('billing.receipts.show', $payment->id);
        return "<html><body onload=\"window.location.href='$url'\">Payment Success! Redirecting to receipt...</body></html>";
    }

    /**
     * Handle failed payment redirect
     */
    public function fail(Request $request)
    {
        $tranId = $request->get('tran_id');
        $payment = Payment::where('tran_id', $tranId)->first();

        $url = route('billing.collect');
        if ($payment) {
            $url .= "?student_id=" . $payment->student_id;
        }

        return "<html><body onload=\"window.location.href='$url'\">Payment Failed. Redirecting back...</body></html>";
    }

    /**
     * Handle cancelled payment redirect
     */
    public function cancel(Request $request)
    {
        $tranId = $request->get('tran_id');
        $payment = Payment::where('tran_id', $tranId)->first();

        $url = route('billing.collect');
        if ($payment) {
            $url .= "?student_id=" . $payment->student_id;
        }

        return "<html><body onload=\"window.location.href='$url'\">Payment Cancelled. Redirecting back...</body></html>";
    }

    /**
     * Handle IPN (Instant Payment Notification)
     */
    public function ipn(Request $request)
    {
        $tranId = $request->get('tran_id');
        $payment = Payment::where('tran_id', $tranId)->first();

        if ($payment && $payment->status !== 'settled' && $request->get('status') === 'VALID') {
            $this->settlePayment($payment, $request->all());
        }

        return response('OK');
    }

    /**
     * Common logic to settle payment and update fee statuses
     */
    private function settlePayment(Payment $payment, array $gatewayData)
    {
        DB::transaction(function () use ($payment, $gatewayData) {
            // Update payment record
            $payment->update([
                'status' => 'settled',
                'gateway_response' => $gatewayData,
            ]);

            // Decode fee info from meta
            $fees = $payment->meta['fees'] ?? [];
            $remarks = $payment->meta['remarks'] ?? '';

            foreach ($fees as $feeData) {
                $studentFee = StudentFee::lockForUpdate()->find($feeData['student_fee_id']);
                if ($studentFee) {
                    PaymentItem::create([
                        'school_id' => $payment->school_id,
                        'payment_id' => $payment->id,
                        'student_fee_id' => $studentFee->id,
                        'amount' => $feeData['amount'],
                    ]);

                    $studentFee->paid_amount += $feeData['amount'];
                    $studentFee->status = ($studentFee->paid_amount >= $studentFee->amount) ? 'paid' : 'partial';
                    $studentFee->save();
                }
            }

            // Create Ledger Entry
            Ledger::create([
                'school_id' => $payment->school_id,
                'type' => 'income',
                'category' => 'Fee Collection (Online)',
                'amount' => $payment->amount_paid,
                'entry_date' => now()->toDateString(),
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'description' => "Online payment (SSLCommerz) for student {$payment->student->student_id}. " . $remarks,
            ]);

            // Issue receipt for settled online payment if not already issued
            if (! $payment->receipt_id) {
                try {
                    $receipt = (new ReceiptService())->issue($payment->student_id, (float) $payment->amount_paid, null);
                    $payment->receipt_id = $receipt->id;
                    $payment->save();
                } catch (\Throwable $e) {
                    Log::error('Receipt issuance failed for SSLCommerz payment '.$payment->id.': '.$e->getMessage());
                }
            }
        });
    }
}
