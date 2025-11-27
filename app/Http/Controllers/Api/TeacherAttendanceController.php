<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TeacherAttendanceResource;
use App\Models\TeacherAttendance;
use App\Models\TeacherAttendanceSetting;
use Illuminate\Support\Carbon;

class TeacherAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) {
            return response()->json(['message' => 'School context unavailable'], 422);
        }
        $query = TeacherAttendance::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->orderByDesc('date');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->get('date'));
        }

        $items = $query->limit(50)->get();
        return TeacherAttendanceResource::collection($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => ['nullable','exists:schools,id'],
            'lat' => ['nullable','numeric','between:-90,90'],
            'lng' => ['nullable','numeric','between:-180,180'],
            'photo' => ['nullable','image','max:2048'],
            'remarks' => ['nullable','string','max:255'],
        ]);

        $user = $request->user();
        $today = Carbon::now()->toDateString();
        $schoolIdAttr = $this->resolveSchoolId($request, $user, $validated['school_id'] ?? null);
        if (! $schoolIdAttr) {
            return response()->json(['message' => 'School context missing for teacher'], 422);
        }
        if (! $user->hasRole('teacher', $schoolIdAttr)) {
            return response()->json(['message' => 'Unauthorized for this school'], 403);
        }
        // Prevent duplicate check-in for same day scoped by school
        $existing = TeacherAttendance::where('user_id', $user->id)
            ->where('school_id',$schoolIdAttr)
            ->where('date', $today)->first();
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

        // Load dynamic settings (fallback to defaults if missing)
        $settings = TeacherAttendanceSetting::where('school_id', $schoolIdAttr)->first();
        $status = 'present';
        if ($settings) {
            $lateThreshold = Carbon::createFromFormat('H:i:s', $settings->late_threshold);
            if (Carbon::now()->greaterThan(Carbon::today()->setTimeFrom($lateThreshold))) {
                $status = 'late';
            }
        } else {
            // Fallback static late threshold 09:30
            if (Carbon::now()->greaterThan(Carbon::today()->setTime(9,30))) {
                $status = 'late';
            }
        }
        $attendance = TeacherAttendance::create([
            'user_id' => $user->id,
            'school_id' => $schoolIdAttr,
            'date' => $today,
            'check_in_time' => Carbon::now()->toDateTimeString(),
            'check_in_photo' => $photoPath,
            'check_in_latitude' => $validated['lat'] ?? null,
            'check_in_longitude' => $validated['lng'] ?? null,
            'status' => $status,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return (new TeacherAttendanceResource($attendance))
            ->additional(['message' => 'Check-in saved']);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'school_id' => ['nullable','exists:schools,id'],
            'lat' => ['nullable','numeric','between:-90,90'],
            'lng' => ['nullable','numeric','between:-180,180'],
            'photo' => ['nullable','image','max:2048'],
            'remarks' => ['nullable','string','max:255'],
        ]);
        $user = $request->user();
        $today = now()->toDateString();
        $schoolIdAttr = $this->resolveSchoolId($request, $user, $validated['school_id'] ?? null);
        if (! $schoolIdAttr) {
            return response()->json(['message' => 'School context missing for teacher'], 422);
        }
        if (! $user->hasRole('teacher', $schoolIdAttr)) {
            return response()->json(['message' => 'Unauthorized for this school'], 403);
        }
        $attendance = TeacherAttendance::where('user_id',$user->id)
            ->where('school_id',$schoolIdAttr)
            ->where('date',$today)->first();
        if (! $attendance) {
            return response()->json(['message' => 'আজ কোনো check-in নেই'], 404);
        }
        if ($attendance->check_out_time) {
            return (new TeacherAttendanceResource($attendance))
                ->additional(['message' => 'ইতিমধ্যে check-out সম্পন্ন']);
        }
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('teacher_attendance', 'public');
        }
        $attendance->update([
            'check_out_time' => now()->toDateTimeString(),
            'check_out_photo' => $photoPath,
            'check_out_latitude' => $validated['lat'] ?? null,
            'check_out_longitude' => $validated['lng'] ?? null,
            'remarks' => $validated['remarks'] ?? $attendance->remarks,
        ]);
        return (new TeacherAttendanceResource($attendance))
            ->additional(['message' => 'Check-out saved']);
    }

    /**
     * Universal school resolver for teacher attendance context.
     */
    protected function resolveSchoolId(Request $request, $user, $explicit = null): ?int
    {
        if ($explicit) return (int) $explicit;
        $attr = $request->attributes->get('current_school_id');
        if ($attr) return (int) $attr;
        $firstActive = $user->firstTeacherSchoolId();
        if ($firstActive) return (int) $firstActive;
        // fallback to any teacher role (inactive?)
        $any = $user->schoolRoles()->whereHas('role', fn($q)=>$q->where('name','teacher'))->value('school_id');
        return $any ? (int) $any : null;
    }
}
