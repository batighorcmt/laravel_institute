<?php

namespace App\Http\Controllers\Principal\Institute;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\TeacherLeave;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherLeaveController extends Controller
{
    public function index(School $school, Request $request)
    {
        $status = $request->query('status');

        $leaves = TeacherLeave::with(['teacher','teacher.user'])
            ->where('school_id', $school->id)
            ->when($status, fn($q)=>$q->where('status',$status))
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('principal.institute.teacher-leaves.index', compact('school','leaves','status'));
    }

    public function approve(School $school, TeacherLeave $leave, Request $request)
    {
        if ($leave->school_id !== $school->id) {
            abort(404);
        }
        $leave->status = 'approved';
        $leave->reviewer_id = Auth::id();
        $leave->reviewed_at = Carbon::now();
        $leave->reject_reason = null;
        $leave->save();

        return redirect()->back()->with('success', 'Leave approved.');
    }

    public function reject(School $school, TeacherLeave $leave, Request $request)
    {
        if ($leave->school_id !== $school->id) {
            abort(404);
        }
        $data = $request->validate([
            'reject_reason' => 'nullable|string|max:1000',
        ]);
        $leave->status = 'rejected';
        $leave->reviewer_id = Auth::id();
        $leave->reviewed_at = Carbon::now();
        $leave->reject_reason = $data['reject_reason'] ?? null;
        $leave->save();

        return redirect()->back()->with('success', 'Leave rejected.');
    }
}
