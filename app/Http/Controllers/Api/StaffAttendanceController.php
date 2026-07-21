<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\StaffAttendanceResource;
use App\Models\StaffAttendance;
use App\Models\SchoolAttendanceSetting;
use Illuminate\Support\Carbon;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) {
            return response()->json(['message' => 'School context unavailable'], 422);
        }
        $query = StaffAttendance::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->orderByDesc('date');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->get('date'));
        }

        $items = $query->limit(50)->get();
        return StaffAttendanceResource::collection($items);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $today = Carbon::now()->toDateString();
        $schoolIdAttr = $this->resolveSchoolId($request, $user, $request->input('school_id'));
        if (! $schoolIdAttr) {
            return response()->json(['message' => 'School context missing for staff'], 422);
        }
        if (! $user->hasRole('staff', $schoolIdAttr)) {
            return response()->json(['message' => 'Unauthorized for this school'], 403);
        }

        // Same require_photo/require_location toggles as teacher self
        // check-in, sourced from the same shared school-wide settings row.
        $settings = SchoolAttendanceSetting::where('school_id', $schoolIdAttr)->first();
        $photoRequired = $settings ? $settings->require_photo : true;
        $locationRequired = $settings ? $settings->require_location : true;

        $validated = $request->validate([
            'school_id' => ['nullable','exists:schools,id'],
            'lat' => [$locationRequired ? 'required' : 'nullable','numeric','between:-90,90'],
            'lng' => [$locationRequired ? 'required' : 'nullable','numeric','between:-180,180'],
            'photo' => [$photoRequired ? 'required' : 'nullable','image','max:2048'],
            'remarks' => ['nullable','string','max:255'],
        ]);

        $existing = StaffAttendance::where('user_id', $user->id)
            ->where('school_id', $schoolIdAttr)
            ->where('date', $today)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Already checked-in today',
                'data' => new StaffAttendanceResource($existing),
            ], 200);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('staff_attendance', 'public');
        }

        $status = 'present';
        if ($settings) {
            $lateThreshold = Carbon::createFromFormat('H:i:s', $settings->teacher_late_threshold);
            if (Carbon::now()->greaterThan(Carbon::today()->setTimeFrom($lateThreshold))) {
                $status = 'late';
            }
        } else {
            if (Carbon::now()->greaterThan(Carbon::today()->setTime(9, 30))) {
                $status = 'late';
            }
        }

        $attendance = StaffAttendance::create([
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

        return (new StaffAttendanceResource($attendance))
            ->additional(['message' => 'Check-in saved']);
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();
        $schoolIdAttr = $this->resolveSchoolId($request, $user, $request->input('school_id'));
        if (! $schoolIdAttr) {
            return response()->json(['message' => 'School context missing for staff'], 422);
        }
        if (! $user->hasRole('staff', $schoolIdAttr)) {
            return response()->json(['message' => 'Unauthorized for this school'], 403);
        }

        $settings = SchoolAttendanceSetting::where('school_id', $schoolIdAttr)->first();
        $photoRequired = $settings ? $settings->require_photo : true;
        $locationRequired = $settings ? $settings->require_location : true;

        $validated = $request->validate([
            'school_id' => ['nullable','exists:schools,id'],
            'lat' => [$locationRequired ? 'required' : 'nullable','numeric','between:-90,90'],
            'lng' => [$locationRequired ? 'required' : 'nullable','numeric','between:-180,180'],
            'photo' => [$photoRequired ? 'required' : 'nullable','image','max:2048'],
            'remarks' => ['nullable','string','max:255'],
        ]);
        $attendance = StaffAttendance::where('user_id', $user->id)
            ->where('school_id', $schoolIdAttr)
            ->where('date', $today)->first();
        if (! $attendance) {
            return response()->json(['message' => 'আজ কোনো check-in নেই'], 404);
        }
        if ($attendance->check_out_time) {
            return (new StaffAttendanceResource($attendance))
                ->additional(['message' => 'ইতিমধ্যে check-out সম্পন্ন']);
        }
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('staff_attendance', 'public');
        }
        $attendance->update([
            'check_out_time' => now()->toDateTimeString(),
            'check_out_photo' => $photoPath,
            'check_out_latitude' => $validated['lat'] ?? null,
            'check_out_longitude' => $validated['lng'] ?? null,
            'remarks' => $validated['remarks'] ?? $attendance->remarks,
        ]);
        return (new StaffAttendanceResource($attendance))
            ->additional(['message' => 'Check-out saved']);
    }

    /**
     * Universal school resolver for staff attendance context.
     */
    protected function resolveSchoolId(Request $request, $user, $explicit = null): ?int
    {
        // Resolve the same way regardless of caller (store/checkout/index) so a
        // check-in can never be written under a different school than the one
        // index()/settings() will read back — client-sent school_id is only a
        // last-resort fallback, never an override of the server-side context.
        $attr = $request->attributes->get('current_school_id');
        if ($attr) return (int) $attr;
        $firstActive = $user->firstStaffSchoolId();
        if ($firstActive) return (int) $firstActive;
        if ($explicit) return (int) $explicit;
        $any = $user->schoolRoles()->whereHas('role', fn($q)=>$q->where('name','staff'))->value('school_id');
        return $any ? (int) $any : null;
    }
}
