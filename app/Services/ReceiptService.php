<?php

namespace App\Services;

use App\Models\Receipt;

class ReceiptService
{
    public function issue(int $studentId, float $totalAmount, ?int $issuedByUserId = null): Receipt
    {
        $number = $this->nextReceiptNumber();
        return Receipt::create([
            'receipt_number' => $number,
            'student_id' => $studentId,
            'total_amount' => $totalAmount,
            'printed_at' => now(),
            'issued_by_user_id' => $issuedByUserId,
        ]);
    }

    protected function nextReceiptNumber(): string
    {
        $year = date('Y');
        $prefix = 'R-'.$year.'-';
        $last = Receipt::where('receipt_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();
        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->receipt_number);
            $seq = isset($parts[2]) ? ((int)$parts[2] + 1) : ($last->id + 1);
        }
        return $prefix.str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
    }
}
