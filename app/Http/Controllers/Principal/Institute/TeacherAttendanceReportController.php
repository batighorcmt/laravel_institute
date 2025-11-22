<?php

namespace App\Http\Controllers\Principal\Institute;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TeacherAttendanceReportController extends Controller
{
    public function dailyReport(School $school, Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        // Get all teachers for this school
        $teachers = Teacher::where('school_id', $school->id)
            ->where('status', 'active')
            ->with(['user', 'teacherAttendances' => function($query) use ($date, $school) {
                $query->where('date', $date)
                      ->where('school_id', $school->id);
            }])
            ->orderBy('first_name')
            ->get();
        
        return view('principal.institute.teacher-attendance.daily-report', compact('school', 'teachers', 'date'));
    }

    public function monthlyReport(School $school, Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();
        
        // Get all teachers for this school with their attendances
        $teachers = Teacher::where('school_id', $school->id)
            ->where('status', 'active')
            ->with(['user', 'teacherAttendances' => function($query) use ($startDate, $endDate, $school) {
                $query->where('school_id', $school->id)
                      ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                      ->orderBy('date');
            }])
            ->orderBy('first_name')
            ->get();
        
        // Get all dates in the month
        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->copy();
            $current->addDay();
        }
        
        return view('principal.institute.teacher-attendance.monthly-report', compact('school', 'teachers', 'dates', 'month'));
    }

    public function dailyReportPrint(School $school, Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        // Get all teachers for this school
        $teachers = Teacher::where('school_id', $school->id)
            ->where('status', 'active')
            ->with(['user', 'teacherAttendances' => function($query) use ($date, $school) {
                $query->where('date', $date)
                      ->where('school_id', $school->id);
            }])
            ->orderBy('first_name')
            ->get();
        
        return view('principal.institute.teacher-attendance.daily-report-print', compact('school', 'teachers', 'date'));
    }

    public function monthlyReportPrint(School $school, Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();
        
        // Get all teachers for this school with their attendances
        $teachers = Teacher::where('school_id', $school->id)
            ->where('status', 'active')
            ->with(['user', 'teacherAttendances' => function($query) use ($startDate, $endDate, $school) {
                $query->where('school_id', $school->id)
                      ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                      ->orderBy('date');
            }])
            ->orderBy('first_name')
            ->get();
        
        // Get all dates in the month
        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->copy();
            $current->addDay();
        }
        
        // Get holidays from school
        $holidays = $school->holidays()
            ->where('status', 'active')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();
        
        // Get weekly holidays (e.g., Friday = 5, Saturday = 6)
        $weeklyHolidays = $school->weeklyHolidays()
            ->where('status', 'active')
            ->pluck('day_number')
            ->toArray();
        
        return view('principal.institute.teacher-attendance.monthly-report-print', compact('school', 'teachers', 'dates', 'month', 'holidays', 'weeklyHolidays'));
    }
}
