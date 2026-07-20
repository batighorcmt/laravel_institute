<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LessonEvaluation;
use App\Models\LessonEvaluationRecord;
use App\Http\Resources\LessonEvaluationResource;
use Illuminate\Support\Carbon;
use App\Models\RoutineEntry;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class LessonEvaluationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LessonEvaluation::query();
        $schoolId = $request->attributes->get('current_school_id')
            ?? $user->primarySchool()?->id 
            ?? $user->firstTeacherSchoolId();

        if ($schoolId) {
            $query->forSchool($schoolId);
        }

        // Teacher scope
        if ($schoolId && $user->isTeacher($schoolId)) {
            $teacher = Teacher::where('user_id', $user->id)->where('school_id', $schoolId)->where('status', 'active')->first();
            if ($teacher) {
                $query->forTeacher($teacher->id);
            }
        }

        // Filters
        if ($request->filled('date')) {
            $query->where('evaluation_date', $request->get('date'));
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->get('section_id'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->get('subject_id'));
        }
        $query->orderByDesc('evaluation_date');
        $items = $query->paginate(25);
        return LessonEvaluationResource::collection($items)->additional([
            'filters' => $request->only(['date','class_id','section_id','subject_id'])
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধু শিক্ষক লেসন ইভ্যালুয়েশন তৈরি করতে পারবেন'], 403);
        }

        $validated = $request->validate([
            'routine_entry_id' => ['nullable','integer','exists:routine_entries,id'],
            'class_id' => ['required','integer'],
            'section_id' => ['nullable','integer'],
            'subject_id' => ['required','integer'],
            'evaluation_date' => ['required','date'],
            'evaluation_time' => ['nullable','date_format:H:i'],
            // "পূর্বের হোমওয়ার্ক/পাঠ্য বিষয়" — what was actually covered today.
            'notes' => ['required','string'],
            // "আগামী ক্লাসের হোমওয়ার্ক/পাঠ্য বিষয়" — becomes the homework description.
            'next_topic' => ['required','string'],
            // Per-student evaluation payload
            'student_ids' => ['required','array'],
            'student_ids.*' => ['integer','exists:students,id'],
            'statuses' => ['required','array'],
            'statuses.*' => ['required','string','in:completed,partial,not_done,absent'],
        ]);

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->where('status','active')->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }

        // Today-only enforcement
        $today = Carbon::today()->toDateString();
        if ($validated['evaluation_date'] !== $today) {
            return response()->json(['message' => 'শুধু আজকের তারিখে মূল্যায়ন রেকর্ড করা যাবে'], 422);
        }

        // Attendance check
        $attendanceExists = Attendance::where('class_id', $validated['class_id'])
            ->where('section_id', $validated['section_id'] ?? null)
            ->where('date', $validated['evaluation_date'])
            ->exists();
        
        if (!$attendanceExists) {
            return response()->json(['message' => 'এই শাখার হাজিরা গ্রহণ করা না হলে লেসন ইভ্যালুয়েশন দেওয়া যাবে না। আগে হাজিরা সম্পন্ন করুন।'], 422);
        }

        $routineId = $validated['routine_entry_id'] ?? 0;
        $lockKey = "lesson_eval_lock_{$teacher->id}_{$validated['evaluation_date']}_{$routineId}_{$validated['subject_id']}";
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            return response()->json(['message' => 'অনুরোধটি ইতিমধ্যে প্রসেস হচ্ছে, অনুগ্রহ করে অপেক্ষা করুন'], 429);
        }

        try {
            DB::beginTransaction();

            // Find existing evaluation for this teacher/date/routine
            $evaluation = LessonEvaluation::forSchool($schoolId)
                ->forTeacher($teacher->id)
                ->forDate($validated['evaluation_date'])
                ->when(isset($validated['routine_entry_id']), function($q) use ($validated) {
                    $q->where('routine_entry_id', $validated['routine_entry_id']);
                })
                ->first();

            $previousStatuses = [];
            if ($evaluation) {
                $previousStatuses = $evaluation->records->pluck('status', 'student_id')->toArray();
                $evaluation->update([
                    'evaluation_time' => $validated['evaluation_time'] ? Carbon::parse($validated['evaluation_time']) : now(),
                    'notes' => $validated['notes'] ?? null,
                    'class_id' => $validated['class_id'],
                    'section_id' => $validated['section_id'] ?? null,
                    'subject_id' => $validated['subject_id'],
                    'status' => 'completed',
                ]);
                // Replace existing records
                $evaluation->records()->delete();
            } else {
                $evaluation = LessonEvaluation::create([
                    'school_id' => $schoolId,
                    'teacher_id' => $teacher->id,
                    'class_id' => $validated['class_id'],
                    'section_id' => $validated['section_id'] ?? null,
                    'subject_id' => $validated['subject_id'],
                    'routine_entry_id' => $validated['routine_entry_id'] ?? null,
                    'evaluation_date' => $validated['evaluation_date'],
                    'evaluation_time' => $validated['evaluation_time'] ? Carbon::parse($validated['evaluation_time']) : now(),
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'completed',
                ]);
            }

            // Safety net: a student marked 'absent' in class attendance for this
            // date/class/section can only ever be recorded as 'absent' here,
            // regardless of what the client submitted for that row.
            $attendanceAbsentIds = Attendance::where('date', $validated['evaluation_date'])
                ->where('class_id', $validated['class_id'])
                ->where('section_id', $validated['section_id'] ?? null)
                ->where('status', 'absent')
                ->pluck('student_id')
                ->flip();

            $evaluationRecords = [];
            foreach ($validated['student_ids'] as $i => $studentId) {
                $studentId = (int) $studentId;
                $status = $attendanceAbsentIds->has($studentId)
                    ? 'absent'
                    : ($validated['statuses'][$i] ?? 'not_done');
                $evaluationRecords[] = LessonEvaluationRecord::create([
                    'lesson_evaluation_id' => $evaluation->id,
                    'student_id' => $studentId,
                    'status' => $status,
                ]);
            }

            // Create/update the paired homework entry (next class's homework/topic)
            // from the same submission, instead of a separate homework form.
            $homework = null;
            $homeworkIsNew = false;
            $homeworkChanged = false;
            if (! empty($validated['section_id'])) {
                $homework = \App\Models\Homework::where('teacher_id', $teacher->id)
                    ->where('class_id', $validated['class_id'])
                    ->where('section_id', $validated['section_id'])
                    ->where('subject_id', $validated['subject_id'])
                    ->whereDate('homework_date', $validated['evaluation_date'])
                    ->first();

                $subjectName = \App\Models\Subject::find($validated['subject_id'])?->name ?? '';
                $title = trim(($subjectName !== '' ? $subjectName.' - ' : '').'হোমওয়ার্ক');

                if ($homework) {
                    $homeworkChanged = $homework->description !== $validated['next_topic'];
                    $homework->update([
                        'title' => $title,
                        'description' => $validated['next_topic'],
                    ]);
                } else {
                    $homeworkIsNew = true;
                    $homework = \App\Models\Homework::create([
                        'school_id' => $schoolId,
                        'class_id' => $validated['class_id'],
                        'section_id' => $validated['section_id'],
                        'subject_id' => $validated['subject_id'],
                        'teacher_id' => $teacher->id,
                        'homework_date' => $validated['evaluation_date'],
                        'submission_date' => Carbon::parse($validated['evaluation_date'])->addDay(),
                        'title' => $title,
                        'description' => $validated['next_topic'],
                    ]);
                }
            }

            DB::commit();

            // Send SMS asynchronously
            try {
                $smsService = app(\App\Services\LessonEvaluationSmsService::class);
                $smsService->sendEvaluationSms($evaluation, $evaluationRecords, $user->id, $previousStatuses);
            } catch (\Throwable $smsEx) {
                \Log::error("Lesson Evaluation SMS Error: " . $smsEx->getMessage());
            }

            // Notify all enrolled students of the class/section about the homework,
            // but only when it's brand new or its content actually changed.
            if ($homework && ($homeworkIsNew || $homeworkChanged)) {
                try {
                    $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');
                    $studentIds = StudentEnrollment::where('school_id', $schoolId)
                        ->where('class_id', $validated['class_id'])
                        ->where('section_id', $validated['section_id'])
                        ->where('status', 'active')
                        ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
                        ->whereHas('student', fn($q) => $q->where('status', 'active'))
                        ->pluck('student_id');

                    app(\App\Services\PushNotificationService::class)
                        ->sendHomeworkNotification($homework, $studentIds, ! $homeworkIsNew);
                } catch (\Throwable $pushEx) {
                    \Log::error('Homework Push Notification Error: '.$pushEx->getMessage());
                }
            }

            return response()->json([
                'message' => 'লেসন ইভ্যালুয়েশন ও হোমওয়ার্ক সংরক্ষণ হয়েছে',
                'evaluation_id' => $evaluation->id,
                'homework_id' => $homework?->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'সংরক্ষণে ত্রুটি: '.$e->getMessage()], 422);
        } finally {
            $lock->release();
        }
    }

    // A routine entry can exist for a weekday/date that is actually a
    // holiday (weekly off-day or a specifically listed Holiday) — no class
    // really happens then, so evaluation isn't possible. Returns
    // [isHoliday, label] so callers can exclude that date from lists/stats.
    // Note: WeeklyHoliday.day_number is ISO-8601 (1=Monday..7=Sunday, see
    // Principal\HolidayController::index), which matches Carbon's
    // dayOfWeekIso — NOT dayOfWeek (0=Sunday..6=Saturday).
    private function holidayInfo(int $schoolId, Carbon $date): array
    {
        $isWeeklyOff = \App\Models\WeeklyHoliday::forSchool($schoolId)
            ->active()
            ->where('day_number', $date->dayOfWeekIso)
            ->exists();
        if ($isWeeklyOff) {
            return [true, 'সাপ্তাহিক ছুটি'];
        }

        $holiday = \App\Models\Holiday::forSchool($schoolId)
            ->active()
            ->whereDate('date', $date->toDateString())
            ->first();
        if ($holiday) {
            return [true, $holiday->title];
        }

        return [false, null];
    }

    // Returns today's routine entries for the logged-in teacher with evaluated flags
    public function todayRoutine(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id;
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক'], 403);
        }

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->where('status','active')->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }
        $teacherId = $teacher->id;
        $today = Carbon::today();
        $dayName = strtolower($today->format('l'));

        // A routine can list periods for today's weekday, but if today is
        // actually a holiday no class really happens — don't offer them for
        // evaluation at all.
        [$isHoliday, $holidayLabel] = $this->holidayInfo($schoolId, $today);
        if ($isHoliday) {
            return response()->json([
                'date' => $today->toDateString(),
                'items' => [],
                'is_holiday' => true,
                'holiday_label' => $holidayLabel,
            ]);
        }

        $entries = RoutineEntry::with(['class','section','subject'])
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayName)
            ->orderBy('period_number')
            ->get();

        $evaluated = LessonEvaluation::forSchool($schoolId)
            ->forTeacher($teacherId)
            ->forDate($today)
            ->pluck('routine_entry_id')
            ->toArray();

        $data = $entries->map(function($e) use ($evaluated) {
            return [
                'routine_entry_id' => $e->id,
                'period_number' => $e->period_number,
                'class_name' => $e->class?->name,
                'section_name' => $e->section?->name,
                'subject_name' => $e->subject?->name,
                'evaluated' => in_array($e->id, $evaluated),
                'class_id' => $e->class_id,
                'section_id' => $e->section_id,
                'subject_id' => $e->subject_id,
            ];
        })->values();

        return response()->json([
            'date' => $today->toDateString(),
            'items' => $data,
        ]);
    }

    // Returns routine entries + evaluated flag for a past (non-future) date,
    // for the "বিগত ক্লাস সমূহ" segment of the lesson evaluation list. Defaults
    // to yesterday when no date is given.
    public function routineForDate(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id;
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক'], 403);
        }

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->where('status','active')->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }
        $teacherId = $teacher->id;
        $today = Carbon::today();
        $dateParam = $request->query('date');
        $date = $dateParam ? Carbon::parse($dateParam)->startOfDay() : $today->copy()->subDay();
        if ($date->greaterThan($today)) {
            return response()->json(['message' => 'ভবিষ্যৎ তারিখ নির্বাচন করা যাবে না'], 422);
        }

        [$isHoliday, $holidayLabel] = $this->holidayInfo($schoolId, $date);
        if ($isHoliday) {
            return response()->json([
                'date' => $date->toDateString(),
                'day_of_week' => strtolower($date->format('l')),
                'items' => [],
                'is_holiday' => true,
                'holiday_label' => $holidayLabel,
            ]);
        }

        $dayName = strtolower($date->format('l'));

        $entries = RoutineEntry::with(['class','section','subject'])
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayName)
            ->orderBy('period_number')
            ->get();

        // Match evaluations the same way form() does: exact routine_entry_id
        // first, falling back to class/section/subject for that date — a
        // class/section/subject can sit under a different routine_entry_id
        // on different weekdays.
        $dayEvaluations = LessonEvaluation::forSchool($schoolId)
            ->forTeacher($teacherId)
            ->forDate($date->toDateString())
            ->get();
        $byRoutineEntry = $dayEvaluations->whereNotNull('routine_entry_id')->keyBy('routine_entry_id');
        $byCompositeKey = $dayEvaluations->keyBy(
            fn($ev) => $ev->class_id.':'.$ev->section_id.':'.$ev->subject_id
        );

        $data = $entries->map(function($e) use ($byRoutineEntry, $byCompositeKey) {
            $evaluation = $byRoutineEntry->get($e->id)
                ?? $byCompositeKey->get($e->class_id.':'.$e->section_id.':'.$e->subject_id);
            return [
                'routine_entry_id' => $e->id,
                'period_number' => $e->period_number,
                'class_name' => $e->class?->name,
                'section_name' => $e->section?->name,
                'subject_name' => $e->subject?->name,
                'evaluated' => $evaluation !== null,
                'evaluation_id' => $evaluation?->id,
                'class_id' => $e->class_id,
                'section_id' => $e->section_id,
                'subject_id' => $e->subject_id,
            ];
        })->values();

        return response()->json([
            'date' => $date->toDateString(),
            'day_of_week' => $dayName,
            'items' => $data,
        ]);
    }

    // Daily + monthly lesson-evaluation completion stats for the logged-in
    // teacher, used by the "লেসন ইভ্যালুয়েশন এন্ট্রি রিপোর্ট" screen.
    public function report(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id;
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক'], 403);
        }
        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->where('status','active')->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }
        $teacherId = $teacher->id;
        $today = Carbon::today();

        // Weekly schedule pattern: number of periods this teacher has on
        // each weekday, keyed by lowercase day name (e.g. 'monday').
        $expectedByDay = RoutineEntry::where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->select('day_of_week', DB::raw('count(*) as cnt'))
            ->groupBy('day_of_week')
            ->pluck('cnt', 'day_of_week');

        $year = (int) $request->query('year', $today->year);
        $month = (int) $request->query('month', $today->month);
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd = $start->copy()->endOfMonth();
        $end = $monthEnd->greaterThan($today) ? $today->copy() : $monthEnd;

        $doneByDate = DB::table('lesson_evaluations')
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->whereBetween('evaluation_date', [$start->toDateString(), $monthEnd->toDateString()])
            ->select('evaluation_date', DB::raw('count(*) as cnt'))
            ->groupBy('evaluation_date')
            ->pluck('cnt', 'evaluation_date');

        // Weekly off-days (recurring) and specifically listed holidays in
        // this range — a routine period landing on either is excluded from
        // every stage of this report, since no class actually happens then
        // (see holidayInfo() above for the day_number/dayOfWeekIso convention).
        $isoDayNames = [1=>'monday',2=>'tuesday',3=>'wednesday',4=>'thursday',5=>'friday',6=>'saturday',7=>'sunday'];
        $weeklyOffDayNames = \App\Models\WeeklyHoliday::forSchool($schoolId)->active()
            ->pluck('day_number')
            ->map(fn ($n) => $isoDayNames[(int) $n] ?? null)
            ->filter()
            ->all();
        $holidayDates = \App\Models\Holiday::forSchool($schoolId)->active()
            ->whereBetween('date', [$start->toDateString(), $monthEnd->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        $dayStats = function (Carbon $date) use ($expectedByDay, $doneByDate, $weeklyOffDayNames, $holidayDates) {
            $dayName = strtolower($date->format('l'));
            $dateStr = $date->toDateString();
            $isHoliday = in_array($dayName, $weeklyOffDayNames, true)
                || in_array($dateStr, $holidayDates, true);
            $expected = $isHoliday ? 0 : (int) ($expectedByDay[$dayName] ?? 0);
            $done = $isHoliday ? 0 : (int) ($doneByDate[$dateStr] ?? 0);
            return [
                'date' => $dateStr,
                'day_of_week' => $dayName,
                'expected' => $expected,
                'done' => $done,
                'not_done' => max(0, $expected - $done),
            ];
        };

        $days = [];
        $expectedTotal = 0;
        $doneTotal = 0;
        if ($end->greaterThanOrEqualTo($start)) {
            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $row = $dayStats($cursor->copy());
                $days[] = $row;
                $expectedTotal += $row['expected'];
                $doneTotal += $row['done'];
            }
        }

        $todayStr = $today->toDateString();
        $todayRow = collect($days)->firstWhere('date', $todayStr) ?? $dayStats($today);

        return response()->json([
            'year' => $year,
            'month' => $month,
            'today' => $todayRow,
            'monthly' => [
                'expected_total' => $expectedTotal,
                'done_total' => $doneTotal,
                'not_done_total' => max(0, $expectedTotal - $doneTotal),
                'completion_rate' => $expectedTotal > 0 ? round(($doneTotal / $expectedTotal) * 100, 1) : 0,
            ],
            'days' => $days,
        ]);
    }

    // Returns student list and existing statuses for a routine entry for today
    public function form(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক'], 403);
        }
        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->where('status','active')->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }
        $teacherId = $teacher->id;
        $routineEntryId = (int)$request->query('routine_entry_id');
        $dateParam = $request->query('date');
        $today = Carbon::today();
        $date = $dateParam ? Carbon::parse($dateParam)->startOfDay() : $today;
        if ($date->greaterThan($today)) {
            return response()->json(['message' => 'ভবিষ্যৎ তারিখ নির্বাচন করা যাবে না'], 422);
        }
        $isToday = $date->isSameDay($today);
        if (! $routineEntryId) return response()->json(['message' => 'রুটিন নির্বাচন করুন'], 422);

        $entry = RoutineEntry::with(['class','section','subject'])
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('id', $routineEntryId)
            ->first();

        if (!$entry) {
            return response()->json(['message' => 'রুটিন এন্ট্রি পাওয়া যায়নি বা আপনার জন্য অনুমোদিত নয়'], 404);
        }

        // Attendance check
        $attendanceExists = Attendance::where('class_id', $entry->class_id)
            ->where('section_id', $entry->section_id)
            ->where('date', $date->toDateString())
            ->exists();
        
        if (!$attendanceExists) {
            return response()->json([
                'date' => $date->toDateString(),
                'routine_entry' => [
                    'id' => $entry->id,
                    'class_id' => $entry->class_id,
                    'section_id' => $entry->section_id,
                    'subject_id' => $entry->subject_id,
                    'class_name' => $entry->class?->name,
                    'section_name' => $entry->section?->name,
                    'subject_name' => $entry->subject?->name,
                    'period_number' => $entry->period_number,
                ],
                'students' => [],
                'allowed_statuses' => ['completed','partial','not_done','absent'],
                'stats' => ['total' => 0, 'completed' => 0, 'partial' => 0, 'not_done' => 0, 'absent' => 0],
                'message' => 'এই শাখার হাজিরা গ্রহণ করা না হলে লেসন ইভ্যালুয়েশন দেওয়া যাবে না। আগে হাজিরা সম্পন্ন করুন।',
                'attendance_missing' => true,
                'read_only' => ! $isToday,
            ], 200);
        }

        // A class/section/subject can be taught in different routine slots on
        // different weekdays (e.g. Saturday period 2 vs Monday period 1), so an
        // evaluation saved under one weekday's routine_entry_id won't match when
        // the same class is viewed via another weekday's entry id. Try the exact
        // routine entry first (disambiguates same-day multi-period cases), then
        // fall back to matching by class/section/subject for that date.
        $evaluation = LessonEvaluation::forSchool($schoolId)
            ->forTeacher($teacherId)
            ->where('evaluation_date', $date->toDateString())
            ->where('routine_entry_id', $entry->id)
            ->with('records')
            ->first();

        if (! $evaluation) {
            $evaluation = LessonEvaluation::forSchool($schoolId)
                ->forTeacher($teacherId)
                ->where('evaluation_date', $date->toDateString())
                ->where('class_id', $entry->class_id)
                ->where('section_id', $entry->section_id)
                ->where('subject_id', $entry->subject_id)
                ->with('records')
                ->first();
        }
        $existing = $evaluation ? $evaluation->records->pluck('status','student_id') : collect();

        // Build enrollment query (optionally filter by subject assignments when exists)
        // Only include enrollments whose student record is active.
        // Students who were marked 'absent' in class attendance for this date are
        // still included (so the teacher can see the full roll), but their row is
        // locked to the 'absent' lesson-evaluation status and labeled accordingly.
        $academicYearId = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id');
        $attendanceAbsentIds = Attendance::where('date', $date->toDateString())
            ->where('class_id', $entry->class_id)
            ->where('section_id', $entry->section_id)
            ->where('status', 'absent')
            ->pluck('student_id')
            ->flip();

        $query = StudentEnrollment::with(['student' => fn($q)=>$q->where('status','active')])
            ->where('school_id', $schoolId)
            ->where('class_id', $entry->class_id)
            ->where('section_id', $entry->section_id)
            ->where('status', 'active')
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->whereHas('student', fn($q)=>$q->where('status','active'));

        $hasSubjectAssignments = DB::table('student_subjects')
            ->whereIn('student_enrollment_id', function($q) use ($schoolId, $entry) {
                $q->select('id')->from('student_enrollments')
                    ->where('school_id', $schoolId)
                    ->where('class_id', $entry->class_id)
                    ->where('section_id', $entry->section_id);
            })->exists();
        if ($hasSubjectAssignments) {
            $query->whereHas('subjects', function($q) use ($entry) {
                $q->where('subject_id', $entry->subject_id)->where('status','active');
            });
        }
        $students = $query->orderBy('roll_no')->get()->map(function($en) use ($existing, $attendanceAbsentIds) {
            $st = $en->student;
            $isAttendanceAbsent = $st && $attendanceAbsentIds->has($st->id);
            return [
                'id' => $st?->id,
                'name' => $st?->full_name,
                'roll' => $en->roll_no,
                'photo_url' => $st?->photo_url,
                'status' => $isAttendanceAbsent ? 'absent' : ($existing[$st?->id] ?? null),
                'attendance_absent' => $isAttendanceAbsent,
            ];
        })->values();

        // Stats from DB (evaluation records) only
        $stats = [
            'total' => $evaluation ? $evaluation->records->count() : 0,
            'completed' => $evaluation ? $evaluation->records->where('status','completed')->count() : 0,
            'partial' => $evaluation ? $evaluation->records->where('status','partial')->count() : 0,
            'not_done' => $evaluation ? $evaluation->records->where('status','not_done')->count() : 0,
            'absent' => $evaluation ? $evaluation->records->where('status','absent')->count() : 0,
        ];

        // Existing homework paired with this class/section/subject/date, if any
        // (so the client can pre-fill the "next class topic" field when editing).
        $existingHomework = \App\Models\Homework::where('teacher_id', $teacherId)
            ->where('class_id', $entry->class_id)
            ->where('section_id', $entry->section_id)
            ->where('subject_id', $entry->subject_id)
            ->whereDate('homework_date', $date->toDateString())
            ->first();

        // For a brand-new entry (no evaluation saved yet for this date), default
        // the "পূর্বের হোমওয়ার্ক/পাঠ্য বিষয়" field to the homework/topic that was
        // assigned in this class/section/subject's most recent prior session, so
        // the teacher can see/confirm what was supposed to be covered today.
        $notesValue = $evaluation?->notes;
        if (! $evaluation && $isToday) {
            $lastHomework = \App\Models\Homework::where('teacher_id', $teacherId)
                ->where('class_id', $entry->class_id)
                ->where('section_id', $entry->section_id)
                ->where('subject_id', $entry->subject_id)
                ->whereDate('homework_date', '<', $date->toDateString())
                ->orderByDesc('homework_date')
                ->first();
            $notesValue = $lastHomework?->description;
        }

        // Attendance was taken but this specific date simply has no lesson
        // evaluation submitted — distinct from the "attendance missing"
        // early-return above, so the teacher isn't told to go take attendance
        // when the real issue is that nobody filled the evaluation in.
        $noEvaluation = ! $isToday && ! $evaluation;

        return response()->json([
            'date' => $date->toDateString(),
            'routine_entry' => [
                'id' => $entry->id,
                'class_id' => $entry->class_id,
                'section_id' => $entry->section_id,
                'subject_id' => $entry->subject_id,
                'class_name' => $entry->class?->name,
                'section_name' => $entry->section?->name,
                'subject_name' => $entry->subject?->name,
                'period_number' => $entry->period_number,
            ],
            'students' => $students,
            'allowed_statuses' => ['completed','partial','not_done','absent'],
            'stats' => $stats,
            'notes' => $notesValue,
            'next_topic' => $existingHomework?->description,
            'read_only' => ! $isToday,
            'no_evaluation' => $noEvaluation,
            'message' => $noEvaluation ? 'এই দিনে লেসন ইভ্যালুয়েশন এন্ট্রি করা হয়নি।' : null,
        ]);
    }

    // A single student's lesson-evaluation history for one subject, scoped to
    // the current academic year. Used by the student detail page reached by
    // tapping a name in a completed (read-only) evaluation. Paginated so the
    // client can infinite-scroll further back in time.
    public function studentHistory(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id;
        if (! $schoolId) {
            $schoolId = $user->firstTeacherSchoolId();
        }
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক'], 403);
        }
        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->where('status','active')->first();
        if (! $teacher) {
            return response()->json(['message' => 'শিক্ষক প্রোফাইল পাওয়া যায়নি'], 422);
        }

        $validated = $request->validate([
            'student_id' => ['required','integer','exists:students,id'],
            'class_id' => ['required','integer'],
            'section_id' => ['required','integer'],
            'subject_id' => ['required','integer'],
            'page' => ['nullable','integer','min:1'],
            'per_page' => ['nullable','integer','min:1','max:50'],
        ]);
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 10);

        $student = \App\Models\Student::forSchool($schoolId)->find($validated['student_id']);
        if (! $student) {
            return response()->json(['message' => 'শিক্ষার্থী পাওয়া যায়নি'], 404);
        }

        $academicYear = \App\Models\AcademicYear::where('school_id', $schoolId)->where('is_current', true)->first();

        $enrollment = StudentEnrollment::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('class_id', $validated['class_id'])
            ->where('section_id', $validated['section_id'])
            ->when($academicYear, fn($q) => $q->where('academic_year_id', $academicYear->id))
            ->first();

        $subject = \App\Models\Subject::find($validated['subject_id']);
        $class = \App\Models\SchoolClass::find($validated['class_id']);
        $section = \App\Models\Section::find($validated['section_id']);

        $scopeEvaluations = function ($q) use ($schoolId, $teacher, $validated, $academicYear) {
            $q->where('school_id', $schoolId)
                ->where('teacher_id', $teacher->id)
                ->where('class_id', $validated['class_id'])
                ->where('section_id', $validated['section_id'])
                ->where('subject_id', $validated['subject_id']);
            if ($academicYear) {
                $q->whereBetween('evaluation_date', [
                    $academicYear->start_date->toDateString(),
                    $academicYear->end_date->toDateString(),
                ]);
            }
        };

        $summaryCounts = LessonEvaluationRecord::where('student_id', $student->id)
            ->whereHas('lessonEvaluation', $scopeEvaluations)
            ->select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $summary = [
            'total' => (int) $summaryCounts->sum(),
            'completed' => (int) ($summaryCounts['completed'] ?? 0),
            'partial' => (int) ($summaryCounts['partial'] ?? 0),
            'not_done' => (int) ($summaryCounts['not_done'] ?? 0),
            'absent' => (int) ($summaryCounts['absent'] ?? 0),
        ];

        $evaluationsQuery = LessonEvaluation::where($scopeEvaluations)
            ->whereHas('records', fn($q) => $q->where('student_id', $student->id))
            ->with(['records' => fn($q) => $q->where('student_id', $student->id)])
            ->orderByDesc('evaluation_date');

        $total = (clone $evaluationsQuery)->count();
        $evaluations = $evaluationsQuery->forPage($page, $perPage)->get();

        $entries = $evaluations->map(function ($ev) {
            $record = $ev->records->first();
            return [
                'date' => $ev->evaluation_date?->toDateString(),
                'status' => $record?->status,
                'notes' => $ev->notes,
            ];
        })->values();

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'roll' => $enrollment?->roll_no,
                'photo_url' => $student->photo_url,
                'guardian_phone' => $student->guardian_phone,
            ],
            'class_name' => $class?->name,
            'section_name' => $section?->name,
            'subject_name' => $subject?->name,
            'academic_year' => $academicYear ? ['id' => $academicYear->id, 'name' => $academicYear->name] : null,
            'summary' => $summary,
            'entries' => $entries,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }
}
