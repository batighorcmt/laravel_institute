<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FeeWaiver;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FeeWaiverController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id ?? $user->activeSchoolRoles()?->first()?->school_id;

        $query = FeeWaiver::where('school_id', $schoolId)->orderBy('created_at', 'desc');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $page = $query->with(['student' => function($q){
            $q->select('id','student_name_bn','student_name_en')
              ->with(['currentEnrollment' => function($en){
                  $en->select('id','student_id','class_id','section_id','roll_no')
                     ->with(['class:id,name','section:id,name']);
              }]);
        }])->paginate(25);

        // map student name and enrollment into each item for frontend convenience
        $page->getCollection()->transform(function($item){
            $student = $item->student;
            $studentName = $student?->student_name_bn ?? $student?->student_name_en ?? null;
            $item->student_name = $studentName;
            $en = $student?->currentEnrollment;
            $item->class_name = $en?->class?->name ?? null;
            $item->section_name = $en?->section?->name ?? null;
            $item->roll_no = $en?->roll_no ?? null;
            return $item;
        });

        return response()->json($page);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id ?? $user->activeSchoolRoles()?->first()?->school_id;

        $waiver = FeeWaiver::where('school_id', $schoolId)->findOrFail($id);
        return response()->json($waiver);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_category_id' => 'nullable|exists:fee_categories,id',
            'fee_structure_id' => 'nullable|exists:fee_structures,id',
            'apply_to_all' => 'boolean',
            'waiver_type' => 'required|in:full,amount,percent',
            'waiver_value' => 'nullable|numeric|min:0',
            'is_recurring' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'apply_to_existing' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id ?? $user->activeSchoolRoles()?->first()?->school_id;

        $data['school_id'] = $schoolId;
        $data['created_by'] = optional($user)->id;

        // capture whether caller wants retroactive application
        $applyToExisting = isset($data['apply_to_existing']) ? (bool)$data['apply_to_existing'] : false;
        unset($data['apply_to_existing']);

        $waiver = FeeWaiver::create($data);

        if ($applyToExisting) {
            try {
                $this->applyWaiverToExistingFees($waiver);
            } catch (\Throwable $e) {
                \Log::warning('Failed to apply waiver to existing fees: '.$e->getMessage());
            }
        }

        return response()->json(['message' => 'Waiver created', 'waiver' => $waiver], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id ?? $user->activeSchoolRoles()?->first()?->school_id;

        $waiver = FeeWaiver::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'fee_category_id' => 'nullable|exists:fee_categories,id',
            'fee_structure_id' => 'nullable|exists:fee_structures,id',
            'apply_to_all' => 'boolean',
            'waiver_type' => 'nullable|in:full,amount,percent',
            'waiver_value' => 'nullable|numeric|min:0',
            'is_recurring' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'apply_to_existing' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        $applyToExisting = isset($data['apply_to_existing']) ? (bool)$data['apply_to_existing'] : false;
        unset($data['apply_to_existing']);

        // revert any prior application of this waiver on existing fees
        try { $this->revertWaiverFromExistingFees($waiver); } catch (\Throwable $e) { \Log::warning('Revert before update failed: '.$e->getMessage()); }

        $waiver->update($data);

        // optionally re-apply retroactively only when requested
        try {
            if ($applyToExisting) {
                $this->applyWaiverToExistingFees($waiver);
            }
        } catch (\Throwable $e) {
            \Log::warning('Re-apply after update failed: '.$e->getMessage());
        }

        return response()->json(['message' => 'Waiver updated', 'waiver' => $waiver]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id ?? $user->activeSchoolRoles()?->first()?->school_id;

        $waiver = FeeWaiver::where('school_id', $schoolId)->findOrFail($id);
        // revert any applied effects before deletion
        try { $this->revertWaiverFromExistingFees($waiver); } catch (\Throwable $e) { \Log::warning('Revert on delete failed: '.$e->getMessage()); }
        $waiver->delete();

        return response()->json(['message' => 'Waiver deleted']);
    }

    /**
     * Apply a waiver to matching existing student_fees for the student
     */
    protected function applyWaiverToExistingFees(FeeWaiver $waiver)
    {
        // Find candidate student_fees for this student in the school
        $query = \App\Models\StudentFee::where('school_id', $waiver->school_id)
            ->where('student_id', $waiver->student_id)
            ->where(function($q) use ($waiver) {
                if ($waiver->fee_structure_id) {
                    $q->where('fee_structure_id', $waiver->fee_structure_id);
                } elseif ($waiver->fee_category_id) {
                    // join to fee_structures to match category
                    $q->whereHas('feeStructure', function($fs) use ($waiver) {
                        $fs->where('fee_category_id', $waiver->fee_category_id);
                    });
                }
            });

        $fees = $query->get();
        foreach ($fees as $sf) {
            // preserve original_amount if not set
            if (is_null($sf->original_amount)) {
                $sf->original_amount = $sf->amount;
            }

            // compute month window if month present
            $monthStart = null; $monthEnd = null;
            if ($sf->month) {
                try {
                    $monthStart = Carbon::parse($sf->month.'-01')->startOfMonth();
                    $monthEnd = Carbon::parse($sf->month.'-01')->endOfMonth();
                } catch (\Throwable $e) { }
            }

            // determine fee structure and category id
            $feeStructureId = $sf->fee_structure_id;
            $feeCategoryId = $sf->feeStructure?->fee_category_id ?? null;

            if (! $waiver->appliesTo($feeStructureId, $feeCategoryId, $monthStart, $monthEnd)) {
                continue;
            }

            $original = $sf->original_amount ?? $sf->amount;
            $final = $original;
            if ($waiver->waiver_type === 'full') {
                $final = 0;
            } elseif ($waiver->waiver_type === 'amount' && $waiver->waiver_value) {
                $final = max(0, $original - (float)$waiver->waiver_value);
            } elseif ($waiver->waiver_type === 'percent' && $waiver->waiver_value) {
                $final = max(0, round($original * (1 - ((float)$waiver->waiver_value / 100)), 2));
            }

            $sf->amount = $final;
            $sf->waiver_id = $waiver->id;
            // Re-derive status from the waived amount vs. what's already been
            // paid — a partial waiver can drop the due below the paid amount
            // without the due itself reaching zero, which previously left
            // status stuck at its pre-waiver value (e.g. still 'partial').
            $paidAmount = (float) ($sf->paid_amount ?? 0);
            if ($final <= 0 || $paidAmount >= $final) {
                $sf->status = 'paid';
            } elseif ($paidAmount > 0) {
                $sf->status = 'partial';
            } else {
                $sf->status = 'unpaid';
            }
            $sf->save();
        }
    }

    /**
     * Revert waiver effects previously applied to student_fees by this waiver
     */
    protected function revertWaiverFromExistingFees(FeeWaiver $waiver)
    {
        $fees = \App\Models\StudentFee::where('school_id', $waiver->school_id)
            ->where('student_id', $waiver->student_id)
            ->where('waiver_id', $waiver->id)
            ->get();

        foreach ($fees as $sf) {
            // restore original amount if available
            if (! is_null($sf->original_amount)) {
                $sf->amount = $sf->original_amount;
            }
            $sf->waiver_id = null;
            // if amount > 0 and paid_amount < amount, mark unpaid
            if (($sf->amount ?? 0) > 0 && ($sf->paid_amount ?? 0) < ($sf->amount ?? 0)) {
                $sf->status = 'unpaid';
            }
            $sf->save();
        }
    }
}
