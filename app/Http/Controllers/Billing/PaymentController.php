<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Student;
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
        ]);

        $user = $request->user();
        $student = Student::findOrFail($data['student_id']);
        $schoolId = $request->attributes->get('current_school_id') ?? $student->school_id;

        // This endpoint had no role restriction at all: any authenticated
        // user (parent, student, ...) could forge a "settled" payment for an
        // arbitrary student. Restrict to school staff who are actually
        // allowed to collect money, same as FeeCollectionController::collectFees.
        if ($student->school_id !== $schoolId) {
            abort(404);
        }
        if (! $user->isSuperAdmin() && ! $user->isPrincipal($schoolId) && ! $user->isCashier($schoolId) && ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }
        // The client-supplied 'role' field was never actually enforced against
        // the caller's real role — derive it server-side instead.
        $role = $user->isPrincipal($schoolId) ? 'headmaster' : ($user->isCashier($schoolId) ? 'cashier' : ($user->isTeacher($schoolId) ? 'teacher' : 'online'));

        $method = $data['payment_method'] ?? 'cash';
        $status = $method === 'cash' ? 'settled' : 'pending';

        $payment = DB::transaction(function () use ($data, $method, $status, $role, $schoolId, $user) {
            return Payment::create(array_merge($data, [
                'school_id' => $schoolId,
                'payment_method' => $method,
                'collected_by_user_id' => optional($user)->id,
                'role' => $role,
                'status' => $status,
                'received_at' => now(),
            ]));
        });

        // Auto-issue receipt for settled payments. Kept outside the payment's
        // own transaction: the payment itself is already durably recorded, so
        // a receipt failure here shouldn't roll back real money collected —
        // it's logged and can be regenerated for this payment later.
        if ($payment->status === 'settled' && ! $payment->receipt_id) {
            try {
                $receipt = (new ReceiptService())->issue($payment->student_id, (float)$payment->amount_paid, optional($user)->id);
                $payment->receipt_id = $receipt->id;
                $payment->save();
            } catch (\Throwable $e) {
                Log::error('Receipt issuance failed for payment '.$payment->id.': '.$e->getMessage());
            }
        }

        return response()->json(['payment' => $payment], 201);
    }
}
