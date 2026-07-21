<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentLeaveResource;
use App\Models\Section;
use App\Models\StudentLeave;
use App\Models\Teacher;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

/**
 * Class-teacher review of student leave applications — separate from
 * TeacherLeaveController, which is a teacher's own leave requests to the
 * principal. Scoped strictly to sections where the logged-in teacher is the
 * class_teacher (Section.class_teacher_id).
 */
class ClassTeacherStudentLeaveController extends Controller
{
    private function resolveTeacher(Request $request): ?Teacher
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->firstTeacherSchoolId();
        if (! $schoolId) {
            return null;
        }
        return Teacher::where('user_id', $user->id)->where('school_id', $schoolId)->where('status', 'active')->first();
    }

    private function mySectionIds(Teacher $teacher)
    {
        return Section::where('class_teacher_id', $teacher->id)->pluck('id');
    }

    public function index(Request $request)
    {
        $teacher = $this->resolveTeacher($request);
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }

        $sectionIds = $this->mySectionIds($teacher);
        if ($sectionIds->isEmpty()) {
            return StudentLeaveResource::collection(collect())->additional([
                'message' => 'আপনি কোনো শাখার শ্রেণি শিক্ষক নন',
            ]);
        }

        $query = StudentLeave::whereIn('section_id', $sectionIds);
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        $items = $query->orderByDesc('created_at')->paginate(25);

        return StudentLeaveResource::collection($items);
    }

    public function show(Request $request, StudentLeave $leave)
    {
        $teacher = $this->resolveTeacher($request);
        if (! $teacher || ! $this->mySectionIds($teacher)->contains($leave->section_id)) {
            return response()->json(['message' => 'এই আবেদনটি দেখার অনুমতি নেই'], 403);
        }

        return new StudentLeaveResource($leave);
    }

    public function review(Request $request, StudentLeave $leave, PushNotificationService $pushService)
    {
        $teacher = $this->resolveTeacher($request);
        if (! $teacher || ! $this->mySectionIds($teacher)->contains($leave->section_id)) {
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
