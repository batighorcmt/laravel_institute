<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TeacherAttendanceSetting;

class TeacherAttendanceSettingController extends Controller
{
    public function show(Request $request)
    {
        $schoolId = $request->attributes->get('current_school_id');
        if (! $schoolId) {
            $user = $request->user();
            $schoolId = $user->getSchoolsForRole('teacher')->first()?->id;
        }
        $settings = TeacherAttendanceSetting::where('school_id', $schoolId)->first();
        if (! $settings) {
            return response()->json([
                'message' => 'Settings not found',
                'data' => null,
            ], 404);
        }
        return response()->json([
            'data' => $settings->only([
                'check_in_start','check_in_end','late_threshold','check_out_start','check_out_end','require_photo','require_location'
            ]),
        ]);
    }
}
