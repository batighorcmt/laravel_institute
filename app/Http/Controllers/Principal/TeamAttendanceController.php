<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\Team;
use App\Models\TeamAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamAttendanceController extends Controller
{
    /**
     * Filter form for team attendance (team + optional class/section + date)
     */
    public function index(School $school, Request $request)
    {
        $teams = Team::forSchool($school->id)->active()->orderBy('name')->get(['id','name']);
        $classes = SchoolClass::forSchool($school->id)->active()->orderBy('numeric_value')->get(['id','name','numeric_value']);
        // If class selected limit sections
        $sections = Section::forSchool($school->id)
            ->where('status','active')
            ->when($request->class_id, fn($q)=>$q->where('class_id',$request->class_id))
            ->get(['id','name','class_id']);

        return view('principal.attendance.team.index', [
            'school' => $school,
            'teams' => $teams,
            'classes' => $classes,
            'sections' => $sections,
            'selectedTeam' => $request->team_id,
            'selectedClass' => $request->class_id,
            'selectedSection' => $request->section_id,
            'date' => $request->date ?? now()->toDateString(),
        ]);
    }

    /**
     * Show take/update form similar to class attendance but based on team membership (filtered by class/section)
     */
    public function take(School $school, Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'date' => 'nullable|date',
        ]);

        $teamId = (int)$request->team_id;
        $classId = $request->class_id ? (int)$request->class_id : null;
        $sectionId = $request->section_id ? (int)$request->section_id : null;
        $date = $request->date ?: now()->toDateString();

        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearVal = $currentYear?->id;

        // Base query: students who are in the team
        $enrollments = StudentEnrollment::with(['student'])
            ->join('team_student','team_student.student_id','=','student_enrollments.student_id')
            ->where('team_student.team_id',$teamId)
            ->where('student_enrollments.school_id',$school->id)
            ->where('student_enrollments.status','active')
            ->when($yearVal, fn($q)=>$q->where('student_enrollments.academic_year_id',$yearVal))
            ->when($classId, fn($q)=>$q->where('student_enrollments.class_id',$classId))
            ->when($sectionId, fn($q)=>$q->where('student_enrollments.section_id',$sectionId))
            ->orderBy('student_enrollments.roll_no')
            ->select('student_enrollments.*')
            ->get();

        // Existing team attendance records for this date/team (+same optional filters)
        $existingAttendance = TeamAttendance::where('team_id',$teamId)
            ->where('date',$date)
            ->when($classId, fn($q)=>$q->where('class_id',$classId))
            ->when($sectionId, fn($q)=>$q->where('section_id',$sectionId))
            ->pluck('status','student_id')->toArray();
        $remarks = TeamAttendance::where('team_id',$teamId)
            ->where('date',$date)
            ->when($classId, fn($q)=>$q->where('class_id',$classId))
            ->when($sectionId, fn($q)=>$q->where('section_id',$sectionId))
            ->pluck('remarks','student_id')->toArray();
        $isExistingRecord = !empty($existingAttendance);

        $team = Team::find($teamId);
        $schoolClass = $classId ? SchoolClass::find($classId) : null;
        $section = $sectionId ? Section::find($sectionId) : null;

        return view('principal.attendance.team.take', compact(
            'school','team','schoolClass','section','enrollments','date','existingAttendance','remarks','isExistingRecord'
        ));
    }

    /**
     * Store / update team attendance records
     */
    public function store(School $school, Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|in:present,absent,late',
        ]);
        $teamId = (int)$request->team_id;
        $classId = $request->class_id ? (int)$request->class_id : null;
        $sectionId = $request->section_id ? (int)$request->section_id : null;
        $date = $request->date;

        $currentYear = AcademicYear::forSchool($school->id)->current()->first();
        $yearVal = $currentYear?->id;

        // Determine expected student IDs (for completeness validation)
        $expectedIds = StudentEnrollment::join('team_student','team_student.student_id','=','student_enrollments.student_id')
            ->where('team_student.team_id',$teamId)
            ->where('student_enrollments.school_id',$school->id)
            ->where('student_enrollments.status','active')
            ->when($yearVal, fn($q)=>$q->where('student_enrollments.academic_year_id',$yearVal))
            ->when($classId, fn($q)=>$q->where('student_enrollments.class_id',$classId))
            ->when($sectionId, fn($q)=>$q->where('student_enrollments.section_id',$sectionId))
            ->orderBy('student_enrollments.roll_no')
            ->pluck('student_enrollments.student_id')
            ->toArray();

        $submittedIds = array_map('intval', array_keys($request->attendance));
        $missingIds = array_diff($expectedIds, $submittedIds);
        if (!empty($missingIds)) {
            return back()->with('error','সকল শিক্ষার্থীর হাজিরা নির্বাচন বাধ্যতামূলক।')->withInput();
        }
        foreach ($request->attendance as $sid => $data) {
            if (!isset($data['status']) || $data['status'] === '') {
                return back()->with('error','কিছু শিক্ষার্থীর স্ট্যাটাস ফাঁকা আছে।')->withInput();
            }
        }

        // Detect existing records
        $existingCount = TeamAttendance::where('team_id',$teamId)
            ->where('date',$date)
            ->when($classId, fn($q)=>$q->where('class_id',$classId))
            ->when($sectionId, fn($q)=>$q->where('section_id',$sectionId))
            ->count();
        $isExistingRecord = $existingCount > 0;

        try {
            if ($isExistingRecord) {
                foreach ($request->attendance as $studentId => $data) {
                    TeamAttendance::updateOrCreate([
                        'team_id' => $teamId,
                        'student_id' => $studentId,
                        'date' => $date,
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                    ],[
                        'status' => $data['status'],
                        'remarks' => $data['remarks'] ?? null,
                        'school_id' => $school->id,
                        'recorded_by' => Auth::id(),
                    ]);
                }
                $message = 'টিম হাজিরা সফলভাবে আপডেট হয়েছে!';
            } else {
                $bulk = [];
                foreach ($request->attendance as $studentId => $data) {
                    $bulk[] = [
                        'school_id' => $school->id,
                        'team_id' => $teamId,
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
                TeamAttendance::insert($bulk);
                $message = 'টিম হাজিরা সফলভাবে রেকর্ড হয়েছে!';
            }
            return redirect()->route('principal.institute.attendance.team.take', [
                $school,
                'team_id' => $teamId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'date' => $date,
            ])->with('success',$message);
        } catch (\Exception $e) {
            return back()->with('error','টিম হাজিরা সংরক্ষণে সমস্যা: '.$e->getMessage());
        }
    }
}
