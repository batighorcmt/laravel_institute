<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Result;
use App\Models\AcademicYear;
use App\Models\Notice;
use App\Models\RoutineEntry;
use App\Models\Homework;
use App\Models\ExtraClassAttendance;
use App\Models\LessonEvaluationRecord;
use App\Models\StudentLeave;
use App\Models\Teacher;
use App\Models\ClassSubject;
use App\Models\ParentFeedback;
use App\Models\WeeklyHoliday;
use App\Models\Holiday;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ParentController extends Controller
{
    /**
     * Show parent dashboard.
     */
    public function dashboard(Request $request)
    {
        $children = $this->resolveChildren();
        
        if ($children->isEmpty()) {
            return view('parent.dashboard', [
                'children' => collect(),
                'selectedStudent' => null,
            ]);
        }

        $selectedId = $request->get('student_id');
        $selectedStudent = $selectedId 
            ? $children->firstWhere('id', $selectedId) 
            : $children->first();
            
        if (!$selectedStudent) {
            $selectedStudent = $children->first();
        }

        // Fetch related data for the selected student
        $currentYear = AcademicYear::forSchool($selectedStudent->school_id)->current()->first();
        
        $attendanceStats = [
            'present' => Attendance::where('student_id', $selectedStudent->id)->where('status', 'present')->count(),
            'absent' => Attendance::where('student_id', $selectedStudent->id)->where('status', 'absent')->count(),
        ];

        $latestResults = Result::where('student_id', $selectedStudent->id)
            ->published()
            ->with('exam')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $notices = Notice::forSchool($selectedStudent->school_id)
            ->published()
            ->orderByDesc('publish_at')
            ->limit(5)
            ->get();

        return view('parent.dashboard', compact('children', 'selectedStudent', 'attendanceStats', 'latestResults', 'notices', 'currentYear'));
    }

    public function profile(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        return view('parent.profile', compact('children', 'selectedStudent'));
    }

    public function subjects(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        
        $subjects = ClassSubject::where('class_id', $selectedStudent->class_id)
            ->where('school_id', $selectedStudent->school_id)
            ->with('subject')
            ->get();

        return view('parent.subjects', compact('children', 'selectedStudent', 'subjects'));
    }

    public function routine(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        
        $enrollment = $selectedStudent->enrollments()->latest()->first();
        $routine = $enrollment 
            ? RoutineEntry::where('class_id', $enrollment->class_id)
                ->where('section_id', $enrollment->section_id)
                ->with(['subject', 'teacher'])
                ->orderBy('day_of_week')
                ->orderBy('period_number')
                ->get()
                ->map(function($item) {
                    $item->day_of_week = ucfirst(strtolower($item->day_of_week));
                    return $item;
                })
                ->groupBy('day_of_week')
            : collect();

        return view('parent.routine', compact('children', 'selectedStudent', 'routine'));
    }

    public function homework(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        
        $homeworkList = Homework::where('class_id', $selectedStudent->class_id)
            ->where('school_id', $selectedStudent->school_id)
            ->with(['subject', 'teacher'])
            ->orderByDesc('date')
            ->paginate(15);

        return view('parent.homework', compact('children', 'selectedStudent', 'homeworkList'));
    }

    public function classAttendance(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));
        
        $calendarData = $this->getMonthlyAttendanceData($selectedStudent->id, $month, $year, 'class');
        
        return view('parent.attendance', array_merge([
            'children' => $children,
            'selectedStudent' => $selectedStudent,
            'type' => 'class',
            'month' => $month,
            'year' => $year,
        ], $calendarData));
    }

    public function extraAttendanceReport(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));
        
        $calendarData = $this->getMonthlyAttendanceData($selectedStudent->id, $month, $year, 'extra');
        
        return view('parent.attendance', array_merge([
            'children' => $children,
            'selectedStudent' => $selectedStudent,
            'type' => 'extra',
            'month' => $month,
            'year' => $year,
        ], $calendarData));
    }

    private function getMonthlyAttendanceData($studentId, $month, $year, $type = 'class')
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        if ($type == 'class') {
            $attendances = Attendance::where('student_id', $studentId)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get()
                ->keyBy(fn($item) => $item->date->format('Y-m-d'));
        } else {
            $attendances = ExtraClassAttendance::where('student_id', $studentId)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get()
                ->keyBy(fn($item) => $item->date->format('Y-m-d'));
        }

        // Get leaves
        $leaves = StudentLeave::where('student_id', $studentId)
            ->where('status', 'approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->get();

        $leaveDates = [];
        foreach ($leaves as $leave) {
            $period = Carbon::parse($leave->start_date)->daysUntil($leave->end_date);
            foreach ($period as $date) {
                if ($date->between($startDate, $endDate)) {
                    $leaveDates[$date->format('Y-m-d')] = true;
                }
            }
        }

        // Get Weekly Holidays
        $schoolId = Student::find($studentId)->school_id;
        $weeklyHolidays = WeeklyHoliday::forSchool($schoolId)->active()->pluck('day_name')->toArray();
        $dayMap = [
            'Saturday' => 'শনিবার', 'Sunday' => 'রবিবার', 'Monday' => 'সোমবার', 
            'Tuesday' => 'মঙ্গলবার', 'Wednesday' => 'বুধবার', 'Thursday' => 'বৃহস্পতিবার', 'Friday' => 'শুক্রবার'
        ];
        
        // Specific Holidays
        $specificHolidays = Holiday::forSchool($schoolId)->active()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();

        $calendar = [];
        $stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'working_days' => 0];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayNameBn = $dayMap[$currentDate->format('l')];
            
            $isHoliday = in_array($dayNameBn, $weeklyHolidays) || in_array($dateStr, $specificHolidays);
            $status = null;
            $remarks = null;

            if (isset($attendances[$dateStr])) {
                $status = $attendances[$dateStr]->status;
                $remarks = $attendances[$dateStr]->remarks;
                if ($status == 'present') $stats['present']++;
                elseif ($status == 'absent') $stats['absent']++;
                elseif ($status == 'late') $stats['late']++;
                
                if (!$isHoliday) $stats['working_days']++;
            } elseif (isset($leaveDates[$dateStr])) {
                $status = 'leave';
                $stats['leave']++;
                if (!$isHoliday) $stats['working_days']++;
            } elseif (!$isHoliday && $currentDate < Carbon::today()) {
                // If not marked and not holiday and in past, assuming absent or not recorded
                // But usually we only count what is recorded.
            }

            $calendar[$dateStr] = [
                'day' => $currentDate->day,
                'is_holiday' => $isHoliday,
                'status' => $status,
                'remarks' => $remarks
            ];

            $currentDate->addDay();
        }

        return [
            'calendar' => $calendar,
            'stats' => $stats,
            'monthName' => $startDate->format('F Y'),
            'prevMonth' => $startDate->copy()->subMonth(),
            'nextMonth' => $startDate->copy()->addMonth(),
        ];
    }

    public function evaluations(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        
        // Default date to today if not provided
        $filterDate = $request->input('date', Carbon::today()->toDateString());
        
        $query = LessonEvaluationRecord::where('student_id', $selectedStudent->id)
            ->with(['lessonEvaluation.subject', 'lessonEvaluation.teacher']);

        // Filtering
        if ($filterDate != '') {
            $query->whereHas('lessonEvaluation', function($q) use ($filterDate) {
                $q->where('evaluation_date', $filterDate);
            });
        }

        if ($request->has('subject_id') && $request->subject_id != '') {
            $query->whereHas('lessonEvaluation', function($q) use ($request) {
                $q->where('subject_id', $request->subject_id);
            });
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $evaluations = $query->orderByDesc('created_at')->paginate(15);

        // Fetch subjects for filter
        $enrollment = $selectedStudent->enrollments()->latest()->first();
        $subjects = [];
        if ($enrollment) {
            $subjects = ClassSubject::where('class_id', $enrollment->class_id)
                ->with('subject')
                ->get();
        }

        // Yearly Summary Data
        $currentYear = Carbon::today()->year;
        $summaryData = LessonEvaluationRecord::where('student_id', $selectedStudent->id)
            ->whereHas('lessonEvaluation', function($q) use ($currentYear) {
                $q->whereYear('evaluation_date', $currentYear);
            })
            ->with(['lessonEvaluation.subject', 'lessonEvaluation.teacher'])
            ->get()
            ->groupBy('lessonEvaluation.subject_id')
            ->map(function($records) {
                $firstRecord = $records->first();
                return [
                    'subject_name' => $firstRecord->lessonEvaluation->subject->name ?? 'N/A',
                    'teacher_name' => $firstRecord->lessonEvaluation->teacher->name ?? 'N/A',
                    'completed' => $records->where('status', 'completed')->count(),
                    'partial' => $records->where('status', 'partial')->count(),
                    'not_done' => $records->where('status', 'not_done')->count(),
                    'absent' => $records->where('status', 'absent')->count(),
                ];
            });

        return view('parent.evaluations', compact('children', 'selectedStudent', 'evaluations', 'subjects', 'filterDate', 'summaryData', 'currentYear'));
    }

    public function leaves(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        
        $leaves = StudentLeave::where('student_id', $selectedStudent->id)
            ->orderByDesc('start_date')
            ->paginate(15);

        return view('parent.leaves', compact('children', 'selectedStudent', 'leaves'));
    }

    public function submitLeave(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'reason' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|string',
        ]);

        StudentLeave::create([
            'school_id' => $request->user()->school_id ?? Student::find($validated['student_id'])->school_id,
            'student_id' => $validated['student_id'],
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'ছুটির আবেদন জমা হয়েছে।');
    }

    public function notices(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        return view('parent.notices', compact('children', 'selectedStudent'));
    }

    public function teachers(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        
        $enrollment = $selectedStudent->enrollments()->latest()->first();
        $teacherIds = $enrollment 
            ? RoutineEntry::where('class_id', $enrollment->class_id)
                ->where('section_id', $enrollment->section_id)
                ->pluck('teacher_id')
                ->unique()
            : collect();

        $teachers = Teacher::whereIn('id', $teacherIds)->get();

        return view('parent.teachers', compact('children', 'selectedStudent', 'teachers'));
    }

    public function feedback(Request $request)
    {
        $children = $this->resolveChildren();
        $selectedStudent = $this->getSelectedStudent($children, $request);
        
        $feedbacks = ParentFeedback::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('parent.feedback', compact('children', 'selectedStudent', 'feedbacks'));
    }

    public function submitFeedback(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        ParentFeedback::create([
            'school_id' => Student::find($validated['student_id'])->school_id,
            'user_id' => Auth::id(),
            'student_id' => $validated['student_id'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'আপনার মতামত/অভিযোগ জমা হয়েছে।');
    }

    private function getSelectedStudent($children, Request $request)
    {
        if ($children->isEmpty()) return null;
        $selectedId = $request->get('student_id');
        return $selectedId ? $children->firstWhere('id', $selectedId) : $children->first();
    }

    private function resolveChildren()
    {
        $user = Auth::user();
        
        // 1. Direct student login
        $directStudent = Student::active()->where('user_id', $user->id)->first();
        if ($directStudent) {
            return collect([$directStudent]);
        }

        // 2. Parent login (linked via guardian phone)
        return Student::active()
            ->where(function($q) use ($user) {
                $q->where('guardian_phone', $user->username)
                  ->orWhere('guardian_phone', $user->email);
            })->get();
    }
}
