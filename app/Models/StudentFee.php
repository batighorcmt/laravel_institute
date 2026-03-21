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

        $dueDate = \Carbon\Carbon::parse($this->due_date);
        
        // Handle logical deadline based on late_fee_day setting if it exists
        if ($category->late_fee_day) {
            try {
                // Use the 'month' field for monthly fees, or the creation month for others
                $refMonth = $this->month ?: \Carbon\Carbon::parse($this->created_at)->format('Y-m');
                $logicalDeadline = \Carbon\Carbon::parse($refMonth . '-' . $category->late_fee_day);
                
                // If today is past the logical deadline (e.g., the 10th of the month), 
                // use that as the threshold for fine calculation.
                if ($logicalDeadline->isPast()) {
                    $dueDate = $logicalDeadline;
                }
            } catch (\Exception $e) {
                // Fallback to record due_date if month parsing fails
            }
        }

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
