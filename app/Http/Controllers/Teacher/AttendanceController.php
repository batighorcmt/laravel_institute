<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherAttendance;
use App\Models\TeacherAttendanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Get school_id (works for both teachers and principals)
        $schoolId = $user->school_id ?? $user->primarySchool()?->id;
        
        if (!$schoolId) {
            return redirect()->back()->with('error', 'No school associated with your account.');
        }
        
        // Get today's attendance record
        $attendance = TeacherAttendance::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('date', $today)
            ->first();
        
        // Get attendance settings
        $settings = TeacherAttendanceSetting::where('school_id', $schoolId)->first();
        
        return view('teacher.attendance.index', compact('attendance', 'settings'));
    }

    public function checkIn(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        $now = Carbon::now();
        
        // Get school_id (works for both teachers and principals)
        $schoolId = $user->school_id ?? $user->primarySchool()?->id;
        
        if (!$schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'আপনার অ্যাকাউন্টের সাথে কোনো প্রতিষ্ঠান সংযুক্ত নেই।'
            ], 400);
        }
        
        // Validate request
        $request->validate([
            'photo' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        
        // Check if already checked in today
        $attendance = TeacherAttendance::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('date', $today)
            ->first();
        
        if ($attendance && $attendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'আপনি আজ ইতিমধ্যে চেক-ইন করেছেন!'
            ], 400);
        }
        
        // Get settings to determine status
        $settings = TeacherAttendanceSetting::where('school_id', $schoolId)->first();
        $status = 'present';
        
        if ($settings) {
            $checkInTime = $now->format('H:i:s');
            if ($checkInTime > $settings->late_threshold) {
                $status = 'late';
            }
        }
        
        // Save photo
        $photoData = $request->photo;
        $photoData = str_replace('data:image/png;base64,', '', $photoData);
        $photoData = str_replace(' ', '+', $photoData);
        $photoName = 'attendance/' . $user->id . '_checkin_' . time() . '.png';
        Storage::disk('public')->put($photoName, base64_decode($photoData));
        
        // Create or update attendance record
        if (!$attendance) {
            $attendance = new TeacherAttendance();
            $attendance->user_id = $user->id;
            $attendance->school_id = $schoolId;
            $attendance->date = $today->format('Y-m-d');
        }
        
        $attendance->check_in_time = $now->format('H:i:s');
        $attendance->check_in_photo = $photoName;
        $attendance->check_in_latitude = $request->latitude;
        $attendance->check_in_longitude = $request->longitude;
        $attendance->status = $status;
        $attendance->save();
        
        return response()->json([
            'success' => true,
            'message' => 'চেক-ইন সফল হয়েছে! আপনার উপস্থিতি রেকর্ড করা হয়েছে।',
            'status' => $status,
            'time' => $now->format('h:i A')
        ]);
    }

    public function checkOut(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        $now = Carbon::now();
        
        // Get school_id (works for both teachers and principals)
        $schoolId = $user->school_id ?? $user->primarySchool()?->id;
        
        if (!$schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'আপনার অ্যাকাউন্টের সাথে কোনো প্রতিষ্ঠান সংযুক্ত নেই।'
            ], 400);
        }
        
        // Validate request
        $request->validate([
            'photo' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        
        // Get today's attendance record
        $attendance = TeacherAttendance::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('date', $today)
            ->first();
        
        if (!$attendance || !$attendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'চেক-আউট করার আগে আপনাকে চেক-ইন করতে হবে!'
            ], 400);
        }
        
        if ($attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'আপনি আজ ইতিমধ্যে চেক-আউট করেছেন!'
            ], 400);
        }
        
        // Save photo
        $photoData = $request->photo;
        $photoData = str_replace('data:image/png;base64,', '', $photoData);
        $photoData = str_replace(' ', '+', $photoData);
        $photoName = 'attendance/' . $user->id . '_checkout_' . time() . '.png';
        Storage::disk('public')->put($photoName, base64_decode($photoData));
        
        // Update attendance record
        $attendance->check_out_time = $now->format('H:i:s');
        $attendance->check_out_photo = $photoName;
        $attendance->check_out_latitude = $request->latitude;
        $attendance->check_out_longitude = $request->longitude;
        $attendance->save();
        
        return response()->json([
            'success' => true,
            'message' => 'চেক-আউট সফল হয়েছে! আজকের দিন শেষ করার জন্য ধন্যবাদ।',
            'time' => $now->format('h:i A')
        ]);
    }

    public function myAttendance()
    {
        $user = auth()->user();
        
        // Get school_id (works for both teachers and principals)
        $schoolId = $user->school_id ?? $user->primarySchool()?->id;
        
        if (!$schoolId) {
            return redirect()->back()->with('error', 'No school associated with your account.');
        }
        
        // Get current month's attendance
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $attendances = TeacherAttendance::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();
        
        return view('teacher.attendance.my-attendance', compact('attendances'));
    }
}
