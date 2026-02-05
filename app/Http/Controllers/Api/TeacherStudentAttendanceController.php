<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\SchoolClass;
use App\Models\ExtraClass;
use App\Models\ExtraClassEnrollment;
use App\Models\ExtraClassAttendance;
use App\Models\Team;
use App\Models\Teacher;
use App\Models\StudentEnrollment;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use App\Services\AttendanceSmsService;

class TeacherStudentAttendanceController extends Controller
{
    public function modules(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        $classEnabled = $teacher ? Section::where('school_id',$schoolId)->where('class_teacher_id',$teacher->id)->where('status','active')->exists() : false;
        // ExtraClass model links teacher_id to users.id
        $extraEnabled = ExtraClass::where('school_id',$schoolId)->where('status','active')->where('teacher_id',$user->id)->exists();
        $teamEnabled = Team::forSchool($schoolId)->active()->exists();

        return response()->json([
            'class_attendance' => $classEnabled,
            'extra_class_attendance' => $extraEnabled,
            'team_attendance' => $teamEnabled,
        ]);
    }

    public function classMeta(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        if (! $teacher) return response()->json(['data'=>[]]);

        $sections = Section::where('school_id',$schoolId)
            ->where('class_teacher_id',$teacher->id)
            ->where('status','active')
            ->with(['schoolClass:id,name'])
            ->orderBy('class_id')
            ->orderBy('name')
            ->get(['id','name','class_id']);

        $byClass = [];
        foreach ($sections as $sec) {
            $cid = $sec->class_id;
            if (!isset($byClass[$cid])) {
                $byClass[$cid] = [
                    'class_id' => $cid,
                    'class_name' => $sec->schoolClass?->name,
                    'sections' => [],
                ];
            }
            $byClass[$cid]['sections'][] = ['id'=>$sec->id,'name'=>$sec->name];
        }
        return response()->json(array_values($byClass));
    }

    public function extraMeta(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);
        $rows = ExtraClass::where('school_id',$schoolId)
            ->where('status','active')
            ->where('teacher_id',$user->id)
            ->with(['schoolClass:id,name','section:id,name','subject:id,name'])
            ->orderByDesc('id')
            ->get(['id','class_id','section_id','subject_id','name','schedule']);
        $data = $rows->map(fn($r)=>[
            'id'=>$r->id,
            'name'=>$r->name,
            'schedule'=>$r->schedule,
            'class_name'=>$r->schoolClass?->name,
            'section_name'=>$r->section?->name,
            'subject_name'=>$r->subject?->name,
        ])->values();
        return response()->json($data);
    }

    public function teamMeta(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);
        $teams = Team::forSchool($schoolId)->active()->orderBy('name')->get(['id','name','type']);
        return response()->json($teams);
    }

    public function classSectionStudents(Request $request, Section $section)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user, $section->school_id);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        if (! $teacher || (int)$section->class_teacher_id !== (int)$teacher->id) {
            return response()->json(['message' => 'আপনি এই শাখার শ্রেণিশিক্ষক নন'], 403);
        }

        $date = $request->query('date', now()->toDateString());

        $enrollments = StudentEnrollment::where([
                'school_id' => $schoolId,
                'class_id' => $section->class_id,
                'section_id' => $section->id,
                'status' => 'active',
            ])
            // only include enrollments whose student record is active
            ->whereHas('student', fn($q)=>$q->where('status','active'))
            ->with(['student' => fn($q)=>$q->where('status','active')])
            ->orderBy('roll_no')
            ->get();

        $existing = Attendance::where('section_id', $section->id)
            ->where('date', $date)
            ->pluck('status','student_id');

        $students = $enrollments->map(function($en) use ($existing) {
            $st = $en->student;
            return [
                'id' => $st?->id,
                'name' => $st?->full_name,
                'roll' => $en->roll_no,
                'photo_url' => $st?->photo_url,
                'status' => $existing[$st?->id] ?? null,
                'gender' => $st?->gender ?? null,
            ];
        })->values();

        // Compute stats from DB for the given date
        $records = Attendance::where('section_id', $section->id)
            ->where('date', $date)
            ->get();
        $stats = [
            'total' => $records->count(),
            'present' => $records->where('status','present')->count(),
            'absent' => $records->where('status','absent')->count(),
            'late' => $records->where('status','late')->count(),
        ];
        // Add male/female counts for PRESENT students (prefer server-side present counts)
        $presentMale = Attendance::where('section_id', $section->id)
            ->where('date', $date)
            ->where('attendance.status', 'present')
            ->join('students', 'attendance.student_id', '=', 'students.id')
            ->where('students.status', 'active')
            ->where('students.gender', 'male')
            ->count();
        $presentFemale = Attendance::where('section_id', $section->id)
            ->where('date', $date)
            ->where('attendance.status', 'present')
            ->join('students', 'attendance.student_id', '=', 'students.id')
            ->where('students.status', 'active')
            ->where('students.gender', 'female')
            ->count();

        // Fallback: total male/female in enrollments (if no attendance records exist yet)
        $maleCount = $enrollments->filter(fn($e)=>($e->student?->gender ?? '') === 'male')->count();
        $femaleCount = $enrollments->filter(fn($e)=>($e->student?->gender ?? '') === 'female')->count();

        $stats['male'] = $presentMale >= 0 ? $presentMale : $maleCount;
        $stats['female'] = $presentFemale >= 0 ? $presentFemale : $femaleCount;
        $stats['present_male'] = $presentMale;
        $stats['present_female'] = $presentFemale;

        return response()->json([
            'date' => $date,
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'class_id' => $section->class_id,
                'class_name' => $section->schoolClass?->name,
            ],
            'students' => $students,
            'stats' => $stats,
        ]);
    }

    public function classSectionSubmit(Request $request, Section $section)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user, $section->school_id);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        if (! $teacher || (int)$section->class_teacher_id !== (int)$teacher->id) {
            return response()->json(['message' => 'আপনি এই শাখার শ্রেণিশিক্ষক নন'], 403);
        }

        $data = $request->validate([
            'date' => ['required','date_format:Y-m-d'],
            'items' => ['required','array','min:1'],
            'items.*.student_id' => ['required','integer'],
            'items.*.status' => ['required','in:present,absent,late'],
        ]);

        $date = $data['date'];
        // Only allow submissions for today (parity with web & extra-class)
        if ($date !== now()->toDateString()) {
            return response()->json(['message' => 'শুধুমাত্র আজকের তারিখের হাজিরা জমা দেওয়া যাবে'], 422);
        }
        $items = collect($data['items']);

        // Fetch active students for the section to validate completeness
        // ensure only active student records are considered
        $sectionStudentIds = StudentEnrollment::where([
                'school_id' => $schoolId,
                'class_id' => $section->class_id,
                'section_id' => $section->id,
                'status' => 'active',
            ])->whereHas('student', fn($q)=>$q->where('status','active'))
            ->pluck('student_id')->values();

        $submittedIds = $items->pluck('student_id')->values();
        $missing = $sectionStudentIds->diff($submittedIds);
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'সকল শিক্ষার্থীর স্ট্যাটাস নির্বাচন করা হয়নি',
                'missing_count' => $missing->count(),
            ], 422);
        }

        // Capture previous statuses before updates
        $previousStatuses = Attendance::where('class_id', $section->class_id)
            ->where('section_id', $section->id)
            ->where('date', $date)
            ->pluck('status','student_id')
            ->toArray();

        $wasExisting = !empty($previousStatuses);

        DB::transaction(function() use ($items, $date, $section, $user) {
            foreach ($items as $it) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $it['student_id'],
                        'date' => $date,
                        'class_id' => $section->class_id,
                        'section_id' => $section->id,
                    ],
                    [
                        'status' => $it['status'],
                        'recorded_by' => $user->id,
                    ]
                );
            }
        });

        // Enqueue SMS notifications in background and return report
        $smsReport = null;
        try {
            $smsService = new AttendanceSmsService();
            // Build attendance payload as expected: studentId => ['status'=>...]
            $attendancePayload = collect($items)->mapWithKeys(fn($it)=>[$it['student_id']=>['status'=>$it['status']]])->toArray();
            $schoolModel = \App\Models\School::find($section->school_id);
            if ($schoolModel) {
                // Only mark as "existing record" when we actually had previous statuses captured.
                $isExisting = !empty($previousStatuses);
                $smsReport = $smsService->enqueueAttendanceSms($schoolModel, $attendancePayload, $section->class_id, $section->id, $date, $isExisting, $previousStatuses, $user->id);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to enqueue class attendance SMS', ['error'=>$e->getMessage(), 'section_id'=>$section->id]);
        }

        $resp = ['message' => $wasExisting ? 'উপস্থিতি আপডেট হয়েছে' : 'উপস্থিতি সফলভাবে সংরক্ষিত হয়েছে'];
        if ($smsReport !== null) { $resp['sms_report'] = $smsReport; }
        return response()->json($resp);
    }

    public function extraClassStudents(Request $request, ExtraClass $extraClass)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user, $extraClass->school_id);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        if ((int)$extraClass->teacher_id !== (int)$user->id || $extraClass->status !== 'active') {
            return response()->json(['message' => 'আপনি এই এক্সট্রা ক্লাসের দায়িত্বপ্রাপ্ত নন'], 403);
        }

        $date = $request->query('date', now()->toDateString());

        $enrollments = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)
            ->where('status','active')
            ->whereHas('student', fn($q)=>$q->where('status','active'))
            ->with(['student' => fn($q)=>$q->where('status','active')->with('currentEnrollment')])
            ->orderByDesc('id')
            ->get();

        $existing = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $date)
            ->pluck('status','student_id');

        $students = $enrollments->map(function($en) use ($existing) {
            $st = $en->student;
            return [
                'id' => $st?->id,
                'name' => $st?->full_name,
                'roll' => (int)($st?->currentEnrollment?->roll_no ?? 0),
                'photo_url' => $st?->photo_url,
                'status' => $existing[$st?->id] ?? null,
                'gender' => $st?->gender ?? null,
            ];
        })->sortBy('roll')->values();

        // Stats from database records only (not local selections)
        $records = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $date)
            ->get();
        $stats = [
            'total' => $records->count(),
            'present' => $records->where('status','present')->count(),
            'absent' => $records->where('status','absent')->count(),
            'late' => $records->where('status','late')->count(),
            'excused' => $records->where('status','excused')->count(),
        ];
        // present male/female for extra class
        $presentMale = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $date)
            ->where('extra_class_attendances.status', 'present')
            ->join('students', 'extra_class_attendances.student_id', '=', 'students.id')
            ->where('students.status', 'active')
            ->where('students.gender', 'male')
            ->count();
        $presentFemale = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $date)
            ->where('extra_class_attendances.status', 'present')
            ->join('students', 'extra_class_attendances.student_id', '=', 'students.id')
            ->where('students.status', 'active')
            ->where('students.gender', 'female')
            ->count();

        $maleCount = $enrollments->filter(fn($e)=>($e->student?->gender ?? '') === 'male')->count();
        $femaleCount = $enrollments->filter(fn($e)=>($e->student?->gender ?? '') === 'female')->count();

        $stats['male'] = $presentMale >= 0 ? $presentMale : $maleCount;
        $stats['female'] = $presentFemale >= 0 ? $presentFemale : $femaleCount;
        $stats['present_male'] = $presentMale;
        $stats['present_female'] = $presentFemale;

        return response()->json([
            'date' => $date,
            'extra_class' => [
                'id' => $extraClass->id,
                'name' => $extraClass->name,
            ],
            'students' => $students,
            'stats' => $stats,
        ]);
    }

    public function extraClassSubmit(Request $request, ExtraClass $extraClass)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user, $extraClass->school_id);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        if ((int)$extraClass->teacher_id !== (int)$user->id || $extraClass->status !== 'active') {
            return response()->json(['message' => 'আপনি এই এক্সট্রা ক্লাসের দায়িত্বপ্রাপ্ত নন'], 403);
        }

        $data = $request->validate([
            'date' => ['required','date_format:Y-m-d'],
            'items' => ['required','array','min:1'],
            'items.*.student_id' => ['required','integer'],
            'items.*.status' => ['required','in:present,absent,late,excused'],
        ]);

        // Only allow submissions for today (parity with web)
        if ($data['date'] !== now()->toDateString()) {
            return response()->json(['message' => 'শুধুমাত্র আজকের তারিখের হাজিরা জমা দেওয়া যাবে'], 422);
        }

        $enrolledIds = ExtraClassEnrollment::where('extra_class_id', $extraClass->id)
            ->where('status','active')
            ->whereHas('student', fn($q)=>$q->where('status','active'))
            ->pluck('student_id');

        $submittedIds = collect($data['items'])->pluck('student_id');
        $missing = $enrolledIds->diff($submittedIds);
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'সকল শিক্ষার্থীর স্ট্যাটাস নির্বাচন করা হয়নি',
                'missing_count' => $missing->count(),
            ], 422);
        }

        // Capture previous statuses before modifications so we can avoid duplicate SMS
        $previousStatuses = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $data['date'])
            ->pluck('status','student_id')
            ->toArray();

        $wasExisting = !empty($previousStatuses);

        DB::transaction(function() use ($data, $extraClass, $user) {
            ExtraClassAttendance::where('extra_class_id', $extraClass->id)
                ->where('date', $data['date'])
                ->delete();

            foreach ($data['items'] as $it) {
                ExtraClassAttendance::create([
                    'extra_class_id' => $extraClass->id,
                    'student_id' => $it['student_id'],
                    'date' => $data['date'],
                    'status' => $it['status'],
                ]);
            }
        });

        // Fetch newly created records for stats
        $records = ExtraClassAttendance::where('extra_class_id', $extraClass->id)
            ->where('date', $data['date'])
            ->get();
        $stats = [
            'total' => $records->count(),
            'present' => $records->where('status','present')->count(),
            'absent' => $records->where('status','absent')->count(),
            'late' => $records->where('status','late')->count(),
            'excused' => $records->where('status','excused')->count(),
        ];

        // Enqueue attendance SMS for the extra class (respecting sms settings and templates)
        try {
            $smsService = new AttendanceSmsService();
            $attendancePayload = collect($data['items'])->mapWithKeys(fn($it)=>[$it['student_id']=>['status'=>$it['status']]])->toArray();
            $schoolModel = \App\Models\School::find($extraClass->school_id);
            if ($schoolModel) {
                $isExisting = !empty($previousStatuses);
                $smsService->enqueueAttendanceSms($schoolModel, $attendancePayload, $extraClass->class_id, $extraClass->section_id, $data['date'], $isExisting, $previousStatuses, $user->id, 'extra_class');
            }
        } catch (\Throwable $e) {
            // Don't fail the attendance submit if SMS enqueue fails; log for debugging
            \Log::error('Failed to enqueue extra-class attendance SMS', ['error'=>$e->getMessage(), 'extra_class_id'=>$extraClass->id]);
        }

        return response()->json([
            'message' => $wasExisting ? 'উপস্থিতি আপডেট হয়েছে' : 'উপস্থিতি সফলভাবে সংরক্ষিত হয়েছে',
            'stats' => $stats,
        ]);
    }

    protected function resolveSchoolId(Request $request, $user, $explicit = null): ?int
    {
        if ($explicit) return (int)$explicit;
        $attr = $request->attributes->get('current_school_id');
        if ($attr) return (int)$attr;
        $firstActive = $user->firstTeacherSchoolId();
        if ($firstActive) return (int)$firstActive;
        $any = $user->schoolRoles()->whereHas('role', fn($q)=>$q->where('name','teacher'))->value('school_id');
        return $any ? (int)$any : null;
    }
}
