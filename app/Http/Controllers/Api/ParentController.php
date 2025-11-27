<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Homework;
use App\Models\Attendance;
use App\Models\Result;
use App\Models\TeacherLeave; // reused for leave placeholder until student leave exists
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
        // Placeholder: আসল স্টুডেন্ট ছুটির মডেল না থাকায় শিক্ষক ছুটি রিটার্ন
        $schoolId = $request->attributes->get('current_school_id');
        $query = TeacherLeave::query();
        if ($schoolId) { $query->where('school_id', $schoolId); }
        $leaves = $query->orderByDesc('start_date')->limit(50)->get();
        return TeacherLeaveResource::collection($leaves)->additional([
            'message' => 'ছুটি আবেদন (প্লেসহোল্ডার)',
        ]);
    }

    public function leavesStore(Request $request)
    {
        // Placeholder: বাস্তব স্টুডেন্ট লিভ মডেল না থাকায় শুধু ভ্যালিডেশন রিটার্ন
        $validated = $request->validate([
            'reason' => ['required','string','max:255'],
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after_or_equal:start_date'],
            'type' => ['nullable','string','max:50'],
        ]);
        return response()->json([
            'saved' => false,
            'data' => $validated,
            'message' => 'স্টুডেন্ট লিভ মডেল অনুপস্থিত; আগে মডেল তৈরি করুন',
        ], 501);
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
