<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FeeConfigurationController extends Controller
{
    /**
     * Get all fee categories and structures
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ??
                   $user->primarySchool()?->id ??
                   $user->activeSchoolRoles()->first()?->school_id;

        $school = \App\Models\School::find($schoolId);

        $categories = FeeCategory::where('school_id', $schoolId)
            ->with(['feeStructures' => function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            }])
            ->get();

        $classes = SchoolClass::where('school_id', $schoolId)
            ->active()
            ->get(['id', 'name']);

        $academicYears = \App\Models\AcademicYear::where('school_id', $schoolId)
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name', 'is_current']);

        return response()->json([
            'categories' => $categories,
            'classes' => $classes,
            'academic_years' => $academicYears,
            'frequencies' => ['monthly', 'one_time', 'termly', 'annual'],
            'school_fine_enabled' => $school ? (bool) $school->fine_enabled : true,
        ]);
    }

    /**
     * Toggle global fine system on/off for the school
     */
    public function toggleGlobalFine(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ??
                   $user->primarySchool()?->id ??
                   $user->activeSchoolRoles()->first()?->school_id;

        $school = \App\Models\School::findOrFail($schoolId);

        $newStatus = $request->input('fine_enabled');
        if (!is_bool($newStatus)) {
            $newStatus = filter_var($newStatus, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (is_null($newStatus)) {
            return response()->json(['message' => 'fine_enabled মান প্রয়োজন'], 422);
        }

        $school->fine_enabled = $newStatus;
        $school->save();

        return response()->json([
            'message' => 'জরিমানা সিস্টেম ' . ($newStatus ? 'চালু' : 'বন্ধ') . ' করা হয়েছে',
            'fine_enabled' => $school->fine_enabled,
        ]);
    }

    /**
     * Store new Fee Category
     */
    public function storeCategory(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ??
                   $user->primarySchool()?->id ??
                   $user->activeSchoolRoles()->first()?->school_id;

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'frequency'    => 'required|in:monthly,one_time,termly,annual',
            'is_common'    => 'boolean',
            'has_fine'     => 'boolean',
            'fine_type'    => 'nullable|in:fixed,percentage',
            'fine_amount'  => 'nullable|numeric|min:0',
            'late_fee_day' => 'nullable|integer|min:1|max:31',
        ]);

        // Check uniqueness within the school
        if (FeeCategory::where('school_id', $schoolId)->where('name', $validated['name'])->exists()) {
            return response()->json(['message' => 'এই নামে ক্যাটাগরি ইতিমধ্যে বিদ্যমান'], 422);
        }

        $validated['school_id'] = $schoolId;
        $validated['fine_amount'] = $validated['fine_amount'] ?? 0;
        $validated['fine_type'] = $validated['fine_type'] ?? 'fixed';

        $category = FeeCategory::create($validated);
        return response()->json(['message' => 'ক্যাটাগরি তৈরি হয়েছে', 'category' => $category]);
    }

    /**
     * Store or Update Fee Structure
     */
    public function saveStructure(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ??
                   $user->primarySchool()?->id ??
                   $user->activeSchoolRoles()->first()?->school_id;

        $validated = $request->validate([
            'fee_category_id' => 'required|exists:fee_categories,id',
            'class_id' => 'nullable|exists:classes,id',
            'amount' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'due_day_of_month' => 'nullable|integer|min:1|max:31',
            'id' => 'nullable|exists:fee_structures,id'
        ]);

        $validated['school_id'] = $schoolId;

        if (isset($validated['id'])) {
            $structure = FeeStructure::where('school_id', $schoolId)->findOrFail($validated['id']);
            $structure->update($validated);
        } else {
            $structure = FeeStructure::create($validated);
        }

        return response()->json(['message' => 'ফি স্ট্রাকচার সংরক্ষিত হয়েছে', 'structure' => $structure]);
    }

    /**
     * Update a fee category
     */
    public function updateCategory(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ??
                   $user->primarySchool()?->id ??
                   $user->activeSchoolRoles()->first()?->school_id;

        $category = FeeCategory::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'frequency'    => 'required|in:monthly,one_time,termly,annual',
            'is_common'    => 'boolean',
            'active'       => 'boolean',
            'has_fine'     => 'boolean',
            'fine_type'    => 'nullable|in:fixed,percentage',
            'fine_amount'  => 'nullable|numeric|min:0',
            'late_fee_day' => 'nullable|integer|min:1|max:31',
        ]);

        // Check uniqueness within the school (exclude current)
        if (FeeCategory::where('school_id', $schoolId)->where('name', $validated['name'])->where('id', '!=', $category->id)->exists()) {
            return response()->json(['message' => 'এই নামে অন্য একটি ক্যাটাগরি ইতিমধ্যে বিদ্যমান'], 422);
        }

        $validated['fine_amount'] = $validated['fine_amount'] ?? 0;
        $validated['fine_type'] = $validated['fine_type'] ?? 'fixed';

        $category->update($validated);

        return response()->json(['message' => 'ক্যাটাগরি আপডেট হয়েছে', 'category' => $category]);
    }

    /**
     * Delete or deactivate a category
     */
    public function deleteCategory(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ??
                   $user->primarySchool()?->id ??
                   $user->activeSchoolRoles()->first()?->school_id;

        $category = FeeCategory::where('school_id', $schoolId)->findOrFail($id);

        // Prefer soft-deactivate to avoid orphaning data
        $category->active = false;
        $category->save();

        return response()->json(['message' => 'ক্যাটাগরি নিষ্ক্রিয় করা হয়েছে']);
    }

    /**
     * Delete a structure
     */
    public function deleteStructure(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ??
                   $user->primarySchool()?->id ??
                   $user->activeSchoolRoles()->first()?->school_id;
        FeeStructure::where('school_id', $schoolId)->findOrFail($id)->delete();
        return response()->json(['message' => 'মুছে ফেলা হয়েছে']);
    }

    /**
     * Generate Dues for students based on structure
     */
    public function generateDues(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'month' => 'nullable|string', // format: YYYY-MM
            'class_id' => 'nullable|exists:classes,id',
            'fee_category_id' => 'nullable|exists:fee_categories,id'
        ]);

        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ??
                   $user->primarySchool()?->id ??
                   $user->activeSchoolRoles()->first()?->school_id;

        $enrollments = StudentEnrollment::where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('status', 'active')
            // ensure the related Student record is also active
            ->whereHas('student', function($q) {
                $q->where('status', 'active');
            })
            ->when(isset($validated['class_id']), fn($q) => $q->where('class_id', $validated['class_id']))
            ->get();

        $generatedCount = 0;

        foreach ($enrollments as $enrollment) {
            $structures = FeeStructure::with('category')
                ->where('school_id', $schoolId)
                ->where('active', true)
                ->where(function($query) use ($enrollment) {
                    $query->where('class_id', $enrollment->class_id)
                          ->orWhereNull('class_id');
                })
                ->when(isset($validated['fee_category_id']), fn($q) => $q->where('fee_category_id', $validated['fee_category_id']))
                ->get();

            foreach ($structures as $struct) {
                // Determine if we should generate for this frequency
                $month = null;
                if ($struct->category->frequency === 'monthly') {
                    if (!$validated['month']) continue; // Monthly requires month
                    $month = $validated['month'];
                }

                // Check if already generated
                $exists = StudentFee::where('school_id', $schoolId)
                    ->where('student_id', $enrollment->student_id)
                    ->where('fee_structure_id', $struct->id)
                    ->when($month, fn($q) => $q->where('month', $month))
                    ->exists();

                if ($exists) continue;

                // Check for applicable waiver(s)
                $monthStart = $month ? Carbon::parse($month . '-01')->startOfMonth() : null;
                $monthEnd = $month ? Carbon::parse($month . '-01')->endOfMonth() : null;

                $waivers = \App\Models\FeeWaiver::where('school_id', $schoolId)
                    ->where('student_id', $enrollment->student_id)
                    ->get();

                $applyingWaiver = null;
                foreach ($waivers as $w) {
                    if ($w->appliesTo($struct->id, $struct->fee_category_id, $monthStart, $monthEnd)) {
                        $applyingWaiver = $w;
                        break;
                    }
                }

                // If a full waiver applies, skip creating the fee
                if ($applyingWaiver && $applyingWaiver->waiver_type === 'full') {
                    continue;
                }

                // Compute amount taking partial waiver into account
                $finalAmount = $struct->amount;
                $waiverId = null;
                if ($applyingWaiver) {
                    $waiverId = $applyingWaiver->id;
                    if ($applyingWaiver->waiver_type === 'amount' && $applyingWaiver->waiver_value) {
                        $finalAmount = max(0, $struct->amount - (float)$applyingWaiver->waiver_value);
                    } elseif ($applyingWaiver->waiver_type === 'percent' && $applyingWaiver->waiver_value) {
                        $finalAmount = max(0, $struct->amount * (1 - ((float)$applyingWaiver->waiver_value / 100)));
                    }
                }

                StudentFee::create([
                    'school_id' => $schoolId,
                    'student_id' => $enrollment->student_id,
                    'fee_structure_id' => $struct->id,
                    'month' => $month,
                    'amount' => $finalAmount,
                    'original_amount' => $struct->amount,
                    'paid_amount' => 0,
                    'status' => $finalAmount <= 0 ? 'paid' : 'unpaid',
                    'due_date' => $struct->due_date ?: (
                        ($struct->category->frequency === 'monthly' && $month && $struct->category->late_fee_day)
                            ? \Carbon\Carbon::parse($month . '-' . $struct->category->late_fee_day)
                            : now()->addDays(15)
                    ),
                    'waiver_id' => $waiverId ?? null,
                ]);
                $generatedCount++;
            }
        }

        return response()->json([
            'message' => 'ফি জেনারেট সফল হয়েছে।',
            'count' => $generatedCount
        ]);
    }
}
