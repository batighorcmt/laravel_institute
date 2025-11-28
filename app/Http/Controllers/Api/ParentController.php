<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Homework;
use App\Models\Attendance;
use App\Models\Result;
use App\Models\TeacherLeave; // legacy teacher leave
use App\Models\StudentLeave; // new student leave
use App\Http\Resources\StudentResource;
use App\Http\Resources\HomeworkResource;
use App\Http\Resources\StudentAttendanceResource;
use App\Http\Resources\ResultResource;
use App\Http\Resources\TeacherLeaveResource;
use Illuminate\Support\Carbon;

class ParentController extends Controller
{
    public function children(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');

        // অনুমান: guardian_phone ফিল্ডে parent user এর username বা email রাখা আছে
        $query = Student::query()->active();
        if ($schoolId) {
            $query->forSchool($schoolId);
        }
        $query->where(function($q) use ($user) {
            $q->where('guardian_phone', $user->username)
              ->orWhere('guardian_phone', $user->email);
        });

        $students = $query->limit(100)->get();
        return StudentResource::collection($students)->additional([
            'count' => $students->count(),
            'message' => 'সন্তান তালিকা',
        ]);
    }

    public function homework(Request $request)
    {
        $schoolId = $request->attributes->get('current_school_id');
        $date = $request->get('date', Carbon::now()->toDateString());
        $students = $this->resolveChildren($request);
        $classIds = $students->pluck('class_id')->filter()->unique()->values();

        $query = Homework::query()->forDate($date);
        if ($schoolId) { $query->forSchool($schoolId); }
        if ($classIds->isNotEmpty()) { $query->whereIn('class_id', $classIds); }
        $homeworks = $query->orderBy('subject_id')->get();

        return HomeworkResource::collection($homeworks)->additional([
            'date' => $date,
            'children_count' => $students->count(),
            'message' => 'নির্দিষ্ট দিনের হোমওয়ার্ক',
        ]);
    }

    public function attendance(Request $request)
    {
        $date = $request->get('date'); // optional single date
        $studentId = $request->get('student_id');
        $students = $this->resolveChildren($request);
        $ids = $students->pluck('id');
        $query = Attendance::query()->whereIn('student_id', $ids);
        if ($studentId && $ids->contains($studentId)) {
            $query->where('student_id', $studentId);
        }
        if ($date) { $query->where('date', $date); }
        $query->orderByDesc('date');
        $records = $query->limit(200)->get();
        return StudentAttendanceResource::collection($records)->additional([
            'children' => $students->count(),
            'message' => 'হাজিরা তালিকা',
        ]);
    }

    public function examResults(Request $request)
    {
        $studentId = $request->get('student_id');
        $students = $this->resolveChildren($request);
        $ids = $students->pluck('id');
        $query = Result::query()->published()->whereIn('student_id', $ids)->orderByDesc('published_at');
        if ($studentId && $ids->contains($studentId)) {
            $query->where('student_id', $studentId);
        }
        if ($request->filled('exam_id')) { $query->forExam($request->get('exam_id')); }
        $results = $query->limit(100)->get();
        return ResultResource::collection($results)->additional([
            'children' => $students->count(),
            'message' => 'প্রকাশিত পরীক্ষার ফলাফল',
        ]);
    }

    public function leavesIndex(Request $request)
    {
        $schoolId = $request->attributes->get('current_school_id');
        $children = $this->resolveChildren($request);
        $studentIds = $children->pluck('id');

        $query = StudentLeave::query()->whereIn('student_id', $studentIds);
        if ($schoolId) { $query->forSchool($schoolId); }
        if ($request->filled('status')) { $query->where('status', $request->get('status')); }
        $leaves = $query->orderByDesc('start_date')->limit(100)->get();

        return \App\Http\Resources\StudentLeaveResource::collection($leaves)->additional([
            'children' => $children->count(),
            'message' => 'ছুটি আবেদন তালিকা',
        ]);
    }

    public function leavesStore(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');

        $children = $this->resolveChildren($request);
        $allowedIds = $children->pluck('id')->toArray();

        $validated = $request->validate([
            'student_id' => ['required','integer', function($attr,$value,$fail) use ($allowedIds){ if (!in_array((int)$value, $allowedIds, true)) { $fail('অবৈধ শিক্ষার্থী'); } }],
            'reason' => ['required','string','max:255'],
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after_or_equal:start_date'],
            'type' => ['nullable','string','max:50'],
        ]);

        $leave = StudentLeave::create([
            'school_id' => $schoolId,
            'student_id' => (int)$validated['student_id'],
            'type' => $validated['type'] ?? null,
            'reason' => $validated['reason'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'pending',
        ]);

        return (new \App\Http\Resources\StudentLeaveResource($leave))->additional([
            'message' => 'ছুটি আবেদন জমা হয়েছে',
        ]);
    }

    /* Utility: resolve parent children set */
    private function resolveChildren(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        $query = Student::query()->active();
        if ($schoolId) { $query->forSchool($schoolId); }
        $query->where(function($q) use ($user) {
            $q->where('guardian_phone', $user->username)
              ->orWhere('guardian_phone', $user->email);
        });
        return $query->get();
    }
}
