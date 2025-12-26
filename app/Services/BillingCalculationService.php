<?php

namespace App\Services;

use App\Models\FeeStructure;
use App\Models\Discount;
use App\Models\Payment;
use App\Models\FineSetting;
use Carbon\Carbon;

class BillingCalculationService
{
    /**
     * Calculate dues for a student for a given month (YYYY-MM).
     */
    public function calculateMonthlyDue(int $studentId, int $classId, string $month): array
    {
        // Select applicable fee structures by effective date window
        $structures = FeeStructure::with('category')->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)->orWhereNull('class_id');
            })
            ->where('active', true)
            ->get();

        $lines = [];
        $total = 0.0;

        foreach ($structures as $fs) {
            // Skip if not effective for the month
            $effectiveFrom = $fs->effective_from->format('Y-m');
            $effectiveTo = $fs->effective_to?->format('Y-m');
            if ($month < $effectiveFrom) { continue; }
            if ($effectiveTo && $month > $effectiveTo) { continue; }

            $amount = (float)$fs->amount;

            // Apply discount for this student and category
            $discount = Discount::where('student_id', $studentId)
                ->where(function ($q) use ($fs) {
                    $q->whereNull('fee_category_id')->orWhere('fee_category_id', $fs->fee_category_id);
                })
                ->where(function ($q) use ($month) {
                    $q->whereNull('start_month')->orWhere('start_month', '<=', $month);
                })
                ->where(function ($q) use ($month) {
                    $q->whereNull('end_month')->orWhere('end_month', '>=', $month);
                })
                ->where(function ($q) {
                    $q->whereNull('scope')->orWhere('scope', 'fee');
                })
                ->first();

            $discountAmount = 0.0;
            if ($discount) {
                if ($discount->type === 'percent') {
                    $discountAmount = round($amount * ((float)$discount->value) / 100, 2);
                } else {
                    $discountAmount = (float)$discount->value;
                }
            }

            $net = max(0.0, $amount - $discountAmount);

            // Compute fine if past due date for the specified month
            $fineSetting = FineSetting::where('active', true)->orderByDesc('id')->first();
            $fine = 0.0;
            if ($fineSetting) {
                $now = Carbon::now();
                $dueDate = null;
                if ($fs->category && $fs->category->frequency === 'monthly') {
                    $day = $fs->due_day_of_month ?: 10; // default day-of-month 10 if not set
                    $dt = Carbon::createFromFormat('Y-m-d', $month . '-01');
                    $day = min($day, $dt->daysInMonth);
                    $dueDate = Carbon::createFromFormat('Y-m-d', $month . '-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT));
                } else {
                    if ($fs->due_date) {
                        $dueDate = Carbon::parse($fs->due_date);
                    }
                }
                if ($dueDate && $now->greaterThan($dueDate)) {
                    if ($fineSetting->fine_type === 'percent') {
                        $fine = round($net * ((float)$fineSetting->fine_value) / 100, 2);
                    } else {
                        $fine = (float)$fineSetting->fine_value;
                    }
                    // Apply discount on fine if any
                    $fineDiscount = Discount::where('student_id', $studentId)
                        ->where(function ($q) use ($fs) {
                            $q->whereNull('fee_category_id')->orWhere('fee_category_id', $fs->fee_category_id);
                        })
                        ->where(function ($q) use ($month) {
                            $q->whereNull('start_month')->orWhere('start_month', '<=', $month);
                        })
                        ->where(function ($q) use ($month) {
                            $q->whereNull('end_month')->orWhere('end_month', '>=', $month);
                        })
                        ->where('scope', 'fine')
                        ->first();
                    if ($fineDiscount) {
                        if ($fineDiscount->type === 'percent') {
                            $fine -= round($fine * ((float)$fineDiscount->value) / 100, 2);
                        } else {
                            $fine -= (float)$fineDiscount->value;
                        }
                        $fine = max(0.0, $fine);
                    }
                }
            }

            $total += $net;
            $lines[] = [
                'fee_category_id' => $fs->fee_category_id,
                'gross' => $amount,
                'discount' => $discountAmount,
                'net' => $net,
                'fine' => round($fine, 2),
            ];
        }

        // Prior arrears (sum of unsettled payments negative balance etc.) — simplified placeholder
        $paidThisMonth = Payment::where('student_id', $studentId)
            ->where('status', 'settled')
            ->whereDate('created_at', '>=', $month.'-01')
            ->whereDate('created_at', '<=', $month.'-31')
            ->sum('amount_paid');

        return [
            'month' => $month,
            'lines' => $lines,
            'total_due' => round($total, 2),
            'paid_this_month' => (float)$paidThisMonth,
        ];
    }
}
