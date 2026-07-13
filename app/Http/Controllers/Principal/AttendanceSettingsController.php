<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolAttendanceSetting;
use Illuminate\Http\Request;

class AttendanceSettingsController extends Controller
{
    public function index(School $school, Request $request)
    {
        $settings = SchoolAttendanceSetting::firstOrNew(['school_id' => $school->id]);
        return view('principal.attendance.settings', compact('school', 'settings'));
    }

    public function store(School $school, Request $request)
    {
        $validated = $request->validate([
            'student_entry_start'     => 'required|date_format:H:i',
            'student_entry_end'       => 'required|date_format:H:i',
            'student_late_threshold'  => 'required|date_format:H:i',
            'student_exit_start'      => 'required|date_format:H:i',
            'student_exit_end'        => 'required|date_format:H:i',
            'teacher_check_in_start'  => 'required|date_format:H:i',
            'teacher_check_in_end'    => 'required|date_format:H:i',
            'teacher_late_threshold'  => 'required|date_format:H:i',
            'teacher_check_out_start' => 'required|date_format:H:i',
            'teacher_check_out_end'   => 'required|date_format:H:i',
        ]);

        // Append seconds for time columns
        $timeFields = array_keys($validated);
        foreach ($timeFields as $field) {
            $validated[$field] = $validated[$field] . ':00';
        }

        SchoolAttendanceSetting::updateOrCreate(
            ['school_id' => $school->id],
            $validated
        );

        return redirect()->route('principal.institute.attendance.settings', $school)
            ->with('success', 'হাজিরার নিয়মকানুন সফলভাবে সংরক্ষণ করা হয়েছে।');
    }
}
