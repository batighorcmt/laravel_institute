<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolStatsSetting;
use App\Services\SchoolStatsResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolStatsController extends Controller
{
    public function data(School $school, SchoolStatsResolver $resolver)
    {
        $settings = SchoolStatsSetting::firstOrCreate(['school_id' => $school->id]);

        return response()->json([
            'settings' => $settings,
            'dynamicPreview' => $resolver->dynamicPreview($school),
        ]);
    }

    public function update(Request $request, School $school, SchoolStatsResolver $resolver)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in([SchoolStatsSetting::MODE_DYNAMIC, SchoolStatsSetting::MODE_STATIC])],
            'static_students_count' => ['nullable', 'integer', 'min:0'],
            'static_teachers_count' => ['nullable', 'integer', 'min:0'],
            'static_staff_count' => ['nullable', 'integer', 'min:0'],
            'static_classes_count' => ['nullable', 'integer', 'min:0'],
            'static_founding_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
        ]);

        $settings = SchoolStatsSetting::firstOrCreate(['school_id' => $school->id]);
        $settings->update($data);

        return response()->json([
            'message' => 'পরিসংখ্যান সেটিংস সংরক্ষণ হয়েছে।',
            'settings' => $settings->fresh(),
            'dynamicPreview' => $resolver->dynamicPreview($school),
        ]);
    }
}
