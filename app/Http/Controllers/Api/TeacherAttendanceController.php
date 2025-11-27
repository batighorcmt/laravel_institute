<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TeacherAttendanceResource;
use App\Models\TeacherAttendance;
use Illuminate\Support\Carbon;

class TeacherAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = TeacherAttendance::where('user_id', $user->id)->orderByDesc('date');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->get('date'));
        }

        $items = $query->limit(50)->get();
        return TeacherAttendanceResource::collection($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lat' => ['nullable','numeric','between:-90,90'],
            'lng' => ['nullable','numeric','between:-180,180'],
            'photo' => ['nullable','image','max:2048'],
            'remarks' => ['nullable','string','max:255'],
        ]);

        $user = $request->user();
        $today = Carbon::now()->toDateString();

        // Prevent duplicate check-in for same day (simple constraint)
        $existing = TeacherAttendance::where('user_id', $user->id)->where('date', $today)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Already checked-in today',
                'data' => new TeacherAttendanceResource($existing),
            ], 200);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('teacher_attendance', 'public');
        }

        $attendance = TeacherAttendance::create([
            'user_id' => $user->id,
            'school_id' => $request->attributes->get('current_school_id'),
            'date' => $today,
            'check_in_time' => Carbon::now()->toDateTimeString(),
            'check_in_photo' => $photoPath,
            'check_in_latitude' => $validated['lat'] ?? null,
            'check_in_longitude' => $validated['lng'] ?? null,
            'status' => 'present',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return (new TeacherAttendanceResource($attendance))
            ->additional(['message' => 'Check-in saved']);
    }
}
