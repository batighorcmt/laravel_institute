<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolAttendanceSetting;

class StaffAttendanceSettingController extends Controller
{
    public function show(Request $request)
    {
        $schoolId = $request->attributes->get('current_school_id');
        if (! $schoolId) {
            $user = $request->user();
            $schoolId = $user->getSchoolsForRole('staff')->first()?->id;
        }
        $settings = SchoolAttendanceSetting::where('school_id', $schoolId)->first();
        if (! $settings) {
            return response()->json([
                'message' => 'Settings not found',
                'data' => null,
            ], 404);
        }

        // Same underlying settings row StaffAttendanceController@store reads
        // for its require_photo/require_location validation, reshaped to
        // the check_in_start/check_in_end/... keys the mobile self
        // attendance screen expects (matches teacher/attendance/settings'
        // response shape).
        return response()->json([
            'data' => [
                'check_in_start' => $settings->teacher_check_in_start,
                'check_in_end' => $settings->teacher_check_in_end,
                'late_threshold' => $settings->teacher_late_threshold,
                'check_out_start' => $settings->teacher_check_out_start,
                'check_out_end' => $settings->teacher_check_out_end,
                'require_photo' => $settings->require_photo,
                'require_location' => $settings->require_location,
            ],
        ]);
    }
}
