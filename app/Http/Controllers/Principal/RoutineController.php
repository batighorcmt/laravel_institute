<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\RoutineEntry;
use App\Models\ClassPeriod;
use App\Models\ClassSubject;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\UserSchoolRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoutineController extends Controller
{
    public function panel(School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->active()->ordered()->get(['id','name','numeric_value']);
        $sections = Section::forSchool($school->id)->orderBy('name')->get(['id','name','class_id']);
        // teachers with designation and serial ordering
        $teacherRoleIdVal = Role::where('name', Role::TEACHER)->value('id');
        $teachers = UserSchoolRole::where('school_id',$school->id)
            ->where('role_id',$teacherRoleIdVal)
            ->join('users','users.id','=','user_school_roles.user_id')
            ->orderByRaw('COALESCE(user_school_roles.serial_number, 999999) asc')
            ->orderBy('users.name')
            ->get(['users.id as id','users.name as name','user_school_roles.designation','user_school_roles.serial_number']);
        return view('principal.routines.panel', compact('school','classes','sections','teachers'));
    }

    public function subjects(Request $request, School $school)
    {
        try {
            $classId = (int)$request->get('class_id');
            if (!$classId) return response()->json([]);
            // First try via Eloquent to avoid SQL mode GROUP BY issues
            $mappings = ClassSubject::where('school_id',$school->id)
                ->where('class_id',$classId)
                ->with('subject:id,name')
                ->get();
            $subjects = $mappings->pluck('subject')->filter()->unique('id')->values();
            if ($subjects->isNotEmpty()) {
                $sorted = $subjects->sortBy('name')->values()->map(fn($s)=>['subject_id'=>$s->id,'name'=>$s->name]);
                return response()->json($sorted);
            }
            // Fallback to join only if above empty (unlikely)
            $q = ClassSubject::where('class_id',$classId)
                ->join('subjects','subjects.id','=','class_subjects.subject_id')
                ->select(['subjects.id as subject_id','subjects.name'])
                ->groupBy('subjects.id','subjects.name')
                ->orderBy('subjects.name');
            $rows = $q->get();
            if ($rows->isNotEmpty()) return response()->json($rows);
            // Final fallback: all subjects of the school
            $fallback = Subject::where('school_id',$school->id)->orderBy('name')->get(['id as subject_id','name']);
            return response()->json($fallback);
        } catch (\Throwable $e) {
            return response()->json([], 200);
        }
    }

    public function periodCount(Request $request, School $school)
    {
        try {
            $classId = (int)$request->get('class_id');
            $sectionId = (int)$request->get('section_id');
            if(!$classId || !$sectionId) return response()->json(['period_count'=>0]);
            $cp = ClassPeriod::forSchool($school->id)->forClassSection($classId,$sectionId)->first();
            return response()->json(['period_count' => $cp? (int)$cp->period_count : 0]);
        } catch (\Throwable $e) {
            return response()->json(['period_count'=>0,'error'=>'server']);
        }
    }

    public function setPeriodCount(Request $request, School $school)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'period_count' => 'required|integer|min:1|max:16'
        ]);
        try {
            $cp = ClassPeriod::updateOrCreate([
                'school_id'=>$school->id,
                'class_id'=>$data['class_id'],
                'section_id'=>$data['section_id']
            ], [ 'period_count'=>$data['period_count'] ]);
            return response()->json(['success'=>true,'period_count'=>$cp->period_count]);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'error'=>'server']);
        }
    }

    public function grid(Request $request, School $school)
    {
        $classId = (int)$request->get('class_id');
        $sectionId = (int)$request->get('section_id');
        if(!$classId || !$sectionId) return response()->json([]);
        $entries = RoutineEntry::forSchool($school->id)->forClassSection($classId,$sectionId)
            ->with(['subject:id,name','teacher:id,name'])
            ->get();
        $grid = [];
        foreach ($entries as $e) {
            $day = $e->day_of_week; $pn = (int)$e->period_number;
            $grid[$day][$pn][] = [
                'id' => $e->id,
                'subject_id' => $e->subject_id,
                'subject_name' => $e->subject?->name,
                'teacher_id' => $e->teacher_id,
                'teacher_name' => $e->teacher?->name,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time,
                'room' => $e->room,
                'remarks' => $e->remarks,
                'period_number' => $pn,
                'day_of_week' => $day,
            ];
        }
        return response()->json($grid);
    }

    public function saveEntry(Request $request, School $school)
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'day_of_week' => 'required|string|max:16',
            'period_number' => 'required|integer|min:1|max:32',
            'subject_id' => 'required|integer',
            'teacher_id' => 'required|integer',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'room' => 'nullable|string|max:64',
            'remarks' => 'nullable|string|max:191'
        ]);
        // Validate subject belongs to class
        $chk = ClassSubject::where('school_id',$school->id)->where('class_id',$data['class_id'])->where('subject_id',$data['subject_id'])->exists();
        if(!$chk) return response()->json(['success'=>false,'error'=>'Subject not assigned to class']);
        
        // Validate teacher and get actual teacher record ID
        $teacherUserId = $data['teacher_id']; // This is actually user_id from frontend
        $teacherOk = UserSchoolRole::where('school_id',$school->id)
            ->where('user_id', $teacherUserId)
            ->whereHas('role', fn($q)=>$q->where('name', Role::TEACHER))
            ->exists();
        if(!$teacherOk) return response()->json(['success'=>false,'error'=>'Invalid teacher']);
        
        // Get the actual teacher record ID (not user_id)
        $teacher = Teacher::where('user_id', $teacherUserId)
            ->where('school_id', $school->id)
            ->first();
        if(!$teacher) return response()->json(['success'=>false,'error'=>'Teacher record not found']);
        
        $payload = [
            'school_id'=>$school->id,
            'class_id'=>$data['class_id'],
            'section_id'=>$data['section_id'],
            'day_of_week'=>$data['day_of_week'],
            'period_number'=>$data['period_number'],
            'subject_id'=>$data['subject_id'],
            'teacher_id'=>$teacher->id, // Use teacher record ID, not user_id
            'start_time'=>$data['start_time'] ?? null,
            'end_time'=>$data['end_time'] ?? null,
            'room'=>$data['room'] ?? null,
            'remarks'=>$data['remarks'] ?? null,
        ];
        if(!empty($data['id'])) {
            $entry = RoutineEntry::where('school_id',$school->id)->where('id',$data['id'])->first();
            if(!$entry) return response()->json(['success'=>false,'error'=>'Not found']);
            $entry->update($payload);
        } else {
            $entry = RoutineEntry::create($payload);
        }
        return response()->json(['success'=>true,'id'=>$entry->id]);
    }

    public function deleteEntry(Request $request, School $school)
    {
        $id = (int)$request->get('id');
        if(!$id) return response()->json(['success'=>false,'error'=>'Missing id']);
        $ok = RoutineEntry::where('school_id',$school->id)->where('id',$id)->delete();
        return response()->json(['success'=>$ok>0]);
    }

    public function printView(Request $request, School $school)
    {
        $classId = (int)$request->get('class_id');
        $sectionId = (int)$request->get('section_id');
        if(!$classId || !$sectionId){
            return redirect()->route('principal.institute.routine.panel',$school)->with('error','প্রিন্টের জন্য আগে শ্রেণি ও শাখা নির্বাচন করুন');
        }
        $class = SchoolClass::find($classId);
        $section = Section::find($sectionId);
        if(!$class || !$section){
            return redirect()->route('principal.institute.routine.panel',$school)->with('error','সঠিক শ্রেণি বা শাখা পাওয়া যায়নি');
        }
        $periodCount = ClassPeriod::forSchool($school->id)->forClassSection($classId,$sectionId)->value('period_count') ?? 0;
        $entries = RoutineEntry::forSchool($school->id)->forClassSection($classId,$sectionId)
            ->with(['subject:id,name','teacher:id,name'])
            ->get()
            ->groupBy(fn($e)=>$e->day_of_week.'#'.$e->period_number);
        $days = ['saturday'=>'শনিবার','sunday'=>'রবিবার','monday'=>'সোমবার','tuesday'=>'মঙ্গলবার','wednesday'=>'বুধবার','thursday'=>'বৃহস্পতিবার','friday'=>'শুক্রবার'];
        return view('principal.routines.print', compact('school','class','section','periodCount','entries','days'));
    }
}
