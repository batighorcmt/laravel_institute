<?php

namespace App\Http\Controllers\Principal\Institute;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StudentLeave;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Principal-side view of ALL student leave applications in the school —
 * separate from ClassTeacherStudentLeaveController (Api), which scopes a
 * class teacher to only their own sections. The principal can see and
 * review every application regardless of section.
 */
class StudentLeaveController extends Controller
{
    public function index(School $school, Request $request)
    {
        $status = $request->query('status');

        $leaves = StudentLeave::with(['student.currentEnrollment.class', 'student.currentEnrollment.section', 'reviewer'])
            ->where('school_id', $school->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'pending' => StudentLeave::where('school_id', $school->id)->where('status', 'pending')->count(),
            'approved' => StudentLeave::where('school_id', $school->id)->where('status', 'approved')->count(),
            'rejected' => StudentLeave::where('school_id', $school->id)->where('status', 'rejected')->count(),
            'on_hold' => StudentLeave::where('school_id', $school->id)->where('status', 'on_hold')->count(),
        ];

        return view('principal.institute.student-leaves.index', compact('school', 'leaves', 'status', 'stats'));
    }

    public function show(School $school, StudentLeave $leave)
    {
        if ($leave->school_id !== $school->id) {
            abort(404);
        }
        $leave->load(['student.currentEnrollment.class', 'student.currentEnrollment.section', 'reviewer']);

        return view('principal.institute.student-leaves.show', compact('school', 'leave'));
    }

    public function review(School $school, StudentLeave $leave, Request $request, PushNotificationService $pushService)
    {
        if ($leave->school_id !== $school->id) {
            abort(404);
        }

        $data = $request->validate([
            'action' => ['required', 'in:approved,rejected,on_hold'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $leave->update([
            'status' => $data['action'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => Carbon::now(),
            'review_note' => $data['note'] ?? null,
        ]);

        try {
            $pushService->sendStudentLeaveDecisionNotification($leave);
        } catch (\Throwable $e) {
            \Log::error('Student leave decision push failed: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'আবেদনটি হালনাগাদ করা হয়েছে।');
    }
}
