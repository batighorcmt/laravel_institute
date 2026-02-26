<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Homework;
use App\Models\Attendance;
use App\Models\Result;
use App\Models\StudentLeave;
use App\Models\RoutineEntry;
use App\Models\ExtraClassAttendance;
use App\Models\LessonEvaluationRecord;
use App\Models\ParentFeedback;
use App\Models\Teacher;
use App\Models\ClassSubject;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\StudentResource;
use App\Http\Resources\HomeworkResource;
use App\Http\Resources\StudentAttendanceResource;
use App\Http\Resources\ResultResource;
use App\Http\Resources\TeacherLeaveResource;
use App\Http\Resources\StudentProfileResource;
use App\Http\Resources\RoutineResource;
use App\Http\Resources\ParentFeedbackResource;
use App\Http\Resources\TeacherResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    public function children(Request $request)
    {
        $students = $this->resolveChildren($request);
        
        return StudentResource::collection($students)->additional([
            'count' => $students->count(),
            'message' => 'সন্তান তালিকা',
        ]);
    }

    public function homework(Request $request)
    {
        $date = $request->get('date');
        $studentId = $request->get('student_id');
        $students = $this->resolveChildren($request);
        $schoolId = $request->attributes->get('current_school_id');
        
        $query = Homework::query()->with(['subject', 'teacher']);
        
        if ($schoolId) { 
            $query->forSchool($schoolId); 
        }

        $student = null;
        if ($studentId) {
            $student = $students->where('id', $studentId)->first();
        } else {
            $student = $students->first();
        }

        if ($student) {
            $enrollment = $student->currentEnrollment;
            $classId = $student->class_id ?? $enrollment?->class_id;
            $sectionId = $enrollment?->section_id;

            if ($classId) {
                $query->where('class_id', $classId);
            }
            if ($sectionId) {
                $query->where('section_id', $sectionId);
            }
        } else {
            // Apply filters based on all children if no specific student is found
            $classIds = $students->map(function($s) {
                return $s->class_id ?? $s->currentEnrollment?->class_id;
            })->filter()->unique();
            
            $sectionIds = $students->map(fn($s) => $s->currentEnrollment?->section_id)->filter()->unique();
            
            if ($classIds->isNotEmpty()) {
                $query->whereIn('class_id', $classIds);
            }
            if ($sectionIds->isNotEmpty()) {
                $query->whereIn('section_id', $sectionIds);
            }
        }

        if ($date) {
            $query->forDate($date);
        } else {
            $query->where(function($q) {
                $q->where('homework_date', '>=', Carbon::now()->subDays(60)->toDateString())
                  ->orWhere('submission_date', '>=', Carbon::now()->toDateString());
            });
        }

        $homeworks = $query->orderByDesc('homework_date')->get();

        return HomeworkResource::collection($homeworks)->additional([
            'date' => $date ?? Carbon::now()->toDateString(),
            'children_count' => $students->count(),
            'message' => $date ? 'নির্দিষ্ট দিনের হোমওয়ার্ক' : 'সাম্প্রতিক হোমওয়ার্ক',
        ]);
    }

    public function attendance(Request $request)
    {
        $date = $request->get('date'); // optional single date
        $month = $request->get('month'); // 1-12
        $year = $request->get('year');
        $studentId = $request->get('student_id');
        $students = $this->resolveChildren($request);
        $ids = $students->pluck('id');
        $query = Attendance::query()->whereIn('student_id', $ids);
        if ($studentId && $ids->contains($studentId)) {
            $query->where('student_id', $studentId);
        }
        if ($date) { 
            $query->whereDate('date', $date); 
        } elseif ($month && $year) {
            $query->whereMonth('date', $month)->whereYear('date', $year);
        }
        $query->orderByDesc('date');
        $records = $query->limit(200)->get();
        return StudentAttendanceResource::collection($records)->additional([
            'children' => $students->count(),
            'message' => 'হাজিরা তালিকা',
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

    public function profile(Request $request)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        return new StudentProfileResource($student);
    }

    public function subjects(Request $request)
    {
        $studentId = $request->get('student_id');
        $students = $this->resolveChildren($request);
        $student = $studentId ? $students->firstWhere('id', $studentId) : $students->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $enrollment = $student->currentEnrollment;
        if (!$enrollment) {
            return response()->json(['data' => [], 'message' => 'অ্যাক্টিভ এনরোলমেন্ট পাওয়া যায়নি']);
        }

        // Check if student has specific subject assignments
        $assignedSubjects = StudentSubject::where('student_enrollment_id', $enrollment->id)
            ->where('status', 'active')
            ->with('subject')
            ->get();

        if ($assignedSubjects->isNotEmpty()) {
            return response()->json([
                'data' => $assignedSubjects->map(fn($s) => [
                    'id' => $s->subject_id,
                    'name' => $s->subject->name ?? 'N/A',
                    'code' => $s->subject->code ?? '',
                    'is_optional' => $s->is_optional,
                ])->values(),
                'message' => 'অ্যাসাইনকৃত বিষয় সমূহ',
            ]);
        }

        // Fallback to class subjects if no specific assignments
        $classId = $student->class_id ?? $enrollment->class_id;
        $subjects = ClassSubject::where('class_id', $classId)
            ->where('school_id', $student->school_id)
            ->where('status', 'active')
            ->whereHas('subject')
            ->with('subject')
            ->get()
            ->unique('subject_id');

        return response()->json([
            'data' => $subjects->map(fn($s) => [
                'id' => $s->subject_id,
                'name' => $s->subject->name ?? 'N/A',
                'code' => $s->subject->code ?? '',
                'is_optional' => $s->is_optional,
            ])->values(),
            'message' => 'পঠিত বিষয় সমূহ',
        ]);
    }

    public function classRoutine(Request $request)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        // We need section_id from enrollment
        $enrollment = $student->currentEnrollment;
        if (!$enrollment) {
            return response()->json(['message' => 'এনরোলমেন্ট পাওয়া যায়নি'], 404);
        }

        $routine = RoutineEntry::where('class_id', $enrollment->class_id)
            ->where('section_id', $enrollment->section_id)
            ->with(['subject', 'teacher'])
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->get();

        return RoutineResource::collection($routine)->additional([
            'message' => 'ক্লাস রুটিন',
        ]);
    }



    public function lessonEvaluations(Request $request)
    {
        $studentId = $request->get('student_id');
        $children = $this->resolveChildren($request);
        $student = $studentId ? $children->firstWhere('id', $studentId) : $children->first();

        if (!$student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $records = LessonEvaluationRecord::where('student_id', $student->id)
            ->with(['lessonEvaluation.subject', 'lessonEvaluation.teacher'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $records->map(fn($r) => [
                'id' => $r->id,
                'date' => $r->lessonEvaluation->date,
                'subject' => $r->lessonEvaluation->subject->name ?? 'N/A',
                'teacher' => $r->lessonEvaluation->teacher->name ?? 'N/A',
                'lesson' => $r->lessonEvaluation->lesson_name,
                'status' => $r->status_label,
                'remarks' => $r->remarks,
            ]),
            'message' => 'লেসন ইভ্যালুয়েশন রিপোর্ট',
        ]);
    }

    public function teachers(Request $request)
    {
        $children = $this->resolveChildren($request);
        $student = $children->first();
        $schoolId = $student?->school_id;
        
        $query = Teacher::query()->active();
        if ($schoolId) {
            $query->forSchool($schoolId);
        }
        
        $teachers = $query->orderBy('first_name')->get();

        return TeacherResource::collection($teachers)->additional([
            'message' => 'বিদ্যালয়ের সকল শিক্ষক তালিকা',
        ]);
    }

    public function feedbackIndex(Request $request)
    {
        $user = $request->user();
        $feedbacks = ParentFeedback::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return ParentFeedbackResource::collection($feedbacks)->additional([
            'message' => 'মতামত/অভিযোগ তালিকা',
        ]);
    }

    public function feedbackStore(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        
        $children = $this->resolveChildren($request);
        $studentId = $request->get('student_id', $children->first()?->id);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $feedback = ParentFeedback::create([
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'student_id' => $studentId,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        return new ParentFeedbackResource($feedback);
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        $user = $request->user();
        $path = $request->file('photo')->store('avatars', 'public');

        $user->avatar = $path;
        $user->save();

        return response()->json([
            'message' => 'প্রোফাইল ছবি আপডেট হয়েছে',
            'photo_url' => Storage::url($path),
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'বর্তমান পাসওয়ার্ড সঠিক নয়'], 422);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $user->password_changed_at = now();
        $user->save();

        return response()->json(['message' => 'পাসওয়ার্ড সফলভাবে পরিবর্তিত হয়েছে']);
    }

    /* Utility: resolve parent children set */
    private function resolveChildren(Request $request)
    {
        $user = $request->user();
        
        // ১. সরাসরি ইউজার আইডির সাথে যুক্ত শিক্ষার্থী
        $directStudent = Student::active()->where('user_id', $user->id)->with('currentEnrollment')->first();
        if ($directStudent) {
            return collect([$directStudent]);
        }

        // ২. অভিভাবক হিসেবে যুক্ত শিক্ষার্থী
        $query = Student::query()->active()->with(['currentEnrollment', 'class', 'school']);
        
        $phone = $user->username;
        $cleanPhone = ltrim(str_replace(['+', '88'], '', $phone), '0');
        
        $query->where(function($q) use ($user, $phone, $cleanPhone) {
            $q->where('guardian_phone', $phone)
              ->orWhere('guardian_phone', '0' . $cleanPhone)
              ->orWhere('guardian_phone', '880' . $cleanPhone)
              ->orWhere('guardian_phone', '+880' . $cleanPhone)
              ->orWhere('guardian_phone', $user->email);
        });
        
        return $query->get();
    }
}
