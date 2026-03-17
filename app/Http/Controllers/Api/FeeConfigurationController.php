<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use Illuminate\Http\Request;

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
            'frequencies' => ['monthly', 'one_time', 'termly', 'annual']
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
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:monthly,one_time,termly,annual',
            'is_common' => 'boolean'
        ]);

        // Check uniqueness within the school
        if (FeeCategory::where('school_id', $schoolId)->where('name', $validated['name'])->exists()) {
            return response()->json(['message' => 'এই নামে ক্যাটাগরি ইতিমধ্যে বিদ্যমান'], 422);
        }

        $validated['school_id'] = $schoolId;
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

                if (!$exists) {
                    StudentFee::create([
                        'school_id' => $schoolId,
                        'student_id' => $enrollment->student_id,
                        'fee_structure_id' => $struct->id,
                        'month' => $month,
                        'amount' => $struct->amount,
                        'paid_amount' => 0,
                        'status' => 'unpaid',
                        'due_date' => $struct->due_date ?: now()->addDays(15),
                    ]);
                    $generatedCount++;
                }
            }
        }

        return response()->json([
            'message' => 'ফি জেনারেট সফল হয়েছে।',
            'count' => $generatedCount
        ]);
    }
}
