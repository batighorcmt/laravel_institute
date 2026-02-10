<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\TeacherLeave;
use App\Models\Teacher;
use App\Http\Resources\TeacherLeaveResource;

class TeacherLeaveController extends Controller
{
    // GET /teacher/leaves
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক'], 403);
        }

        $teacher = Teacher::where('user_id', $user->id)->where('school_id', $schoolId)->where('status','active')->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }

        $query = TeacherLeave::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacher->id);
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('start_date', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('end_date', '<=', $request->get('to'));
        }
        $items = $query->orderByDesc('start_date')->paginate(25);
        return TeacherLeaveResource::collection($items);
    }

    // POST /teacher/leaves
    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক ছুটি আবেদন করতে পারবেন'], 403);
        }

        $validated = $request->validate([
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after_or_equal:start_date'],
            'type' => ['nullable','string','max:50'],
            'reason' => ['required','string','max:255'],
        ]);

        $teacher = Teacher::where('user_id', $user->id)->where('school_id', $schoolId)->where('status','active')->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }

        $leave = TeacherLeave::create([
            'school_id' => $schoolId,
            'teacher_id' => $teacher->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'type' => $validated['type'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        return (new TeacherLeaveResource($leave))->additional([
            'message' => 'ছুটি আবেদন জমা হয়েছে',
        ]);
    }
}
