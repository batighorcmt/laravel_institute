<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\StudentEnrollment;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

class PrincipalReportController extends Controller
{
    public function attendanceSummary(Request $request)
    {
        return response()->json([
            'data' => [
                'present_percentage' => null,
                'absent_percentage' => null,
            ],
            'meta' => ['message' => 'attendance summary placeholder']
        ]);
    }

    /**
     * Return detailed attendance breakdown for principal mobile UI.
     * Expects request attribute 'current_school_id' to be set by middleware.
     * Optional query param: date (YYYY-MM-DD). If absent uses today.
     */
    public function attendanceDetails(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id')
            ?? (method_exists($user, 'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null)
            ?? ($user->primarySchool()?->id ?? null);
        if (empty($schoolId)) {
            return response()->json(['message' => 'No school context'], 400);
        }
        $date = $request->query('date', now()->toDateString());

        // Compute totals and breakdowns similar to Principal\AttendanceController::dashboard
        $currentYear = AcademicYear::forSchool($schoolId)->current()->first();
        $yearVal = $currentYear?->id;

        $totalStudents = StudentEnrollment::where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($yearVal, fn($q)=>$q->where('academic_year_id', $yearVal))
            ->count();

        $presentToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->where('attendance.date', $date)
            ->where('students.school_id', $schoolId)
            ->whereIn('attendance.status', ['present','late'])
            ->count();
        $absentToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->where('attendance.date', $date)
            ->where('students.school_id', $schoolId)
            ->where('attendance.status', 'absent')
            ->count();

        $anyAttendanceToday = Attendance::join('students','students.id','=','attendance.student_id')
            ->where('attendance.date', $date)
            ->where('students.school_id', $schoolId)
            ->exists();

        $attendancePercent = ($totalStudents > 0 && $anyAttendanceToday)
            ? round(($presentToday / $totalStudents) * 100, 1)
            : null;

        // Section totals
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
            ->where('student_enrollments.school_id', $schoolId)
            ->where('student_enrollments.status','active')
            ->where('sections.status','active')
            ->when($yearVal, fn($q)=>$q->where('student_enrollments.academic_year_id', $yearVal))
            ->groupBy('classes.id','classes.name','classes.numeric_value','sections.id','sections.name')
            ->get();

        $allClasses = SchoolClass::forSchool($schoolId)->active()->get(['id','name','numeric_value']);
        $existingKeys = $sectionTotals->map(fn($r)=>"{$r->class_id}|{$r->section_id}")->all();
        foreach ($allClasses as $cls) {
            $classSections = Section::forSchool($schoolId)
                ->where('class_id', $cls->id)
                ->where('status','active')
                ->get(['id','name']);
            if ($classSections->isEmpty()) {
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
        $sectionTotals = $sectionTotals->sortBy(function($r){
            return sprintf('%05d|%s', (int)$r->numeric_value, (string)$r->section_name);
        })->values();

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

        $classWise = collect($classBreakdown)->sortBy('numeric_value')->map(function($c){
            $anyAtt = false;
            foreach ($c['sections'] as $s) { if (!empty($s['att_taken'])) { $anyAtt = true; break; } }
            $c['any_att'] = $anyAtt;
            $c['percentage'] = ($c['total']>0 && $anyAtt) ? round(($c['present_total']/$c['total'])*100,1) : null;
            return $c;
        })->values();

        $grandPercent = ($grandTotal>0 && $anyAttendanceToday) ? round(($grandPresent/$grandTotal)*100,1) : null;

        $genderCounts = Attendance::select('students.gender', DB::raw('COUNT(DISTINCT attendance.student_id) as cnt'))
            ->join('students','students.id','=','attendance.student_id')
            ->where('attendance.date', $date)
            ->where('students.school_id', $schoolId)
            ->whereIn('attendance.status', ['present','late'])
            ->groupBy('students.gender')
            ->pluck('cnt','gender');

        return response()->json([
            'data' => [
                'date' => $date,
                'total_students' => $totalStudents,
                'present_today' => $presentToday,
                'absent_today' => $absentToday,
                'present_percentage' => $attendancePercent,
                'class_wise' => $classWise,
                'grand_total' => $grandTotal,
                'grand_present' => $grandPresent,
                'grand_percent' => $grandPercent,
            ],
            'meta' => ['message' => 'success', 'gender_counts' => $genderCounts->toArray()]
        ]);
    }

    public function examResultsSummary(Request $request)
    {
        return response()->json([
            'data' => [
                'average_score' => null,
                'top_students' => [],
            ],
            'meta' => ['message' => 'exam results summary placeholder']
        ]);
    }
}
