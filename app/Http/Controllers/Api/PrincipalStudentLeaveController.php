<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentLeaveResource;
use App\Models\StudentLeave;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

/**
 * Principal-side review of ALL student leave applications in the school —
 * unlike ClassTeacherStudentLeaveController, not limited to a teacher's own
 * sections. Mirrors Principal\Institute\StudentLeaveController (web) but as
 * JSON for the mobile app.
 */
class PrincipalStudentLeaveController extends Controller
{
    private function resolveSchoolId(Request $request): ?int
    {
        $user = $request->user();

        return $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);
    }

    public function index(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (! $schoolId) {
            return response()->json(['message' => 'স্কুল প্রসঙ্গ পাওয়া যায়নি'], 400);
        }

        $query = StudentLeave::where('school_id', $schoolId);
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        $items = $query->orderByDesc('created_at')->paginate(25);

        return StudentLeaveResource::collection($items);
    }

    public function stats(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (! $schoolId) {
            return response()->json(['message' => 'স্কুল প্রসঙ্গ পাওয়া যায়নি'], 400);
        }

        $base = StudentLeave::where('school_id', $schoolId);

        return response()->json([
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
            'on_hold' => (clone $base)->where('status', 'on_hold')->count(),
        ]);
    }

    public function show(Request $request, StudentLeave $leave)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (! $schoolId || $leave->school_id !== $schoolId) {
            return response()->json(['message' => 'এই আবেদনটি দেখার অনুমতি নেই'], 403);
        }

        return new StudentLeaveResource($leave);
    }

    public function review(Request $request, StudentLeave $leave, PushNotificationService $pushService)
    {
        $schoolId = $this->resolveSchoolId($request);
        if (! $schoolId || $leave->school_id !== $schoolId) {
            return response()->json(['message' => 'এই আবেদনটি রিভিউ করার অনুমতি নেই'], 403);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approved,rejected,on_hold'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $leave->update([
            'status' => $validated['action'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $validated['note'] ?? null,
        ]);

        try {
            $pushService->sendStudentLeaveDecisionNotification($leave);
        } catch (\Throwable $e) {
            \Log::error('Student leave decision push failed: '.$e->getMessage());
        }

        $labels = ['approved' => 'অনুমোদিত', 'rejected' => 'বাতিল', 'on_hold' => 'স্থগিত'];
        return (new StudentLeaveResource($leave))->additional([
            'message' => 'আবেদনটি '.($labels[$validated['action']] ?? $validated['action']).' করা হয়েছে',
        ]);
    }
}
