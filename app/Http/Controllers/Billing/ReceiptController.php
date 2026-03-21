<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    /**
     * API Response
     */
    public function show($id)
    {
        $payment = Payment::with(['student', 'paymentItems.studentFee.feeStructure.category'])->findOrFail($id);
        return response()->json(['receipt' => $payment]);
    }

    /**
     * Web View Rendering
     */
    public function showWeb(Request $request, $id)
    {
        $payment = Payment::with([
            'student.currentEnrollment.class', 
            'student.currentEnrollment.section',
            'paymentItems.studentFee.feeStructure.category',
            'school'
        ])->findOrFail($id);

        return view('billing.receipt', compact('payment'));
    }

    /**
     * PDF Download
     */
    public function downloadPdf($id)
    {
        $payment = Payment::with([
            'student.currentEnrollment.class', 
            'student.currentEnrollment.section',
            'paymentItems.studentFee.feeStructure.category',
            'school'
        ])->findOrFail($id);

        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '120');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.receipt_pdf', compact('payment'));
        return $pdf->download("Receipt-{$payment->payment_number}.pdf");
    }
}
