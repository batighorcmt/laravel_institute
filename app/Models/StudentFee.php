<?php

namespace App\Models;

use App\Models\FeeCategory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StudentFee extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'fee_structure_id',
        'month',
        'amount',
        'original_amount',
        'waiver_id',
        'paid_amount',
        'fine_amount',
        'fine_waiver',
        'fine_waiver_reason',
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

    public function waiver()
    {
        return $this->belongsTo(\App\Models\FeeWaiver::class, 'waiver_id');
    }

    public function paymentItems()
    {
        return $this->hasMany(PaymentItem::class);
    }

    /**
     * Get the effective due date based on category configuration for monthly fees.
     */
    public function getEffectiveDueDate()
    {
        $category = FeeCategory::whereHas('feeStructures', function ($q) {
            $q->where('id', $this->fee_structure_id);
        })->first();

        if ($category && $category->late_fee_day && $this->month) {
            try {
                return \Carbon\Carbon::parse($this->month . '-' . $category->late_fee_day)->toDateString();
            } catch (\Exception $e) {
                return $this->due_date;
            }
        }
        return $this->due_date;
    }

    /**
     * Get the formatted fee name including the month if applicable.
     */
    public function getFormattedName()
    {
        $category = FeeCategory::whereHas('feeStructures', function ($q) {
            $q->where('id', $this->fee_structure_id);
        })->first();
        
        $name = $category->name ?? 'N/A';
        
        if ($this->month) {
            try {
                $date = \Carbon\Carbon::parse($this->month . '-01');
                $months = [
                    1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
                    5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
                    9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর'
                ];
                $bnMonth = $months[$date->month] ?? '';
                $bnYear = str_replace(range(0, 9), ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'], $date->year);
                $name .= " ($bnMonth, $bnYear)";
            } catch (\Exception $e) {
                // fall back to default name
            }
        }
        
        return $name;
    }

    /**
     * Calculate and return the fine for this fee record dynamically.
     */
    public function calculateFine()
    {
        $school = School::find($this->school_id);
        if (!$school || !$school->fine_enabled) return 0;

        $category = FeeCategory::whereHas('feeStructures', function ($q) {
            $q->where('id', $this->fee_structure_id);
        })->first();

        if (!$category || !$category->has_fine) return 0;

        $baseDue = max(0, $this->amount - $this->paid_amount);
        if ($baseDue <= 0) return 0;

        $dueDate = \Carbon\Carbon::parse($this->getEffectiveDueDate());

        if ($dueDate->isFuture()) return 0;

        $fine = 0;
        if ($category->fine_type === 'fixed') {
            $fine = floatval($category->fine_amount);
        } else {
            $fine = ($baseDue * floatval($category->fine_amount)) / 100;
        }

        return max(0, $fine - ($this->fine_waiver ?? 0));
    }
}
