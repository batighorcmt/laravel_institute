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
use App\Models\TeamAttendance;
use App\Models\Teacher;
use App\Models\StudentEnrollment;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AttendanceSmsService;

class TeacherStudentAttendanceController extends Controller
{
    public function modules(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        if ($isPrincipal) {
            $classEnabled = Section::where('school_id', $schoolId)->where('status', 'active')->exists();
            $extraEnabled = ExtraClass::where('school_id', $schoolId)->where('status', 'active')->exists();
            $teamEnabled = Team::forSchool($schoolId)->active()->exists();
        } else {
            $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->where('status','active')->first();
            $classEnabled = $teacher ? Section::where('school_id',$schoolId)->where('class_teacher_id',$teacher->id)->where('status','active')->exists() : false;
            // ExtraClass model links teacher_id to users.id
            $extraEnabled = ExtraClass::where('school_id',$schoolId)->where('status','active')->where('teacher_id',$user->id)->exists();
            // Team.teacher_id likewise links straight to users.id — a plain
            // teacher only gets the module if assigned to one of their own teams
            $teamEnabled = Team::forSchool($schoolId)->active()->where('teacher_id', $user->id)->exists();
        }

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

        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');
        $sectionsQuery = Section::where('school_id', $schoolId)->where('status', 'active');
        if($academicYearId) {
            $sectionsQuery->whereHas('enrollments', function($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId)->where('status','active');
            });
        }
        if (!$isPrincipal) {
            $teacher = Teacher::where('user_id', $user->id)->where('school_id', $schoolId)->where('status', 'active')->first();
            if (!$teacher) return response()->json(['data' => []]);
            $sectionsQuery->where('class_teacher_id', $teacher->id);
        }

        $sections = $sectionsQuery->with(['schoolClass:id,name'])
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
        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');

        $rowsQuery = ExtraClass::where('school_id', $schoolId)->where('status', 'active');
        if ($academicYearId) {
            $rowsQuery->where('academic_year_id', $academicYearId);
        }
        if (!$isPrincipal) {
            $rowsQuery->where('teacher_id', $user->id);
        }

        $rows = $rowsQuery->with(['schoolClass:id,name','section:id,name','subject:id,name'])
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
        $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');
        $teamsQuery = Team::forSchool($schoolId)->active();
        if (! ($user->isPrincipal($schoolId) || $user->isSuperAdmin())) {
            $teamsQuery->where('teacher_id', $user->id);
        }
        if ($academicYearId) {
            $teamsQuery->whereHas('students.enrollments', function($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId)->where('status','active');
            });
        }
        $teams = $teamsQuery->orderBy('name')->get(['id','name','type']);
        return response()->json($teams);
    }

    public function classSectionStudents(Request $request, Section $section)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user, $section->school_id);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        if (!$isPrincipal) {
            $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->where('status','active')->first();
            if (! $teacher || (int)$section->class_teacher_id !== (int)$teacher->id) {
                return response()->json(['message' => 'আপনি এই শাখার শ্রেণিশিক্ষক নন'], 403);
            }
        }

        $date = $request->query('date', now()->toDateString());
        $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');

        $enrollments = StudentEnrollment::where([
                'school_id' => $schoolId,
                'class_id' => $section->class_id,
                'section_id' => $section->id,
                'status' => 'active',
            ])
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            // only include enrollments whose student record is active
            ->whereHas('student', fn($q)=>$q->where('status','active'))
            ->with(['student' => fn($q)=>$q->where('status','active')])
            ->orderBy('roll_no')
            ->get();

        $existing = Attendance::where('section_id', $section->id)
            ->where('date', $date)
            ->pluck('status','student_id');

        // Approved leave covering this date — surfaced to the teacher as
        // "ছুটি অনুমোদিত" so they know why a student is absent before
        // marking attendance (mirrors the same badge on the web panel).
        $onLeave = \App\Models\StudentLeave::where('school_id', $schoolId)
            ->where('class_id', $section->class_id)
            ->where('section_id', $section->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->pluck('student_id')
            ->flip();

        $students = $enrollments->map(function($en) use ($existing, $onLeave) {
            $st = $en->student;
            return [
                'id' => $st?->id,
                'student_id' => $st?->student_id,
                'name' => $st?->full_name,
                'roll' => $en->roll_no,
                'photo_url' => $st?->photo_url,
                'status' => $existing[$st?->id] ?? null,
                'gender' => $st?->gender ?? null,
                'on_leave' => $st?->id ? isset($onLeave[$st->id]) : false,
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

        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        if (!$isPrincipal) {
            $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->where('status','active')->first();
            if (! $teacher || (int)$section->class_teacher_id !== (int)$teacher->id) {
                return response()->json(['message' => 'আপনি এই শাখার শ্রেণিশিক্ষক নন'], 403);
            }
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

        $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');

        // Fetch active students for the section to validate completeness
        // ensure only active student records are considered
        $sectionStudentIds = StudentEnrollment::where([
                'school_id' => $schoolId,
                'class_id' => $section->class_id,
                'section_id' => $section->id,
                'status' => 'active',
            ])
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->whereHas('student', fn($q)=>$q->where('status','active'))
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
                // Biometric lock: if attendance was recorded via biometric machine, block mobile override
                $existing = Attendance::where('student_id', $it['student_id'])
                    ->where('date', $date)
                    ->where('class_id', $section->class_id)
                    ->where('section_id', $section->id)
                    ->first();

                if ($existing && $existing->medium === 'biometric') {
                    // Skip – cannot override biometric attendance from mobile app
                    continue;
                }

                Attendance::updateOrCreate(
                    [
                        'student_id' => $it['student_id'],
                        'date' => $date,
                        'class_id' => $section->class_id,
                        'section_id' => $section->id,
                    ],
                    [
                        'school_id' => $section->school_id,
                        'status' => $it['status'],
                        'recorded_by' => $user->id,
                        'medium' => 'mobile_app',
                    ]
                );
            }
        });

        // Enqueue SMS and Push notifications in background and return report
        $smsReport = null;
        try {
            $smsService = new AttendanceSmsService();
            $pushService = new \App\Services\PushNotificationService();
            // Build attendance payload as expected: studentId => ['status'=>...]
            $attendancePayload = collect($items)->mapWithKeys(fn($it)=>[$it['student_id']=>['status'=>$it['status']]])->toArray();
            $schoolModel = \App\Models\School::find($section->school_id);
            if ($schoolModel) {
                // Only mark as "existing record" when we actually had previous statuses captured.
                $isExisting = !empty($previousStatuses);
                $smsReport = $smsService->enqueueAttendanceSms($schoolModel, $attendancePayload, $section->class_id, $section->id, $date, $isExisting, $previousStatuses, $user->id);
                
                // Send Push Notifications
                foreach ($items as $it) {
                    // Avoid duplicate push if status hasn't changed (optional, but good for UX)
                    if (!$wasExisting || (isset($previousStatuses[$it['student_id']]) && $previousStatuses[$it['student_id']] !== $it['status'])) {
                        $pushService->sendAttendanceNotification($it['student_id'], $it['status'], $date, 'class');
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to enqueue class attendance notifications', ['error'=>$e->getMessage(), 'section_id'=>$section->id]);
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

        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        if (!$isPrincipal) {
            if ((int)$extraClass->teacher_id !== (int)$user->id || $extraClass->status !== 'active') {
                return response()->json(['message' => 'আপনি এই এক্সট্রা ক্লাসের দায়িত্বপ্রাপ্ত নন'], 403);
            }
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
                'student_id' => $st?->student_id,
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
                'class_id' => $extraClass->class_id,
                'section_id' => $extraClass->section_id,
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

        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        if (!$isPrincipal) {
            if ((int)$extraClass->teacher_id !== (int)$user->id || $extraClass->status !== 'active') {
                return response()->json(['message' => 'আপনি এই এক্সট্রা ক্লাসের দায়িত্বপ্রাপ্ত নন'], 403);
            }
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

        // Enqueue attendance notifications for the extra class (respecting settings and templates)
        try {
            $smsService = new AttendanceSmsService();
            $pushService = new \App\Services\PushNotificationService();
            $attendancePayload = collect($data['items'])->mapWithKeys(fn($it)=>[$it['student_id']=>['status'=>$it['status']]])->toArray();
            $schoolModel = \App\Models\School::find($extraClass->school_id);
            if ($schoolModel) {
                $isExisting = !empty($previousStatuses);
                $smsService->enqueueAttendanceSms($schoolModel, $attendancePayload, $extraClass->class_id, $extraClass->section_id, $data['date'], $isExisting, $previousStatuses, $user->id, 'extra_class');

                // Send Push Notifications
                foreach ($data['items'] as $it) {
                    if (!$wasExisting || (isset($previousStatuses[$it['student_id']]) && $previousStatuses[$it['student_id']] !== $it['status'])) {
                        $pushService->sendAttendanceNotification($it['student_id'], $it['status'], $data['date'], 'extra_class');
                    }
                }
            }
        } catch (\Throwable $e) {
            // Don't fail the attendance submit if notification enqueue fails; log for debugging
            Log::error('Failed to enqueue extra-class attendance notifications', ['error'=>$e->getMessage(), 'extra_class_id'=>$extraClass->id]);
        }

        return response()->json([
            'message' => $wasExisting ? 'উপস্থিতি আপডেট হয়েছে' : 'উপস্থিতি সফলভাবে সংরক্ষিত হয়েছে',
            'stats' => $stats,
        ]);
    }

    public function teamStudents(Request $request, Team $team)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user, $team->school_id);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        if (!$isPrincipal) {
            if ((int)$team->teacher_id !== (int)$user->id || $team->status !== 'active') {
                return response()->json(['message' => 'আপনি এই টিমের দায়িত্বপ্রাপ্ত নন'], 403);
            }
        }

        $date = $request->query('date', now()->toDateString());
        $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');

        $enrollments = StudentEnrollment::query()
            ->join('team_student', 'team_student.student_id', '=', 'student_enrollments.student_id')
            ->where('team_student.team_id', $team->id)
            ->where('team_student.status', 'active')
            ->where('student_enrollments.school_id', $schoolId)
            ->where('student_enrollments.status', 'active')
            ->when($academicYearId, fn($q) => $q->where('student_enrollments.academic_year_id', $academicYearId))
            ->whereHas('student', fn($q) => $q->where('status', 'active'))
            ->with(['student' => fn($q) => $q->where('status', 'active'), 'class', 'section'])
            ->select('student_enrollments.*')
            ->get();

        // Team members can come from different classes/sections, so sort by
        // class order then roll number rather than roll alone — otherwise
        // roll numbers from unrelated classes interleave meaninglessly.
        $enrollmentsArr = $enrollments->all();
        usort($enrollmentsArr, function ($a, $b) {
            $byClass = ($a->class?->numeric_value ?? 0) <=> ($b->class?->numeric_value ?? 0);
            if ($byClass !== 0) return $byClass;
            $bySection = strcmp($a->section?->name ?? '', $b->section?->name ?? '');
            if ($bySection !== 0) return $bySection;
            return ($a->roll_no ?? 0) <=> ($b->roll_no ?? 0);
        });
        $enrollments = collect($enrollmentsArr);

        $studentIds = $enrollments->pluck('student_id');

        // Class (section) attendance is the gate: a student can't be marked
        // for team attendance until their class attendance for the day
        // exists, and if class marked them absent, team attendance is forced
        // absent too — surfaced here so the app can lock/disable those rows.
        $classStatuses = Attendance::where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->pluck('status', 'student_id');

        $teamStatuses = TeamAttendance::where('team_id', $team->id)
            ->where('date', $date)
            ->pluck('status', 'student_id');

        $students = $enrollments->map(function ($en) use ($classStatuses, $teamStatuses) {
            $st = $en->student;
            $classStatus = $classStatuses[$en->student_id] ?? null;
            $canMark = $classStatus !== null;
            $status = $canMark
                ? ($classStatus === 'absent' ? 'absent' : ($teamStatuses[$en->student_id] ?? null))
                : null;

            return [
                'id' => $st?->id,
                'student_id' => $st?->student_id,
                'name' => $st?->full_name,
                'roll' => $en->roll_no,
                'class_id' => $en->class_id,
                'class_name' => $en->class?->name,
                'section_id' => $en->section_id,
                'section_name' => $en->section?->name,
                'photo_url' => $st?->photo_url,
                'gender' => $st?->gender ?? null,
                'class_status' => $classStatus,
                'can_mark' => $canMark,
                'status' => $status,
            ];
        })->values();

        $records = TeamAttendance::where('team_id', $team->id)->where('date', $date)->get();
        $stats = [
            'total' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
        ];

        return response()->json([
            'date' => $date,
            'team' => ['id' => $team->id, 'name' => $team->name],
            'students' => $students,
            'any_markable' => $students->contains(fn ($s) => $s['can_mark']),
            'stats' => $stats,
        ]);
    }

    public function teamSubmit(Request $request, Team $team)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user, $team->school_id);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        if (!$isPrincipal) {
            if ((int)$team->teacher_id !== (int)$user->id || $team->status !== 'active') {
                return response()->json(['message' => 'আপনি এই টিমের দায়িত্বপ্রাপ্ত নন'], 403);
            }
        }

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.student_id' => ['required', 'integer'],
            'items.*.status' => ['required', 'in:present,absent,late'],
        ]);

        if ($data['date'] !== now()->toDateString()) {
            return response()->json(['message' => 'শুধুমাত্র আজকের তারিখের হাজিরা জমা দেওয়া যাবে'], 422);
        }

        $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');

        $enrollments = StudentEnrollment::query()
            ->join('team_student', 'team_student.student_id', '=', 'student_enrollments.student_id')
            ->where('team_student.team_id', $team->id)
            ->where('team_student.status', 'active')
            ->where('student_enrollments.school_id', $schoolId)
            ->where('student_enrollments.status', 'active')
            ->when($academicYearId, fn($q) => $q->where('student_enrollments.academic_year_id', $academicYearId))
            ->whereHas('student', fn($q) => $q->where('status', 'active'))
            ->select('student_enrollments.*')
            ->get()
            ->keyBy('student_id');

        $classStatuses = Attendance::where('date', $data['date'])
            ->whereIn('student_id', $enrollments->keys())
            ->pluck('status', 'student_id');

        $markableIds = $classStatuses->keys();
        $submittedByStudent = collect($data['items'])->keyBy('student_id');

        // Only members whose class attendance was already taken today are
        // required (and allowed) to be recorded — the rest simply can't have
        // team attendance yet.
        $missing = $enrollments->keys()->intersect($markableIds)->diff($submittedByStudent->keys());
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'সকল শিক্ষার্থীর স্ট্যাটাস নির্বাচন করা হয়নি',
                'missing_count' => $missing->count(),
            ], 422);
        }

        $previousStatuses = TeamAttendance::where('team_id', $team->id)
            ->where('date', $data['date'])
            ->pluck('status', 'student_id')
            ->toArray();
        $wasExisting = !empty($previousStatuses);

        $rowsToWrite = [];
        foreach ($enrollments as $studentId => $enrollment) {
            if (!$markableIds->contains($studentId)) {
                continue; // class attendance not taken yet — skip entirely
            }
            $classStatus = $classStatuses[$studentId];
            $submitted = $submittedByStudent[$studentId]['status'] ?? 'absent';
            // Class absence overrides whatever was submitted for team attendance.
            $finalStatus = $classStatus === 'absent' ? 'absent' : $submitted;

            $rowsToWrite[] = [
                'student_id' => $studentId,
                'class_id' => $enrollment->class_id,
                'section_id' => $enrollment->section_id,
                'status' => $finalStatus,
            ];
        }

        if (empty($rowsToWrite)) {
            return response()->json([
                'message' => 'কোনো শিক্ষার্থীর শ্রেণি হাজিরা এখনো নেওয়া হয়নি, তাই টিম হাজিরা নেওয়া যাচ্ছে না।',
            ], 422);
        }

        DB::transaction(function () use ($rowsToWrite, $team, $schoolId, $data, $user) {
            TeamAttendance::where('team_id', $team->id)->where('date', $data['date'])->delete();
            foreach ($rowsToWrite as $row) {
                TeamAttendance::create([
                    'school_id' => $schoolId,
                    'team_id' => $team->id,
                    'student_id' => $row['student_id'],
                    'class_id' => $row['class_id'],
                    'section_id' => $row['section_id'],
                    'date' => $data['date'],
                    'status' => $row['status'],
                    'recorded_by' => $user->id,
                ]);
            }
        });

        $records = TeamAttendance::where('team_id', $team->id)->where('date', $data['date'])->get();
        $stats = [
            'total' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
        ];

        return response()->json([
            'message' => $wasExisting ? 'উপস্থিতি আপডেট হয়েছে' : 'উপস্থিতি সফলভাবে সংরক্ষিত হয়েছে',
            'stats' => $stats,
        ]);
    }

    public function studentStats(Request $request, $studentId)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $student = Student::where('id', $studentId)->where('school_id', $schoolId)->first();
        if (! $student) return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);

        $classId = (int) $request->query('class_id');
        $sectionId = (int) $request->query('section_id');
        if (! $classId || ! $sectionId) {
            return response()->json(['message' => 'class_id ও section_id প্রয়োজন'], 422);
        }

        $isPrincipal = $user->isPrincipal($schoolId) || $user->isSuperAdmin();
        if (! $isPrincipal) {
            // Reachable either from the class-attendance page (must be this
            // section's class teacher) or the extra-class page (must own an
            // extra class for this exact class/section) — either grants view.
            $teacher = Teacher::where('user_id', $user->id)->where('school_id', $schoolId)->where('status', 'active')->first();
            $ownsSection = $teacher && Section::where('id', $sectionId)->where('class_id', $classId)->where('class_teacher_id', $teacher->id)->exists();
            $ownsExtraClass = ExtraClass::where('school_id', $schoolId)->where('teacher_id', $user->id)->where('status', 'active')
                ->where('class_id', $classId)->where('section_id', $sectionId)->exists();
            if (! $ownsSection && ! $ownsExtraClass) {
                return response()->json(['message' => 'আপনি এই শ্রেণির শিক্ষার্থীর তথ্য দেখার অনুমতিপ্রাপ্ত নন'], 403);
            }
        }

        $academicYear = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->first();

        $today = \Carbon\Carbon::today();
        $startDate = $academicYear?->start_date ? \Carbon\Carbon::parse($academicYear->start_date) : $today->copy()->startOfYear();
        if ($student->admission_date) {
            $admission = \Carbon\Carbon::parse($student->admission_date);
            if ($admission->gt($startDate)) $startDate = $admission->copy();
        }
        $endDate = $today->copy();

        $weeklyHolidays = \App\Models\WeeklyHoliday::where('school_id', $schoolId)->active()->pluck('day_number')->toArray();
        $holidaySet = \App\Models\Holiday::where('school_id', $schoolId)->active()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->flip();

        $isHolidayDate = function (\Carbon\Carbon $date) use ($weeklyHolidays, $holidaySet) {
            $dayNum = $date->dayOfWeek == 0 ? 7 : $date->dayOfWeek;
            if (in_array($dayNum, $weeklyHolidays)) return true;
            return isset($holidaySet[$date->toDateString()]);
        };

        $totalWorkingDays = 0;
        if ($startDate->lte($endDate)) {
            foreach (\Carbon\CarbonPeriod::create($startDate, $endDate) as $d) {
                if (! $isHolidayDate($d)) $totalWorkingDays++;
            }
        }

        $attendanceRecords = Attendance::where('student_id', $student->id)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['date', 'status']);
        $attendanceByDate = $attendanceRecords->keyBy(fn($a) => $a->date->toDateString());

        $present = $attendanceRecords->where('status', 'present')->count();
        $absent = $attendanceRecords->where('status', 'absent')->count();
        $late = $attendanceRecords->where('status', 'late')->count();

        // Approved leave days overlapping the range — excused, so they must
        // not inflate the absence count or break/count toward the streak.
        $approvedLeaves = \App\Models\StudentLeave::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $endDate->toDateString())
            ->where('end_date', '>=', $startDate->toDateString())
            ->get(['start_date', 'end_date']);

        $leaveDateSet = [];
        foreach ($approvedLeaves as $leave) {
            $ls = $leave->start_date->lt($startDate) ? $startDate->copy() : $leave->start_date->copy();
            $le = $leave->end_date->gt($endDate) ? $endDate->copy() : $leave->end_date->copy();
            for ($d = $ls->copy(); $d->lte($le); $d->addDay()) {
                $leaveDateSet[$d->toDateString()] = true;
            }
        }
        $approvedLeaveCount = count($leaveDateSet);

        // Consecutive-absence streak: walk backwards from "today if its
        // attendance is already recorded, else yesterday" until the last day
        // the student actually attended (present/late) — holidays and
        // approved-leave days are skipped, not counted and not stop points.
        $streakEnd = $attendanceByDate->has($today->toDateString()) ? $today->copy() : $today->copy()->subDay();
        $consecutiveAbsent = 0;
        $cursor = $streakEnd->copy();
        $iterations = 0;
        while ($cursor->gte($startDate) && $iterations < 400) {
            $iterations++;
            $dateStr = $cursor->toDateString();
            if ($isHolidayDate($cursor) || isset($leaveDateSet[$dateStr])) {
                $cursor->subDay();
                continue;
            }
            $rec = $attendanceByDate->get($dateStr);
            if (! $rec) break; // no record for this day — boundary reached
            if (in_array($rec->status, ['present', 'late'])) break; // last attended day
            if ($rec->status === 'absent') {
                $consecutiveAbsent++;
                $cursor->subDay();
                continue;
            }
            break;
        }

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'photo_url' => $student->photo_url,
            ],
            'as_of_date' => $today->toDateString(),
            'academic_year_start' => $startDate->toDateString(),
            'total_working_days' => $totalWorkingDays,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'approved_leave' => $approvedLeaveCount,
            'consecutive_absent' => $consecutiveAbsent,
        ]);
    }

    protected function resolveSchoolId(Request $request, $user, $explicit = null): ?int
    {
        if ($explicit) return (int)$explicit;
        $attr = $request->attributes->get('current_school_id');
        if ($attr) return (int)$attr;
        
        $principalSchoolId = $user->schoolRoles()->whereHas('role', fn($q)=>$q->whereIn('name', ['principal', 'super_admin']))->value('school_id');
        if ($principalSchoolId) return (int)$principalSchoolId;
        
        $firstActive = $user->firstTeacherSchoolId();
        if ($firstActive) return (int)$firstActive;
        $any = $user->schoolRoles()->whereHas('role', fn($q)=>$q->where('name','teacher'))->value('school_id');
        return $any ? (int)$any : null;
    }
}
