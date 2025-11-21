<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(School $school)
    {
        $q = request('q');
        $teams = Team::forSchool($school->id)
            ->when($q, fn($qb)=>$qb->where('name','like',"%$q%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        return view('principal.institute.teams.index', compact('school','teams','q'));
    }

    public function create(School $school)
    {
        $team = new Team(['status'=>'active']);
        return view('principal.institute.teams.create', compact('school','team'));
    }

    public function store(Request $request, School $school)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
        $data['school_id'] = $school->id;
        Team::create($data);
        return redirect()->route('principal.institute.teams.index', $school)->with('success','টিম তৈরি হয়েছে');
    }

    public function edit(School $school, Team $team)
    {
        abort_unless($team->school_id === $school->id, 404);
        return view('principal.institute.teams.edit', compact('school','team'));
    }

    public function update(Request $request, School $school, Team $team)
    {
        abort_unless($team->school_id === $school->id, 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
        $team->update($data);
        return redirect()->route('principal.institute.teams.index', $school)->with('success','টিম আপডেট হয়েছে');
    }

    public function destroy(School $school, Team $team)
    {
        abort_unless($team->school_id === $school->id, 404);
        $team->delete();
        return back()->with('success','টিম মুছে ফেলা হয়েছে');
    }

    public function addStudents(School $school, Team $team)
    {
        abort_unless($team->school_id === $school->id, 404);
        $classId = request('class_id');
        $sectionId = request('section_id');
        $q = request('q');

        // Base queries for dropdowns
        $classes = \App\Models\SchoolClass::forSchool($school->id)->active()->orderBy('numeric_value')->get(['id','name','numeric_value']);
        $sections = \App\Models\Section::forSchool($school->id)
            ->where('status','active')
            ->when($classId, fn($qq)=>$qq->where('class_id',$classId))
            ->get(['id','name','class_id']);
        // Students already in team (for pre-check + possible removal)
        $existingIds = $team->students()->pluck('students.id')->all();

        // Current academic year (foreign key id)
        $currentYear = \App\Models\AcademicYear::forSchool($school->id)->current()->first();
        $yearVal = $currentYear?->id;

        // Build filtered active enrollments (include all; mark existing separately)
        $enrollQuery = \App\Models\StudentEnrollment::select(
                'student_enrollments.student_id','student_enrollments.roll_no','student_enrollments.class_id','student_enrollments.section_id',
                'students.student_name_bn','students.student_name_en',
                'classes.name as class_name','sections.name as section_name'
            )
            ->join('students','students.id','=','student_enrollments.student_id')
            ->join('classes','classes.id','=','student_enrollments.class_id')
            ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
            ->where('student_enrollments.school_id',$school->id)
            ->where('student_enrollments.status','active')
            ->when($yearVal, fn($qq)=>$qq->where('student_enrollments.academic_year_id',$yearVal))
            ->when($classId, fn($qq)=>$qq->where('student_enrollments.class_id',$classId))
            ->when($sectionId, fn($qq)=>$qq->where('student_enrollments.section_id',$sectionId))
            ->when($q, fn($qq)=>$qq->where(function($sub) use ($q){
                $sub->where('students.student_name_en','like',"%$q%")
                    ->orWhere('students.student_name_bn','like',"%$q%")
                    ->orWhere('student_enrollments.roll_no','like',"%$q%");
            }))
            ->orderBy('classes.numeric_value')
            ->orderBy('student_enrollments.roll_no');
        $enrollments = $enrollQuery->get();

        return view('principal.institute.teams.add-students', [
            'school' => $school,
            'team' => $team,
            'enrollments' => $enrollments,
            'classes' => $classes,
            'sections' => $sections,
            'selectedClass' => $classId,
            'selectedSection' => $sectionId,
            'q' => $q,
            'existingIds' => $existingIds,
        ]);
    }

    public function storeStudents(Request $request, School $school, Team $team)
    {
        abort_unless($team->school_id === $school->id, 404);
        $data = $request->validate([
            'student_ids' => 'nullable|array', // can be empty to remove all
            'student_ids.*' => 'exists:students,id',
        ]);

        $newIds = collect($data['student_ids'] ?? [])->map(fn($id)=>(int)$id)->unique()->values();
        $currentIds = $team->students()->pluck('students.id');

        // Determine removals
        $removeIds = $currentIds->diff($newIds);
        $attachIds = $newIds->diff($currentIds);

        if($removeIds->isNotEmpty()) {
            $team->students()->detach($removeIds->all());
        }
        if($attachIds->isNotEmpty()) {
            $attachData = [];
            foreach ($attachIds as $sid) {
                $attachData[$sid] = ['joined_at' => now(), 'status' => 'active'];
            }
            $team->students()->attach($attachData);
        }

        return redirect()->route('principal.institute.teams.add-students', [$school,$team])->with('success','টিম সদস্য তালিকা হালনাগাদ হয়েছে');
    }

    public function members(School $school, Team $team)
    {
        abort_unless($team->school_id === $school->id, 404);
        // Load members with enrollment context (current academic year if any)
        $currentYear = \App\Models\AcademicYear::forSchool($school->id)->current()->first();
        $yearVal = $currentYear?->id;

        $members = \App\Models\StudentEnrollment::select(
                'student_enrollments.student_id','student_enrollments.roll_no','student_enrollments.class_id','student_enrollments.section_id',
                'students.student_name_bn','students.student_name_en',
                'classes.name as class_name','sections.name as section_name'
            )
            ->join('students','students.id','=','student_enrollments.student_id')
            ->join('classes','classes.id','=','student_enrollments.class_id')
            ->leftJoin('sections','sections.id','=','student_enrollments.section_id')
            ->where('student_enrollments.school_id',$school->id)
            ->where('student_enrollments.status','active')
            ->when($yearVal, fn($qq)=>$qq->where('student_enrollments.academic_year_id',$yearVal))
            ->whereIn('student_enrollments.student_id', $team->students()->pluck('students.id'))
            ->orderBy('classes.numeric_value')
            ->orderBy('student_enrollments.roll_no')
            ->get();

        return view('principal.institute.teams.members', [
            'school' => $school,
            'team' => $team,
            'members' => $members,
        ]);
    }
}
