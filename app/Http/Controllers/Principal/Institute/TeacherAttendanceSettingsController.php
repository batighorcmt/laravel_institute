<?php

namespace App\Http\Controllers\Principal\Institute;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\TeacherAttendanceSetting;
use Illuminate\Http\Request;

class TeacherAttendanceSettingsController extends Controller
{
    public function index(School $school)
    {
        $settings = TeacherAttendanceSetting::where('school_id', $school->id)->first();
        
        return view('principal.institute.teacher-attendance.settings', compact('school', 'settings'));
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'check_in_start' => 'required|date_format:H:i',
            'check_in_end' => 'required|date_format:H:i',
            'late_threshold' => 'required|date_format:H:i',
            'check_out_start' => 'required|date_format:H:i',
            'check_out_end' => 'required|date_format:H:i',
            'require_photo' => 'boolean',
            'require_location' => 'boolean',
        ]);

        $settings = TeacherAttendanceSetting::updateOrCreate(
            ['school_id' => $school->id],
            [
                'check_in_start' => $validated['check_in_start'],
                'check_in_end' => $validated['check_in_end'],
                'late_threshold' => $validated['late_threshold'],
                'check_out_start' => $validated['check_out_start'],
                'check_out_end' => $validated['check_out_end'],
                'require_photo' => $request->has('require_photo'),
                'require_location' => $request->has('require_location'),
            ]
        );

        return redirect()
            ->route('principal.institute.teacher-attendance.settings.index', $school)
            ->with('success', 'Teacher Attendance Settings updated successfully!');
    }
}
