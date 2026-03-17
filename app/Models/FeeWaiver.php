<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FeeWaiver extends Model
{
    protected $table = 'fee_waivers';

    protected $fillable = [
        'school_id', 'student_id', 'fee_category_id', 'fee_structure_id',
        'waiver_type', 'waiver_value', 'is_recurring', 'start_date', 'end_date', 'apply_to_all',
        'created_by', 'notes'
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'apply_to_all' => 'boolean',
    ];

    /**
     * Determine whether waiver applies for given structure/category and month range
     * $monthStart, $monthEnd are Carbon instances (optional)
     */
    public function appliesTo(int $feeStructureId, int $feeCategoryId, ?Carbon $monthStart = null, ?Carbon $monthEnd = null): bool
    {
        // If not explicitly scoped to a structure/category and not global, do not apply
        if (! $this->apply_to_all && !$this->fee_structure_id && !$this->fee_category_id) return false;

        // structure/category match
        if ($this->fee_structure_id && $this->fee_structure_id != $feeStructureId) return false;
        if ($this->fee_category_id && $this->fee_category_id != $feeCategoryId) return false;

        // date range match
        if (! $monthStart || ! $monthEnd) {
            // no month provided: check whether today is within waiver
            $today = Carbon::today();
            $start = $this->start_date ? Carbon::parse($this->start_date) : null;
            $end = $this->end_date ? Carbon::parse($this->end_date) : null;
            if ($start && $today->lt($start)) return false;
            if ($end && $today->gt($end)) return false;
            return true;
        }

        $start = $this->start_date ? Carbon::parse($this->start_date) : null;
        $end = $this->end_date ? Carbon::parse($this->end_date) : null;

        if ($this->is_recurring) {
            // recurring: active if start <= monthEnd and (end is null or end >= monthStart)
            if ($start && $start->gt($monthEnd)) return false;
            if ($end && $end->lt($monthStart)) return false;
            return true;
        }

        // non-recurring: apply only if month window overlaps start_date (or start within month)
        if ($start && $start->between($monthStart, $monthEnd)) return true;
        return false;
    }

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class, 'student_id');
    }
}
