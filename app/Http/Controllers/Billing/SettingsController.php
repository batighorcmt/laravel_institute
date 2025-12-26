<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\FeeStructure;
use App\Models\FeeCategory;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function feeStructureIndex()
    {
        $structures = FeeStructure::with('category')->orderByDesc('effective_from')->paginate(20);
        $schools = \App\Models\School::orderBy('name')->get(['id','name']);
        $classes = \Illuminate\Support\Facades\DB::table('classes')->orderBy('name')->get(['id','name']);
        $categories = FeeCategory::orderBy('name')->get(['id','name']);
        return view('billing.settings.fee_structures', compact('structures','schools','classes','categories'));
    }

    public function feeStructureStore(Request $request)
    {
        $data = $request->validate([
            'school_id' => ['required','integer'],
            'class_id' => ['required','integer'],
            'category_id' => ['required','integer'],
            'amount' => ['required','numeric','min:0'],
            'effective_from' => ['required','date'],
            'effective_to' => ['nullable','date','after:effective_from'],
            'due_day_of_month' => ['nullable','integer','min:1','max:31'],
            'due_date' => ['nullable','date'],
        ]);

        $category = \App\Models\FeeCategory::findOrFail($data['category_id']);

        $payload = [
            'class_id' => $data['class_id'],
            'fee_category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
        ];
        // Assign due fields based on category frequency
        if ($category->frequency === 'monthly') {
            $payload['due_day_of_month'] = $data['due_day_of_month'] ?? null;
            $payload['due_date'] = null;
        } else {
            $payload['due_day_of_month'] = null;
            $payload['due_date'] = $data['due_date'] ?? null;
        }
        FeeStructure::create($payload);
        return back()->with('success', 'Fee structure saved');
    }

    public function discountsIndex()
    {
        $discounts = Discount::orderByDesc('created_at')->paginate(20);
        $schools = \App\Models\School::orderBy('name')->get(['id','name']);
        $students = \App\Models\Student::orderBy('name')->limit(100)->get(['id','name']);
        $classes = \Illuminate\Support\Facades\DB::table('classes')->orderBy('name')->get(['id','name']);
        return view('billing.settings.discounts', compact('discounts','schools','students','classes'));
    }

    public function discountsStore(Request $request)
    {
        $data = $request->validate([
            'school_id' => ['required','integer'],
            'student_id' => ['nullable','integer'],
            'class_id' => ['nullable','integer'],
            'type' => ['required','in:fixed,percent'],
            'value' => ['required','numeric','min:0'],
            'scope' => ['nullable','in:fee,fine'],
            'start_month' => ['required','date_format:Y-m'],
            'end_month' => ['nullable','date_format:Y-m'],
        ]);
        Discount::create([
            'student_id' => $data['student_id'] ?? null,
            'fee_category_id' => $request->input('fee_category_id'), // optional per-category discount
            'type' => $data['type'],
            'value' => $data['value'],
            'scope' => $data['scope'] ?? 'fee',
            'start_month' => $data['start_month'] ?? null,
            'end_month' => $data['end_month'] ?? null,
            'approved_by' => $request->user()?->id,
            'reason' => $request->input('reason'),
        ]);
        return back()->with('success', 'Discount saved');
    }

    // Fine Settings (global)
    public function fineIndex()
    {
        $setting = \App\Models\FineSetting::orderByDesc('id')->first();
        return view('billing.settings.fines', compact('setting'));
    }

    public function fineStore(Request $request)
    {
        $data = $request->validate([
            'fine_type' => ['required','in:fixed,percent'],
            'fine_value' => ['required','numeric','min:0'],
            'active' => ['nullable'],
        ]);
        \App\Models\FineSetting::create([
            'fine_type' => $data['fine_type'],
            'fine_value' => $data['fine_value'],
            'active' => $request->boolean('active'),
        ]);
        return back()->with('success', 'Fine setting saved');
    }

    // Fee Categories (type + global flag)
    public function categoriesIndex()
    {
        $categories = FeeCategory::orderBy('name')->paginate(20);
        return view('billing.settings.fee_categories', compact('categories'));
    }

    public function categoriesStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'type' => ['required','in:monthly,one_time'],
            // Checkboxes may be omitted when unchecked; validate as sometimes present
            'is_global' => ['nullable'],
            'active' => ['nullable'],
        ]);
        $payload = [
            'name' => $data['name'],
            'frequency' => $data['type'] === 'monthly' ? 'monthly' : 'one_time',
            // Use boolean() to correctly interpret checkbox values
            'is_common' => $request->boolean('is_global'),
            'active' => $request->boolean('active'),
        ];
        FeeCategory::create($payload);
        return back()->with('success', 'Fee category saved');
    }

    public function categoriesUpdate(Request $request, FeeCategory $category)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'type' => ['required','in:monthly,one_time'],
            'is_global' => ['nullable'],
            'active' => ['nullable'],
        ]);
        $payload = [
            'name' => $data['name'],
            'frequency' => $data['type'] === 'monthly' ? 'monthly' : 'one_time',
            'is_common' => $request->boolean('is_global'),
            'active' => $request->boolean('active'),
        ];
        $category->update($payload);
        return back()->with('success', 'Fee category updated');
    }

    public function categoriesDestroy(FeeCategory $category)
    {
        // Prevent deletion if referenced in fee_structures or payments
        $inUse = \App\Models\FeeStructure::where('fee_category_id', $category->id)->exists()
            || \Illuminate\Support\Facades\DB::table('payments')->where('fee_category_id', $category->id)->exists();
        if ($inUse) {
            return back()->with('error', 'Cannot delete: category in use');
        }
        $category->delete();
        return back()->with('success', 'Fee category deleted');
    }

    // Global Fees (FeeStructure with class_id = null)
    public function globalFeesIndex()
    {
        $globals = FeeStructure::whereNull('class_id')->orderByDesc('effective_from')->paginate(20);
        $categories = FeeCategory::where('is_common', true)->orderBy('name')->get(['id','name']);
        $schools = \App\Models\School::orderBy('name')->get(['id','name']);
        return view('billing.settings.global_fees', compact('globals','categories','schools'));
    }

    public function globalFeesStore(Request $request)
    {
        $data = $request->validate([
            'school_id' => ['required','integer'],
            'category_id' => ['required','integer'],
            'amount' => ['required','numeric','min:0'],
            'effective_from' => ['required','date'],
            'effective_to' => ['nullable','date','after:effective_from'],
        ]);
        $payload = [
            'class_id' => null,
            'fee_category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
        ];
        FeeStructure::create($payload);
        return back()->with('success', 'Global fee saved');
    }
}
