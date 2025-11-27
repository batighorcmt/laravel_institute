<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherLeave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            return redirect()->back()->with('error', 'Teacher profile not found.');
        }

        $leaves = TeacherLeave::where('teacher_id', $teacher->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('teacher.leave.index', compact('leaves'));
    }

    public function create()
    {
        return view('teacher.leave.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            return redirect()->back()->with('error', 'Teacher profile not found.');
        }

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|string|max:50',
            'reason' => 'nullable|string|max:2000',
        ]);

        TeacherLeave::create([
            'school_id' => $teacher->school_id,
            'teacher_id' => $teacher->id,
            'start_date' => Carbon::parse($data['start_date'])->toDateString(),
            'end_date' => Carbon::parse($data['end_date'])->toDateString(),
            'type' => $data['type'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        return redirect()->route('teacher.leave.index')->with('success', 'Leave application submitted for approval.');
    }
}
