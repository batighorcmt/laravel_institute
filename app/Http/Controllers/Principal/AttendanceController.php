<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * মাসিক রিপোর্ট দেখানোর জন্য
     */
    public function monthlyReport(School $school, Request $request)
    {
        // Extract filters early so they are definitely defined
        $month = $request->query('month', now()->format('Y-m'));
        $classId = $request->query('class_id'); // may be null
        $sectionId = $request->query('section_id'); // may be null
    $print = $request->boolean('print', false);
    $requiresSelection = empty($classId) || empty($sectionId);

        // Parse month into numeric parts
        [$yearNum,$monthNum] = explode('-', $month) + [date('Y'), date('m')];
        $yearNum = (int)$yearNum; $monthNum = (int)$monthNum;
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearVal = $currentYear?->id;

        // মাসের প্রথম ও শেষ দিন
        $startDate = $month.'-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // সকল ক্লাস ও সেকশন
        $classes = SchoolClass::forSchool($school->id)->active()->orderBy('numeric_value')->get();
        $sections = Section::forSchool($school->id)
            ->where('status','active')
            ->when($classId, fn($q)=>$q->where('class_id', $classId))
            ->get();

        // If class/section not selected, return early with minimal data and a flag
        if ($requiresSelection) {
            // Base lists for the form
            $classes = SchoolClass::forSchool($school->id)->active()->orderBy('numeric_value')->get();
            $sections = Section::forSchool($school->id)
                ->where('status','active')
                ->when($classId, fn($q)=>$q->where('class_id', $classId))
                ->get();

            // Build month dates to support header context if needed (could be empty too)
            [$yearNum,$monthNum] = explode('-', $month) + [date('Y'), date('m')];
            $yearNum = (int)$yearNum; $monthNum = (int)$monthNum;
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNum, $yearNum);
            $dates = [];
            for($d=1;$d<=$daysInMonth;$d++){ $dates[] = sprintf('%04d-%02d-%02d',$yearNum,$monthNum,$d); }

            return view('principal.attendance.monthly_report', [
                'school'=>$school,
                'month'=>$month,
                'classes'=>$classes,
                'sections'=>$sections,
                'students'=>collect(),
                'dates'=>[],
                'attendanceMatrix'=>[],
                'holidayDates'=>[],
                'weeklyHolidayNums'=>[],
                'print'=>false,
                'requiresSelection'=>true,
            ]);
        }

        // Determine roster (enrollments) filtered by class/section
        $enrollQuery = StudentEnrollment::select(
                'student_enrollments.student_id','student_enrollments.class_id','student_enrollments.section_id',
                'student_enrollments.roll_no','students.student_name_bn','students.student_name_en','students.gender',
                'classes.name as class_name','sections.name as section_name','classes.numeric_value'
            )
            ->join('students','students.id','=','student_enrollments.student_id')
            ->join('classes','classes.id','=','student_enrollments.class_id')
            ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
            ->where('student_enrollments.school_id',$school->id)
            ->where('student_enrollments.status','active')
            ->when($yearVal, fn($q)=>$q->where('student_enrollments.academic_year_id',$yearVal))
            ->when($classId, fn($q)=>$q->where('student_enrollments.class_id',$classId))
            ->when($sectionId, fn($q)=>$q->where('student_enrollments.section_id',$sectionId));

        $students = $enrollQuery->orderBy('classes.numeric_value')->orderBy('student_enrollments.roll_no')->get();

        // Map student IDs for attendance fetch
        $studentIds = $students->pluck('student_id')->all();
        $attendanceMatrix = [];
        if (!empty($studentIds)) {
            $attRows = Attendance::select('student_id','date','status')
                ->whereIn('student_id',$studentIds)
                ->whereBetween('date', [$startDate,$endDate])
                ->get();
            foreach ($attRows as $r) {
                $dateKey = is_string($r->date) ? $r->date : (\Carbon\Carbon::parse($r->date)->toDateString());
                $attendanceMatrix[$r->student_id][$dateKey] = $r->status;
            }
        }

        // Weekly holidays for this school (1=Mon ... 7=Sun)
        $weeklyHolidayNums = \App\Models\WeeklyHoliday::where('school_id',$school->id)
            ->where('status','active')->pluck('day_number')->map(fn($n)=>(int)$n)->all();

        // Single-day holidays for selected month
        $holidayDates = \App\Models\Holiday::where('school_id',$school->id)
            ->where('status','active')
            ->whereBetween('date', [$startDate,$endDate])
            ->pluck('date')->map(fn($d)=>\Carbon\Carbon::parse($d)->toDateString())->all();

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNum, $yearNum);
        $dates = [];
        for($d=1;$d<=$daysInMonth;$d++){ $dates[] = sprintf('%04d-%02d-%02d',$yearNum,$monthNum,$d); }

        return view('principal.attendance.monthly_report', [
            'school'=>$school,
            'month'=>$month,
            'classes'=>$classes,
            'sections'=>$sections,
            'students'=>$students,
            'dates'=>$dates,
            'attendanceMatrix'=>$attendanceMatrix,
            'holidayDates'=>$holidayDates,
            'weeklyHolidayNums'=>$weeklyHolidayNums,
            'print'=>$print,
            'requiresSelection'=>false,
        ]);
    }
    /**
     * Attendance dashboard overview for a school (date filter, aggregates, charts, absent list)
     */
    public function dashboard(School $school, Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        // Current academic year (use ID foreign key)
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearVal = $currentYear?->id;

        // Total active students (current year)
        $totalStudents = StudentEnrollment::where('school_id', $school->id)
            ->where('status', 'active')
            ->when($yearVal, fn($q)=>$q->where('academic_year_id', $yearVal))
            ->count();

        // Today's attendance counts
        // Assumption: 'present' and 'late' are considered as present for overall percentage
        $presentToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->where('attendance.date', $date)
            ->where('students.school_id', $school->id)
            ->whereIn('attendance.status', ['present','late'])
            ->count();
        $absentToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->where('attendance.date', $date)
            ->where('students.school_id', $school->id)
            ->where('attendance.status', 'absent')
            ->count();
        // Attendance percent: show null when there are no attendance entries today for this school
        $anyAttendanceToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->where('attendance.date', $date)
            ->where('students.school_id', $school->id)
            ->exists();
        $attendancePercent = ($totalStudents > 0 && $anyAttendanceToday)
            ? round(($presentToday / $totalStudents) * 100, 1)
            : null;

        // Build class-section breakdown (totals & present per section), plus class totals and grand totals
        // Section totals including gender splits (total male/female per section) for existing enrollments
        $sectionTotals = StudentEnrollment::select(
                'classes.id as class_id','classes.name as class_name','classes.numeric_value',
                'sections.id as section_id','sections.name as section_name',
                DB::raw('COUNT(DISTINCT student_enrollments.student_id) as total'),
                DB::raw("SUM(CASE WHEN students.gender='male' THEN 1 ELSE 0 END) as total_male"),
                DB::raw("SUM(CASE WHEN students.gender='female' THEN 1 ELSE 0 END) as total_female")
            )
            ->join('classes','student_enrollments.class_id','=','classes.id')
            ->join('sections','student_enrollments.section_id','=','sections.id')
            ->join('students','students.id','=','student_enrollments.student_id')
            ->where('student_enrollments.school_id', $school->id)
            ->where('student_enrollments.status','active')
            ->where('sections.status','active')
            ->when($yearVal, fn($q)=>$q->where('student_enrollments.academic_year_id', $yearVal))
            ->groupBy('classes.id','classes.name','classes.numeric_value','sections.id','sections.name')
            ->get();

        // Ensure ALL active classes and their active sections appear even if zero enrollment
        $allClasses = SchoolClass::forSchool($school->id)->active()->get(['id','name','numeric_value']);
        $existingKeys = $sectionTotals->map(fn($r)=>"{$r->class_id}|{$r->section_id}")->all();
        foreach ($allClasses as $cls) {
            $classHasAny = false;
            $classSections = Section::forSchool($school->id)
                ->where('class_id', $cls->id)
                ->where('status','active')
                ->get(['id','name']);
            if ($classSections->isEmpty()) {
                // Create a placeholder section entry so the class appears at least once
                $sectionTotals->push((object) [
                    'class_id' => $cls->id,
                    'class_name' => $cls->name,
                    'numeric_value' => $cls->numeric_value,
                    'section_id' => 0,
                    'section_name' => '—',
                    'total' => 0,
                    'total_male' => 0,
                    'total_female' => 0,
                ]);
                continue;
            }
            foreach ($classSections as $sec) {
                $key = $cls->id . '|' . $sec->id;
                if (!in_array($key, $existingKeys, true)) {
                    // Synthetic zero row for this real section
                    $sectionTotals->push((object) [
                        'class_id' => $cls->id,
                        'class_name' => $cls->name,
                        'numeric_value' => $cls->numeric_value,
                        'section_id' => $sec->id,
                        'section_name' => $sec->name,
                        'total' => 0,
                        'total_male' => 0,
                        'total_female' => 0,
                    ]);
                }
            }
        }
        // Re-order after injecting synthetic rows (by class numeric then section name)
        $sectionTotals = $sectionTotals->sortBy(function($r){
            return sprintf('%05d|%s', (int)$r->numeric_value, (string)$r->section_name);
        })->values();

        // Present male/female and absent male/female per section
        $attendanceGender = Attendance::select(
                'attendance.class_id','attendance.section_id',
                DB::raw("SUM(CASE WHEN students.gender='male' AND attendance.status IN ('present','late') THEN 1 ELSE 0 END) as present_male"),
                DB::raw("SUM(CASE WHEN students.gender='female' AND attendance.status IN ('present','late') THEN 1 ELSE 0 END) as present_female"),
                DB::raw("SUM(CASE WHEN students.gender='male' AND attendance.status='absent' THEN 1 ELSE 0 END) as absent_male"),
                DB::raw("SUM(CASE WHEN students.gender='female' AND attendance.status='absent' THEN 1 ELSE 0 END) as absent_female"),
                DB::raw("COUNT(DISTINCT CASE WHEN attendance.status IN ('present','late') THEN attendance.student_id END) as present_total"),
                DB::raw("COUNT(DISTINCT CASE WHEN attendance.status='absent' THEN attendance.student_id END) as absent_total")
            )
            ->join('students','students.id','=','attendance.student_id')
            ->where('attendance.date',$date)
            ->groupBy('attendance.class_id','attendance.section_id')
            ->get()
            ->mapWithKeys(function($r){
                $key = "{$r->class_id}|{$r->section_id}";
                return [$key => [
                    'present_male' => (int)$r->present_male,
                    'present_female' => (int)$r->present_female,
                    'absent_male' => (int)$r->absent_male,
                    'absent_female' => (int)$r->absent_female,
                    'present_total' => (int)$r->present_total,
                    'absent_total' => (int)$r->absent_total,
                ]];
            });

        // Map of sections with any attendance record today (to detect 'not taken' sections)
        $attendanceExists = Attendance::select('class_id','section_id')
            ->where('date',$date)
            ->distinct()->get()
            ->mapWithKeys(fn($r)=>["{$r->class_id}|{$r->section_id}"=>true]);

        $classBreakdown = [];
        $grandTotal = 0; $grandPresent = 0;
        foreach ($sectionTotals as $row) {
            $key = $row->class_id;
            $attKey = "{$row->class_id}|{$row->section_id}";
            $genderAtt = $attendanceGender->get($attKey, [
                'present_male'=>0,'present_female'=>0,'absent_male'=>0,'absent_female'=>0,'present_total'=>0,'absent_total'=>0
            ]);
            if (!isset($classBreakdown[$key])) {
                $classBreakdown[$key] = [
                    'class_id' => $row->class_id,
                    'class_name' => $row->class_name,
                    'numeric_value' => $row->numeric_value,
                    'sections' => [],
                    'total' => 0,
                    'total_male' => 0,
                    'total_female' => 0,
                    'present_male' => 0,
                    'present_female' => 0,
                    'absent_male' => 0,
                    'absent_female' => 0,
                    'present_total' => 0,
                    'absent_total' => 0,
                ];
            }
            $classBreakdown[$key]['sections'][] = [
                'section_id' => $row->section_id,
                'section_name' => $row->section_name,
                'total' => (int)$row->total,
                'total_male' => (int)$row->total_male,
                'total_female' => (int)$row->total_female,
                'present_male' => $genderAtt['present_male'],
                'absent_male' => $genderAtt['absent_male'],
                'present_female' => $genderAtt['present_female'],
                'absent_female' => $genderAtt['absent_female'],
                'present_total' => $genderAtt['present_total'],
                'absent_total' => $genderAtt['absent_total'],
                'att_taken' => (bool)($attendanceExists["{$row->class_id}|{$row->section_id}"] ?? false),
            ];
            // Aggregate class totals
            $classBreakdown[$key]['total'] += (int)$row->total;
            $classBreakdown[$key]['total_male'] += (int)$row->total_male;
            $classBreakdown[$key]['total_female'] += (int)$row->total_female;
            $classBreakdown[$key]['present_male'] += $genderAtt['present_male'];
            $classBreakdown[$key]['present_female'] += $genderAtt['present_female'];
            $classBreakdown[$key]['absent_male'] += $genderAtt['absent_male'];
            $classBreakdown[$key]['absent_female'] += $genderAtt['absent_female'];
            $classBreakdown[$key]['present_total'] += $genderAtt['present_total'];
            $classBreakdown[$key]['absent_total'] += $genderAtt['absent_total'];
            $grandTotal += (int)$row->total;
            $grandPresent += $genderAtt['present_total'];
        }
        // Convert to collection and compute percentages and order by numeric_value
        $classWise = collect($classBreakdown)->sortBy('numeric_value')->map(function($c){
            $anyAtt = false;
            foreach ($c['sections'] as $s) { if (!empty($s['att_taken'])) { $anyAtt = true; break; } }
            $c['any_att'] = $anyAtt;
            $c['percentage'] = ($c['total']>0 && $anyAtt) ? round(($c['present_total']/$c['total'])*100,1) : null;
            return (object)$c;
        })->values();
    // Grand percent: reuse same attendance presence flag
    $grandPercent = ($grandTotal>0 && $anyAttendanceToday) ? round(($grandPresent/$grandTotal)*100,1) : null;

        // Gender pie for today's present
        $genderCounts = Attendance::select('students.gender', DB::raw('COUNT(DISTINCT attendance.student_id) as cnt'))
            ->join('students','students.id','=','attendance.student_id')
            ->where('attendance.date', $date)
            ->where('students.school_id', $school->id)
            ->whereIn('attendance.status', ['present','late'])
            ->groupBy('students.gender')
            ->pluck('cnt','gender');

        // Absent list for today with roll, class, section, consecutive absence approximation
        $absentees = Attendance::select(
                'attendance.student_id','students.student_name_bn','students.student_name_en','students.gender',
                'student_enrollments.roll_no','classes.name as class_name','sections.name as section_name'
            )
            ->join('students','students.id','=','attendance.student_id')
            ->leftJoin('student_enrollments','student_enrollments.student_id','=','students.id')
            ->leftJoin('classes','classes.id','=','student_enrollments.class_id')
            ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
            ->where('student_enrollments.school_id', $school->id)
            ->when($yearVal, fn($q)=>$q->where('student_enrollments.academic_year_id', $yearVal))
            ->where('attendance.date', $date)
            ->where('attendance.status','absent')
            ->orderBy('classes.numeric_value')
            ->orderBy('student_enrollments.roll_no')
            ->get();

        // Compute consecutive absence days (approx: days since last present/late date, inclusive)
        $absentees = $absentees->map(function($s) use ($date) {
            $lastPresent = Attendance::where('student_id', $s->student_id)
                ->whereIn('status',['present','late'])
                ->where('date','<', $date)
                ->orderByDesc('date')
                ->value('date');
            $streak = null;
            if ($lastPresent) {
                $streak = (new \DateTime($date))->diff(new \DateTime($lastPresent))->days;
                $streak = max(1, $streak); // at least 1 for today
            } else {
                $streak = 1; // no prior present/late found
            }
            // Latest remark
            $latestRemark = Attendance::where('student_id', $s->student_id)
                ->whereNotNull('remarks')
                ->where('remarks','!=','')
                ->orderByDesc('date')
                ->value('remarks');
            $s->streak_days = $streak;
            $s->latest_remarks = $latestRemark;
            return $s;
        });

        // Prepare chart payloads
        $barLabels = $classWise->pluck('class_name');
        $barData = $classWise->pluck('percentage');
        $genderLabels = $genderCounts->keys();
        $genderData = $genderCounts->values();

        return view('principal.attendance.dashboard', compact(
            'school','date','totalStudents','presentToday','absentToday','attendancePercent',
            'classWise','barLabels','barData','genderLabels','genderData','absentees','grandTotal','grandPresent','grandPercent'
        ));
    }
    public function index(School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->active()->orderBy('numeric_value')->get();
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();

        return view('principal.attendance.class.index', compact('school', 'classes', 'currentYear'));
    }

    public function take(School $school, Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
        ]);

        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $date = now()->toDateString(); // Force current date

        // Simplified permission (allow for now; tighten later if needed)
        $allowed = true;

        if (!$allowed) {
            return back()->with('error', 'আপনার এই ক্লাস/শাখার উপস্থিতি নেওয়ার অনুমতি নেই।');
        }

        $schoolClass = SchoolClass::find($classId);
        $section = Section::find($sectionId);
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearVal = $currentYear?->id;

        // Get enrolled students for this class and section (current year only)
        $enrollments = StudentEnrollment::with('student')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active')
            ->when($yearVal, function($q) use ($yearVal){ $q->where('academic_year_id', $yearVal); })
            ->orderBy('roll_no')
            ->get();

        // Get existing attendance records for today
        $existingAttendance = Attendance::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('date', $date)
            ->pluck('status', 'student_id')
            ->toArray();

        $remarks = Attendance::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('date', $date)
            ->pluck('remarks', 'student_id')
            ->toArray();

        $isExistingRecord = !empty($existingAttendance);

        return view('principal.attendance.class.take', compact(
            'school', 'schoolClass', 'section', 'enrollments', 'date',
            'existingAttendance', 'remarks', 'isExistingRecord'
        ));
    }

    public function store(School $school, Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|in:present,absent,late',
        ]);

        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $date = now()->toDateString(); // Force current date

        // Simplified permission (allow for now; tighten later if needed)
        $allowed = true;

        if (!$allowed) {
            return back()->with('error', 'আপনার এই ক্লাস/শাখার উপস্থিতি নেওয়ার অনুমতি নেই।');
        }

        // Build expected enrollment list (server-side completeness check like legacy script)
        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearVal = $currentYear?->id;

        $enrollments = StudentEnrollment::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active')
            ->when($yearVal, fn($q)=>$q->where('academic_year_id', $yearVal))
            ->orderBy('roll_no')
            ->pluck('student_id')
            ->toArray();

        $submittedIds = array_map('intval', array_keys($request->attendance));
        $missingIds = array_diff($enrollments, $submittedIds);
        if (!empty($missingIds)) {
            return back()->with('error', 'সকল শিক্ষার্থীর হাজিরা নির্বাচন বাধ্যতামূলক।')->withInput();
        }
        // Additionally ensure each submitted entry has a status (already validated per element) but protect against empty arrays
        foreach ($request->attendance as $sid => $data) {
            if (!isset($data['status']) || $data['status'] === '') {
                return back()->with('error', 'কিছু শিক্ষার্থীর স্ট্যাটাস ফাঁকা আছে।')->withInput();
            }
        }

        // Check if attendance already exists
        $existingCount = Attendance::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('date', $date)
            ->count();

        $isExistingRecord = $existingCount > 0;

        try {
            if ($isExistingRecord) {
                // Update existing records
                foreach ($request->attendance as $studentId => $data) {
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'class_id' => $classId,
                            'section_id' => $sectionId,
                            'date' => $date,
                        ],
                        [
                            'status' => $data['status'],
                            'remarks' => $data['remarks'] ?? null,
                            'recorded_by' => Auth::id(),
                        ]
                    );
                }
                $message = 'উপস্থিতি সফলভাবে আপডেট করা হয়েছে!';
            } else {
                // Create new records
                $attendanceData = [];
                foreach ($request->attendance as $studentId => $data) {
                    $attendanceData[] = [
                        'student_id' => $studentId,
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'date' => $date,
                        'status' => $data['status'],
                        'remarks' => $data['remarks'] ?? null,
                        'recorded_by' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Attendance::insert($attendanceData);
                $message = 'উপস্থিতি সফলভাবে রেকর্ড করা হয়েছে!';
            }

            return redirect()->route('principal.institute.attendance.class.take', [$school, 'class_id' => $classId, 'section_id' => $sectionId])->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'উপস্থিতি রেকর্ড করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }
}
