<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\LessonEvaluation;
use App\Models\School;
use Illuminate\Http\Request;

class LessonEvaluationReportController extends Controller
{
    public function index(School $school, Request $request)
    {
        $lang = $request->get('lang', 'bn');
        $query = LessonEvaluation::with(['teacher', 'class', 'section', 'subject'])
            ->forSchool($school->id)
            ->orderByDesc('evaluation_date');

        // Date Range filter
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if ($fromDate) {
            $query->whereDate('evaluation_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('evaluation_date', '<=', $toDate);
        }

        // Additional filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->get('section_id'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->get('subject_id'));
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->get('teacher_id'));
        }

        // Per-page control
        $perPage = (int) $request->get('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100, 200])) {
            $perPage = 25;
        }

        $evaluations = $query->paginate($perPage)->withQueryString();

        // Meta for filters
        $classes = \App\Models\SchoolClass::forSchool($school->id)->ordered()->get();
        $teachers = \App\Models\Teacher::forSchool($school->id)->active()->orderBy('first_name')->get();
        $subjects = \App\Models\Subject::forSchool($school->id)->orderBy('name')->get();

        // Sections only if class is selected
        $sections = collect();
        if ($request->filled('class_id')) {
            $sections = \App\Models\Section::forSchool($school->id)->where('class_id', $request->class_id)->ordered()->get();
        }

        return view('principal.lesson-evaluations.index', compact(
            'school', 'evaluations', 'fromDate', 'toDate', 'perPage', 
            'classes', 'sections', 'subjects', 'teachers', 'lang'
        ));
    }

    public function entryReport(School $school, Request $request)
    {
        $classes = \App\Models\SchoolClass::forSchool($school->id)->active()->ordered()->get();
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        
        $sections = collect();
        if ($classId) {
            $sections = \App\Models\Section::forSchool($school->id)->where('class_id', $classId)->ordered()->get();
        }

        $reportResults = $this->getEntryReportData($school, $request);
        $reportData = $reportResults['reportData'];
        $fromDate = $reportResults['fromDate'];
        $toDate = $reportResults['toDate'];

        return view('principal.lesson-evaluations.entry_report', compact('school', 'classes', 'sections', 'classId', 'sectionId', 'reportData', 'fromDate', 'toDate'));
    }

    public function entryReportPrint(School $school, Request $request)
    {
        $lang = $request->get('lang', 'bn');
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        
        $class = \App\Models\SchoolClass::find($classId);
        $section = \App\Models\Section::find($sectionId);

        $reportResults = $this->getEntryReportData($school, $request);
        $reportData = $reportResults['reportData'];
        $fromDate = $reportResults['fromDate'];
        $toDate = $reportResults['toDate'];

        // Prepare subtitle info for print layout
        $dtBn = function($d) {
            $digits = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
            return strtr(\Carbon\Carbon::parse($d)->format('d-m-Y'), $digits);
        };

        if ($lang == 'bn') {
            $printTitle = 'লেসন ইভ্যালুয়েশন এন্ট্রি রিপোর্ট';
            $cName = $class->bangla_name ?: $class->name;
            $sName = $section->bangla_name ?: $section->name;
            $fDate = $dtBn($fromDate);
            $tDate = $dtBn($toDate);
            $printSubtitle = "শ্রেণি: {$cName} | শাখা: {$sName} | তারিখ: {$fDate} - {$tDate}";
        } else {
            $printTitle = 'Lesson Evaluation Entry Report';
            $printSubtitle = "Class: {$class->name} | Section: {$section->name} | Date: " . \Carbon\Carbon::parse($fromDate)->format('d-m-Y') . " - " . \Carbon\Carbon::parse($toDate)->format('d-m-Y');
        }

        return view('principal.lesson-evaluations.entry_report_print', compact('school', 'reportData', 'fromDate', 'toDate', 'class', 'section', 'lang', 'printTitle', 'printSubtitle'));
    }

    public function routineWiseReport(School $school, Request $request)
    {
        $selectedDate = $request->get('date', \Carbon\Carbon::today()->format('Y-m-d'));
        $date = \Carbon\Carbon::parse($selectedDate);
        $dayOfWeek = strtolower($date->format('l'));

        $days = [
            'saturday'  => 'শনিবার',
            'sunday'    => 'রবিবার',
            'monday'    => 'সোমবার',
            'tuesday'   => 'মঙ্গলবার',
            'wednesday' => 'বুধবার',
            'thursday'  => 'বৃহস্পতিবার',
            'friday'    => 'শুক্রবার',
        ];
        $dayNameBn = $days[$dayOfWeek] ?? ucfirst($dayOfWeek);

        // Get all teachers who have a routine entry on that day
        $teachers = \App\Models\Teacher::forSchool($school->id)
            ->with('user:id,name')
            ->orderByRaw('COALESCE(serial_number, 999999) asc')
            ->orderBy(\App\Models\User::select('name')->whereColumn('users.id', 'teachers.user_id'))
            ->get();

        // Get all routine entries for that day
        $routineEntries = \App\Models\RoutineEntry::forSchool($school->id)
            ->where('day_of_week', $dayOfWeek)
            ->with(['subject:id,name,bangla_name', 'class:id,name,bangla_name', 'section:id,name,bangla_name'])
            ->get();

        // Determine max period number
        $maxPeriod = $routineEntries->max('period_number') ?? 0;

        // Build a lookup: teacher_id -> period_number -> routine entry
        // Key: "teacher_id#period_number"
        $routineGrid = $routineEntries->groupBy(fn($e) => $e->teacher_id . '#' . $e->period_number);

        // Get all lesson evaluations for that date (for this school)
        $evaluations = \App\Models\LessonEvaluation::forSchool($school->id)
            ->whereDate('evaluation_date', $selectedDate)
            ->get(['id', 'teacher_id', 'class_id', 'section_id', 'subject_id', 'routine_entry_id']);

        // Build a fast lookup
        // Key 1: "teacher_id#class_id#section_id#subject_id" -> evaluation id (most reliable)
        // Key 2: "routine_entry_id" -> evaluation id (if routine_entry_id is set)
        $evalLookup = [];
        foreach ($evaluations as $ev) {
            $key = $ev->teacher_id . '#' . $ev->class_id . '#' . $ev->section_id . '#' . $ev->subject_id;
            $evalLookup[$key] = $ev->id;

            if ($ev->routine_entry_id) {
                $evalLookup['re#' . $ev->routine_entry_id] = $ev->id;
            }
        }

        // Filter teachers who actually have classes on this day
        $activeTeachers = $teachers->filter(function ($t) use ($routineEntries) {
            return $routineEntries->where('teacher_id', $t->id)->isNotEmpty();
        })->values();

        return view('principal.lesson-evaluations.routine_wise_report', compact(
            'school',
            'selectedDate',
            'date',
            'dayOfWeek',
            'dayNameBn',
            'days',
            'teachers',
            'activeTeachers',
            'maxPeriod',
            'routineGrid',
            'routineEntries',
            'evaluations',
            'evalLookup'
        ));
    }

    public function routineWiseReportPrint(School $school, Request $request)
    {
        $selectedDate = $request->get('date', \Carbon\Carbon::today()->format('Y-m-d'));
        $date         = \Carbon\Carbon::parse($selectedDate);
        $dayOfWeek    = strtolower($date->format('l'));
        $lang         = $request->get('lang', 'bn');

        $days = [
            'saturday'  => 'শনিবার',
            'sunday'    => 'রবিবার',
            'monday'    => 'সোমবার',
            'tuesday'   => 'মঙ্গলবার',
            'wednesday' => 'বুধবার',
            'thursday'  => 'বৃহস্পতিবার',
            'friday'    => 'শুক্রবার',
        ];
        $dayNameBn = $days[$dayOfWeek] ?? ucfirst($dayOfWeek);

        $dtBn = function ($d) {
            $digits = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
            return strtr(\Carbon\Carbon::parse($d)->format('d-m-Y'), $digits);
        };

        if ($lang === 'bn') {
            $printTitle    = 'রুটিন ভিত্তিক ইভ্যালুয়েশন রিপোর্ট';
            $printSubtitle = $dayNameBn . ' | তারিখ: ' . $dtBn($selectedDate);
        } else {
            $printTitle    = 'Routine Wise Evaluation Report';
            $printSubtitle = ucfirst($dayOfWeek) . ' | Date: ' . $date->format('d-m-Y');
        }

        // Fetch same data as routineWiseReport
        $teachers = \App\Models\Teacher::forSchool($school->id)
            ->with('user:id,name')
            ->orderByRaw('COALESCE(serial_number, 999999) asc')
            ->orderBy(\App\Models\User::select('name')->whereColumn('users.id', 'teachers.user_id'))
            ->get();

        $routineEntries = \App\Models\RoutineEntry::forSchool($school->id)
            ->where('day_of_week', $dayOfWeek)
            ->with(['subject:id,name,bangla_name', 'class:id,name,bangla_name', 'section:id,name,bangla_name'])
            ->get();

        $maxPeriod  = $routineEntries->max('period_number') ?? 0;
        $routineGrid = $routineEntries->groupBy(fn($e) => $e->teacher_id . '#' . $e->period_number);

        $evaluations = \App\Models\LessonEvaluation::forSchool($school->id)
            ->whereDate('evaluation_date', $selectedDate)
            ->get(['id', 'teacher_id', 'class_id', 'section_id', 'subject_id', 'routine_entry_id']);

        $evalLookup = [];
        foreach ($evaluations as $ev) {
            $key = $ev->teacher_id . '#' . $ev->class_id . '#' . $ev->section_id . '#' . $ev->subject_id;
            $evalLookup[$key] = $ev->id;
            if ($ev->routine_entry_id) {
                $evalLookup['re#' . $ev->routine_entry_id] = $ev->id;
            }
        }

        $activeTeachers = $teachers->filter(fn($t) => $routineEntries->where('teacher_id', $t->id)->isNotEmpty())->values();

        return view('principal.lesson-evaluations.routine_wise_report_print', compact(
            'school', 'selectedDate', 'date', 'dayOfWeek', 'dayNameBn', 'days', 'lang',
            'activeTeachers', 'maxPeriod', 'routineGrid', 'routineEntries',
            'evaluations', 'evalLookup', 'printTitle', 'printSubtitle'
        ));
    }

    private function getEntryReportData(School $school, Request $request)
    {
        $lang = $request->get('lang', 'bn');
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        
        $fromDate = $request->get('from_date', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', \Carbon\Carbon::now()->format('Y-m-d'));
        
        $startDate = \Carbon\Carbon::parse($fromDate);
        $endDate = \Carbon\Carbon::parse($toDate);
        
        $reportData = collect();

        if ($classId && $sectionId) {
            $routineEntries = \App\Models\RoutineEntry::forSchool($school->id)
                ->forClassSection($classId, $sectionId)
                ->with(['subject', 'teacher.user'])
                ->get();

            $groupedRoutine = $routineEntries->groupBy(function ($item) {
                return $item->subject_id . '_' . $item->teacher_id;
            });

            // Get weekly holidays
            $weeklyHolidays = \App\Models\WeeklyHoliday::forSchool($school->id)->active()->pluck('day_name')->map(fn($d)=>strtolower($d))->toArray();
            
            // Pre-calculate occurrences of each day in range excluding specific holidays
            $dayCountsInRange = [
                'saturday' => 0, 'sunday' => 0, 'monday' => 0, 'tuesday' => 0, 
                'wednesday' => 0, 'thursday' => 0, 'friday' => 0
            ];
            
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $dayName = strtolower($current->format('l'));
                // Only count if not a weekly holiday
                if (!in_array($dayName, $weeklyHolidays)) {
                    $dayCountsInRange[$dayName]++;
                }
                $current->addDay();
            }

            // Subtract specific holidays
            $holidays = \App\Models\Holiday::forSchool($school->id)->active()
                ->whereBetween('date', [$startDate, $endDate])
                ->get(['date']);
                
            foreach ($holidays as $h) {
                $dayName = strtolower($h->date->format('l'));
                // If it was counted, subtract it
                if (isset($dayCountsInRange[$dayName]) && $dayCountsInRange[$dayName] > 0) {
                    // Only subtract if it's not already a weekly holiday (already excluded)
                    if (!in_array($dayName, $weeklyHolidays)) {
                        $dayCountsInRange[$dayName]--;
                    }
                }
            }

            $subjectOrders = \DB::table('class_subjects')
                ->where('class_id', $classId)
                ->pluck('order_no', 'subject_id')
                ->toArray();

            foreach ($groupedRoutine as $key => $entries) {
                $first = $entries->first();
                $subject = $first->subject;
                $teacher = $first->teacher;
                
                if (!$subject || !$teacher) continue;

                // Days this subject/teacher pair has classes
                $classDays = $entries->pluck('day_of_week')->unique()->map(fn($d) => strtolower($d))->toArray();
                
                // Calculate total classes for this pair
                $totalClasses = 0;
                foreach($classDays as $cd) {
                    if (isset($dayCountsInRange[$cd])) {
                        $totalClasses += $dayCountsInRange[$cd];
                    }
                }

                // Count entries and stats
                $evaluations = \App\Models\LessonEvaluation::forSchool($school->id)
                    ->where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('subject_id', $subject->id)
                    ->where('teacher_id', $teacher->id)
                    ->whereBetween('evaluation_date', [$startDate, $endDate])
                    ->get();

                $entriesCount = $evaluations->count();
                
                $completedS = 0;
                $partialS = 0;
                $notDoneS = 0;
                $absentS = 0;

                foreach($evaluations as $ev) {
                    $stats = $ev->getCompletionStats();
                    $completedS += $stats['completed'];
                    $partialS += $stats['partial'];
                    $notDoneS += $stats['not_done'];
                    $absentS += $stats['absent'];
                }

                $sName = ($lang == 'bn' && $subject->bangla_name) ? $subject->bangla_name : $subject->name;
                $tName = ($lang == 'bn' && $teacher->full_name_bn) ? $teacher->full_name_bn : ($teacher->full_name ?? ($teacher->user->name ?? 'N/A'));
                if ($teacher->initials) {
                    $tName .= " [{$teacher->initials}]";
                }

                $reportData->push([
                    'subject_id' => $subject->id,
                    'subject' => $sName,
                    'teacher' => $tName,
                    'total_classes' => $totalClasses,
                    'entered' => $entriesCount,
                    'missing' => max(0, $totalClasses - $entriesCount),
                    'completed_students' => $completedS,
                    'partial_students' => $partialS,
                    'not_done_students' => $notDoneS,
                    'absent_students' => $absentS,
                    'order_no' => $subjectOrders[$subject->id] ?? 999
                ]);
            }

            // Sort by order_no
            $reportData = $reportData->sortBy('order_no')->values();
        }

        return [
            'reportData' => $reportData,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }

    public function show(School $school, LessonEvaluation $lessonEvaluation)
    {
        // Load only records that belong to active students
        $lessonEvaluation->load(['teacher', 'class', 'section', 'subject', 'records' => function($q){
            $q->whereHas('student', fn($s)=>$s->where('status','active'))->with(['student' => fn($s)=>$s->where('status','active')]);
        }]);

        $stats = $lessonEvaluation->getCompletionStats();

        return view('principal.lesson-evaluations.show', compact('school', 'lessonEvaluation', 'stats'));
    }

    /**
     * JSON endpoint for mobile clients: single evaluation with records and student info
     */
    public function apiShow($id)
    {
        $ev = LessonEvaluation::with(['teacher.user','class','section','subject','records' => function($q){
                $q->whereHas('student', fn($s)=>$s->where('status','active'))->with(['student' => fn($s)=>$s->where('status','active')]);
            }])
            ->findOrFail($id);

        $data = [
            'evaluation' => [
                'id' => $ev->id,
                'evaluation_date' => $ev->evaluation_date?->format('Y-m-d'),
                'evaluation_time' => $ev->evaluation_time?->format('H:i'),
                'teacher' => [
                    'id' => $ev->teacher?->id,
                    'name' => $ev->teacher?->full_name ?? $ev->teacher?->user?->name,
                ],
                'class_name' => $ev->class?->name,
                'section_name' => $ev->section?->name,
                'subject_name' => $ev->subject?->name,
                'notes' => $ev->notes,
                'status' => $ev->status,
                'stats' => $ev->getCompletionStats(),
                'records' => $ev->records->map(function($r){
                    // Skip records without an active student
                    if (! $r->student) return null;
                    return [
                        'id' => $r->id,
                        'student_id' => $r->student_id,
                        'status' => $r->status,
                        'status_label' => $r->status_label,
                        'status_color' => $r->status_color,
                        'student' => [
                            'id' => $r->student->id,
                            'roll' => $r->student->roll,
                            'full_name' => $r->student->full_name,
                            'photo_url' => $r->student->photo_url ?? null,
                        ],
                    ];
                })->filter()->values()->toArray(),
            ],
        ];

        return response()->json($data);
    }

    /**
     * Details search endpoint: find evaluation(s) by filters and return records list
     */
    public function details(Request $request)
    {
        $q = LessonEvaluation::with(['records' => function ($q) {
                $q->whereHas('student', fn($s) => $s->where('status', 'active'))->with(['student' => fn($s) => $s->where('status', 'active')]);
            }])->orderByDesc('evaluation_date');
        if ($request->filled('class_id')) $q->where('class_id', $request->get('class_id'));
        if ($request->filled('section_id')) $q->where('section_id', $request->get('section_id'));
        if ($request->filled('subject_id')) $q->where('subject_id', $request->get('subject_id'));
        if ($request->filled('teacher_id')) $q->where('teacher_id', $request->get('teacher_id'));
        if ($request->filled('date')) $q->whereDate('evaluation_date', $request->get('date'));

        $evaluations = $q->get();
        $records = [];
        foreach ($evaluations as $ev) {
            foreach ($ev->records as $r) {
                if (! $r->student) continue;
                $records[] = [
                    'evaluation_id' => $ev->id,
                    'student_id' => $r->student_id,
                    'status' => $r->status,
                    'status_label' => $r->status_label,
                    'status_color' => $r->status_color,
                    'student' => [
                        'id' => $r->student->id,
                        'roll' => $r->student->roll,
                        'full_name' => $r->student->full_name,
                        'photo_url' => $r->student->photo_url ?? null,
                    ],
                ];
            }
        }

        return response()->json(['records' => $records]);
    }

    public function print(School $school, Request $request)
    {
        $lang = $request->get('lang', 'bn');
        $query = LessonEvaluation::with(['teacher', 'class', 'section', 'subject'])
            ->forSchool($school->id)
            ->orderByDesc('evaluation_date');

        // Date range filter
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if ($fromDate) {
            $query->whereDate('evaluation_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('evaluation_date', '<=', $toDate);
        }

        // Additional filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->get('section_id'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->get('subject_id'));
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->get('teacher_id'));
        }

        $evaluations = $query->get();

        return view('principal.lesson-evaluations.print', compact('school', 'evaluations', 'fromDate', 'toDate', 'request', 'lang'));
    }

    public function teacherReport(School $school, Request $request)
    {
        $teachers = \App\Models\Teacher::forSchool($school->id)->active()->orderBy('first_name')->get();
        $teacherId = $request->get('teacher_id');
        
        $reportResults = $this->getTeacherReportData($school, $request);
        $reportData = $reportResults['reportData'];
        $fromDate = $reportResults['fromDate'];
        $toDate = $reportResults['toDate'];

        return view('principal.lesson-evaluations.teacher_report', compact('school', 'teachers', 'teacherId', 'reportData', 'fromDate', 'toDate'));
    }

    public function teacherReportPrint(School $school, Request $request)
    {
        $lang = $request->get('lang', 'bn');
        $teacherId = $request->get('teacher_id');
        $teacher = \App\Models\Teacher::find($teacherId);

        $reportResults = $this->getTeacherReportData($school, $request);
        $reportData = $reportResults['reportData'];
        $fromDate = $reportResults['fromDate'];
        $toDate = $reportResults['toDate'];

        $dtBn = function($d) {
            $digits = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
            return strtr(\Carbon\Carbon::parse($d)->format('d-m-Y'), $digits);
        };

        if ($lang == 'bn') {
            $printTitle = 'লেসন ইভ্যালুয়েশন শিক্ষক ভিত্তিক রিপোর্ট';
            $tName = $teacher->full_name_bn ?: $teacher->full_name;
            if ($teacher->initials) {
                $tName .= " [{$teacher->initials}]";
            }
            $fDate = $dtBn($fromDate);
            $tDate = $dtBn($toDate);
            $printSubtitle = "শিক্ষকের নাম: {$tName} | তারিখ: {$fDate} - {$tDate}";
        } else {
            $printTitle = 'Lesson Evaluation Teacher Report';
            $tName = $teacher->full_name;
            if ($teacher->initials) {
                $tName .= " [{$teacher->initials}]";
            }
            $printSubtitle = "Teacher: {$tName} | Date: " . \Carbon\Carbon::parse($fromDate)->format('d-m-Y') . " - " . \Carbon\Carbon::parse($toDate)->format('d-m-Y');
        }

        return view('principal.lesson-evaluations.teacher_report_print', compact('school', 'reportData', 'fromDate', 'toDate', 'teacher', 'lang', 'printTitle', 'printSubtitle'));
    }

    private function getTeacherReportData(School $school, Request $request)
    {
        $lang = $request->get('lang', 'bn');
        $teacherId = $request->get('teacher_id');
        
        $fromDate = $request->get('from_date', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', \Carbon\Carbon::now()->format('Y-m-d'));
        
        $startDate = \Carbon\Carbon::parse($fromDate);
        $endDate = \Carbon\Carbon::parse($toDate);
        
        $reportData = collect();

        if ($teacherId) {
            $routineEntries = \App\Models\RoutineEntry::forSchool($school->id)
                ->where('teacher_id', $teacherId)
                ->with(['subject', 'class', 'section'])
                ->get();

            $groupedRoutine = $routineEntries->groupBy(function ($item) {
                return $item->class_id . '_' . $item->section_id . '_' . $item->subject_id;
            });

            $weeklyHolidays = \App\Models\WeeklyHoliday::forSchool($school->id)->active()->pluck('day_name')->map(fn($d)=>strtolower($d))->toArray();
            $dayCountsInRange = ['saturday' => 0, 'sunday' => 0, 'monday' => 0, 'tuesday' => 0, 'wednesday' => 0, 'thursday' => 0, 'friday' => 0];
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $dayName = strtolower($current->format('l'));
                if (!in_array($dayName, $weeklyHolidays)) $dayCountsInRange[$dayName]++;
                $current->addDay();
            }
            $holidays = \App\Models\Holiday::forSchool($school->id)->active()->whereBetween('date', [$startDate, $endDate])->get(['date']);
            foreach ($holidays as $h) {
                $dayName = strtolower($h->date->format('l'));
                if (isset($dayCountsInRange[$dayName]) && $dayCountsInRange[$dayName] > 0 && !in_array($dayName, $weeklyHolidays)) $dayCountsInRange[$dayName]--;
            }

            foreach ($groupedRoutine as $key => $entries) {
                $first = $entries->first();
                $subject = $first->subject;
                $class = $first->class;
                $section = $first->section;
                
                if (!$subject || !$class || !$section) continue;

                $classDays = $entries->pluck('day_of_week')->unique()->map(fn($d) => strtolower($d))->toArray();
                $totalClasses = 0;
                foreach($classDays as $cd) if (isset($dayCountsInRange[$cd])) $totalClasses += $dayCountsInRange[$cd];

                $evaluations = \App\Models\LessonEvaluation::forSchool($school->id)
                    ->where('class_id', $class->id)
                    ->where('section_id', $section->id)
                    ->where('subject_id', $subject->id)
                    ->where('teacher_id', $teacherId)
                    ->whereBetween('evaluation_date', [$startDate, $endDate])
                    ->get();

                $entriesCount = $evaluations->count();
                $completedS = 0; $partialS = 0; $notDoneS = 0; $absentS = 0;
                foreach($evaluations as $ev) {
                    $stats = $ev->getCompletionStats();
                    $completedS += $stats['completed']; $partialS += $stats['partial']; $notDoneS += $stats['not_done']; $absentS += $stats['absent'];
                }

                $sName = ($lang == 'bn' && $subject->bangla_name) ? $subject->bangla_name : $subject->name;
                $cName = ($lang == 'bn' && $class->bangla_name) ? $class->bangla_name : $class->name;
                $secName = ($lang == 'bn' && $section->bangla_name) ? $section->bangla_name : $section->name;

                $reportData->push([
                    'class_name' => $cName,
                    'section_name' => $secName,
                    'subject' => $sName,
                    'total_classes' => $totalClasses,
                    'entered' => $entriesCount,
                    'missing' => max(0, $totalClasses - $entriesCount),
                    'completed_students' => $completedS,
                    'partial_students' => $partialS,
                    'not_done_students' => $notDoneS,
                    'absent_students' => $absentS,
                ]);
            }
        }

        return [
            'reportData' => $reportData, 'fromDate' => $fromDate, 'toDate' => $toDate, 'startDate' => $startDate, 'endDate' => $endDate
        ];
    }
}
