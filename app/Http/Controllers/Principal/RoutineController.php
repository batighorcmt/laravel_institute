<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\RoutineEntry;
use App\Models\ClassPeriod;
use App\Models\ClassPeriodTime;
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
        $sections = Section::forSchool($school->id)
            ->orderBy('name')
            ->with(['classTeacher.user'])
            ->get(['id','name','class_id','class_teacher_id','class_teacher_name']);
        // Teachers for dropdown: use Teacher record IDs, show associated user name
        $teachers = Teacher::where('school_id',$school->id)
            ->with('user:id,name')
            ->orderByRaw('COALESCE(serial_number, 999999) asc')
            ->orderBy(User::select('name')->whereColumn('users.id','teachers.user_id'))
            ->get(['id','user_id','school_id','designation','serial_number','initials']);
        return view('principal.routines.panel', compact('school','classes','sections','teachers'));
    }

    public function teacherPanel(School $school)
    {
        $teachers = Teacher::where('school_id',$school->id)
            ->with('user:id,name')
            ->orderByRaw('COALESCE(serial_number, 999999) asc')
            ->orderBy(User::select('name')->whereColumn('users.id','teachers.user_id'))
            ->get(['id','user_id','school_id','designation','serial_number','initials']);
        return view('principal.routines.teacher_panel', compact('school','teachers'));
    }

    public function master(Request $request, School $school)
    {
        $days = ['saturday'=>'শনিবার','sunday'=>'রবিবার','monday'=>'সোমবার','tuesday'=>'মঙ্গলবার','wednesday'=>'বুধবার','thursday'=>'বৃহস্পতিবার','friday'=>'শুক্রবার'];
        $selectedDay = $request->get('day_of_week', 'saturday');
        if(!array_key_exists($selectedDay, $days)) $selectedDay = 'saturday';

        $teachers = Teacher::where('school_id', $school->id)
            ->with('user:id,name')
            ->orderByRaw('COALESCE(serial_number, 999999) asc')
            ->orderBy(User::select('name')->whereColumn('users.id','teachers.user_id'))
            ->get();

        $maxPeriod = RoutineEntry::forSchool($school->id)->where('day_of_week', $selectedDay)->max('period_number') ?? 0;

        $entries = RoutineEntry::forSchool($school->id)->where('day_of_week', $selectedDay)
            ->with(['subject:id,name','class:id,name','section:id,name'])
            ->get()
            ->groupBy(fn($e)=>$e->teacher_id.'#'.$e->period_number);

        return view('principal.routines.master', compact('school','days','selectedDay','teachers','maxPeriod','entries'));
    }

    public function masterAll(School $school)
    {
        $days = ['saturday'=>'শনিবার','sunday'=>'রবিবার','monday'=>'সোমবার','tuesday'=>'মঙ্গলবার','wednesday'=>'বুধবার','thursday'=>'বৃহস্পতিবার','friday'=>'শুক্রবার'];

        // Get all teachers
        $allTeachers = Teacher::where('school_id', $school->id)
            ->with('user:id,name')
            ->orderByRaw('COALESCE(serial_number, 999999) asc')
            ->orderBy(User::select('name')->whereColumn('users.id','teachers.user_id'))
            ->get();

        // Get all routine entries
        $allEntries = RoutineEntry::forSchool($school->id)
            ->with(['subject:id,name','class:id,name','section:id,name'])
            ->get();
            
        $maxPeriod = $allEntries->max('period_number') ?? 0;

        $entries = $allEntries->groupBy(fn($e)=>$e->day_of_week.'#'.$e->teacher_id.'#'.$e->period_number);

        // Filter active days
        $activeDays = [];
        foreach($days as $dk => $dn){
            if($allEntries->where('day_of_week', $dk)->isNotEmpty()){
                $activeDays[$dk] = $dn;
            }
        }
        if(empty($activeDays)){
            $activeDays = $days;
        }

        // Filter teachers who actually have classes across the week to save space
        $teachers = $allTeachers->filter(function($t) use ($allEntries) {
            return $allEntries->where('teacher_id', $t->id)->isNotEmpty();
        });

        return view('principal.routines.master_all', compact('school','activeDays','teachers','maxPeriod','entries'));
    }

    public function masterPrint(Request $request, School $school)
    {
        $days = ['saturday'=>'শনিবার','sunday'=>'রবিবার','monday'=>'সোমবার','tuesday'=>'মঙ্গলবার','wednesday'=>'বুধবার','thursday'=>'বৃহস্পতিবার','friday'=>'শুক্রবার'];
        $selectedDay = $request->get('day_of_week', 'saturday');
        if(!array_key_exists($selectedDay, $days)) $selectedDay = 'saturday';

        $teachers = Teacher::where('school_id', $school->id)
            ->with('user:id,name')
            ->orderByRaw('COALESCE(serial_number, 999999) asc')
            ->orderBy(User::select('name')->whereColumn('users.id','teachers.user_id'))
            ->get();

        $maxPeriod = RoutineEntry::forSchool($school->id)->where('day_of_week', $selectedDay)->max('period_number') ?? 0;

        $entries = RoutineEntry::forSchool($school->id)->where('day_of_week', $selectedDay)
            ->with(['subject:id,name','class:id,name','section:id,name'])
            ->get()
            ->groupBy(fn($e)=>$e->teacher_id.'#'.$e->period_number);

        return view('principal.routines.master_print', compact('school','days','selectedDay','teachers','maxPeriod','entries'));
    }

    public function subjects(Request $request, School $school)
    {
        try {
            $classId = (int)$request->get('class_id');
            if (!$classId) return response()->json([]);
            // First try via Eloquent to avoid SQL mode GROUP BY issues
            $mappings = ClassSubject::where('school_id',$school->id)
                ->where('class_id',$classId)
                ->with('subject:id,name,bangla_name')
                ->get();
            $subjects = $mappings->pluck('subject')->filter()->unique('id')->values();
            if ($subjects->isNotEmpty()) {
                $sorted = $subjects->sortBy('name')->values()->map(fn($s)=>['subject_id'=>$s->id,'name'=>$s->name,'bangla_name'=>$s->bangla_name]);
                return response()->json($sorted);
            }
            // Fallback to join only if above empty (unlikely)
            $q = ClassSubject::where('class_id',$classId)
                ->join('subjects','subjects.id','=','class_subjects.subject_id')
                ->select(['subjects.id as subject_id','subjects.name','subjects.bangla_name'])
                ->groupBy('subjects.id','subjects.name','subjects.bangla_name')
                ->orderBy('subjects.name');
            $rows = $q->get();
            if ($rows->isNotEmpty()) return response()->json($rows);
            // Final fallback: all subjects of the school
            $fallback = Subject::where('school_id',$school->id)->orderBy('name')->get(['id as subject_id','name','bangla_name']);
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

    /**
     * Start/end time per period_number for a class+section — set once,
     * reused across all seven days so the principal doesn't retype the same
     * time in every day×period cell.
     */
    public function periodTimes(Request $request, School $school)
    {
        try {
            $classId = (int)$request->get('class_id');
            $sectionId = (int)$request->get('section_id');
            if (!$classId || !$sectionId) return response()->json([]);
            $times = ClassPeriodTime::forSchool($school->id)->forClassSection($classId,$sectionId)->get();
            $map = [];
            foreach ($times as $t) {
                $map[$t->period_number] = ['start_time'=>$t->start_time, 'end_time'=>$t->end_time];
            }
            return response()->json($map);
        } catch (\Throwable $e) {
            return response()->json([], 200);
        }
    }

    public function setPeriodTimes(Request $request, School $school)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'times' => 'required|array',
            'times.*.period_number' => 'required|integer|min:1|max:32',
            'times.*.start_time' => 'nullable|string',
            'times.*.end_time' => 'nullable|string',
        ]);
        try {
            foreach ($data['times'] as $row) {
                ClassPeriodTime::updateOrCreate([
                    'school_id' => $school->id,
                    'class_id' => $data['class_id'],
                    'section_id' => $data['section_id'],
                    'period_number' => $row['period_number'],
                ], [
                    'start_time' => $row['start_time'] ?: null,
                    'end_time' => $row['end_time'] ?: null,
                ]);
            }
            return response()->json(['success'=>true]);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'error'=>'server']);
        }
    }

    /**
     * Duplicate every entry from one day onto one or more other days for the
     * same class+section — replaces whatever was already on the target days.
     */
    public function copyDay(Request $request, School $school)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'source_day' => 'required|string|max:16',
            'target_days' => 'required|array|min:1',
            'target_days.*' => 'string|max:16',
        ]);

        $sourceEntries = RoutineEntry::forSchool($school->id)
            ->forClassSection($data['class_id'], $data['section_id'])
            ->where('day_of_week', $data['source_day'])
            ->get();

        if ($sourceEntries->isEmpty()) {
            return response()->json(['success'=>false,'error'=>'উৎস দিনে কোনো এন্ট্রি নেই']);
        }

        $targetDays = array_diff($data['target_days'], [$data['source_day']]);
        foreach ($targetDays as $day) {
            RoutineEntry::forSchool($school->id)
                ->forClassSection($data['class_id'], $data['section_id'])
                ->where('day_of_week', $day)
                ->delete();

            foreach ($sourceEntries as $e) {
                RoutineEntry::create([
                    'school_id' => $school->id,
                    'class_id' => $data['class_id'],
                    'section_id' => $data['section_id'],
                    'day_of_week' => $day,
                    'period_number' => $e->period_number,
                    'subject_id' => $e->subject_id,
                    'teacher_id' => $e->teacher_id,
                    'start_time' => $e->start_time,
                    'end_time' => $e->end_time,
                    'room' => $e->room,
                    'remarks' => $e->remarks,
                ]);
            }
        }

        return response()->json(['success'=>true, 'copied_days'=>array_values($targetDays)]);
    }

    public function grid(Request $request, School $school)
    {
        try {
            $classId = (int)$request->get('class_id');
            $sectionId = (int)$request->get('section_id');
            if(!$classId || !$sectionId) return response()->json([]);
            $entries = RoutineEntry::forSchool($school->id)
                ->forClassSection($classId,$sectionId)
                ->with(['subject','teacher.user'])
                ->get();
            $grid = [];
            foreach ($entries as $e) {
                $day = $e->day_of_week; $pn = (int)$e->period_number;
                $grid[$day][$pn][] = [
                    'id' => $e->id,
                    'subject_id' => $e->subject_id,
                    'subject_name' => $e->subject?->name,
                    'teacher_id' => $e->teacher_id,
                    'teacher_name' => optional(optional($e->teacher)->user)->name,
                    'start_time' => $e->start_time,
                    'end_time' => $e->end_time,
                    'room' => $e->room,
                    'remarks' => $e->remarks,
                    'period_number' => $pn,
                    'day_of_week' => $day,
                ];
            }
            return response()->json($grid);
        } catch (\Throwable $e) {
            // Avoid breaking the UI; return empty grid on server errors
            return response()->json([], 200);
        }
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
        
        // Validate teacher as Teacher record ID
        $teacher = Teacher::where('school_id', $school->id)
            ->where('id', $data['teacher_id'])
            ->first();
        if(!$teacher) return response()->json(['success'=>false,'error'=>'Teacher record not found']);
        
        // Fall back to the class+section's saved period-time template when the
        // client didn't send an explicit time, so times don't need retyping.
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;
        if (!$startTime && !$endTime) {
            $template = ClassPeriodTime::forSchool($school->id)
                ->forClassSection($data['class_id'], $data['section_id'])
                ->where('period_number', $data['period_number'])
                ->first();
            if ($template) {
                $startTime = $startTime ?: $template->start_time;
                $endTime = $endTime ?: $template->end_time;
            }
        }

        $payload = [
            'school_id'=>$school->id,
            'class_id'=>$data['class_id'],
            'section_id'=>$data['section_id'],
            'day_of_week'=>$data['day_of_week'],
            'period_number'=>$data['period_number'],
            'subject_id'=>$data['subject_id'],
            'teacher_id'=>$teacher->id,
            'start_time'=>$startTime,
            'end_time'=>$endTime,
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
            ->with(['subject:id,name,bangla_name','teacher.user:id,name'])
            ->get()
            ->groupBy(fn($e)=>$e->day_of_week.'#'.$e->period_number);

        $lang = $request->get('lang') === 'en' ? 'en' : 'bn';
        $days = $lang === 'en'
            ? ['saturday'=>'Saturday','sunday'=>'Sunday','monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday']
            : ['saturday'=>'শনিবার','sunday'=>'রবিবার','monday'=>'সোমবার','tuesday'=>'মঙ্গলবার','wednesday'=>'বুধবার','thursday'=>'বৃহস্পতিবার','friday'=>'শুক্রবার'];

        return view('principal.routines.print', compact('school','class','section','periodCount','entries','days','lang'));
    }

    public function teacherPrintView(Request $request, School $school)
    {
        $teacherId = (int)$request->get('teacher_id');
        if(!$teacherId){
            return redirect()->route('principal.institute.routine.panel',$school)->with('error','প্রিন্টের জন্য আগে শিক্ষক নির্বাচন করুন');
        }
        $teacher = Teacher::where('school_id', $school->id)->with('user')->find($teacherId);
        if(!$teacher){
            return redirect()->route('principal.institute.routine.panel',$school)->with('error','সঠিক শিক্ষক পাওয়া যায়নি');
        }
        
        // Find max period number for the teacher to build a dynamic grid
        $maxPeriod = RoutineEntry::forSchool($school->id)->where('teacher_id', $teacherId)->max('period_number') ?? 0;
        
        $entries = RoutineEntry::forSchool($school->id)->where('teacher_id', $teacherId)
            ->with(['subject:id,name','class:id,name','section:id,name'])
            ->get()
            ->groupBy(fn($e)=>$e->day_of_week.'#'.$e->period_number);
        
        $days = ['saturday'=>'শনিবার','sunday'=>'রবিবার','monday'=>'সোমবার','tuesday'=>'মঙ্গলবার','wednesday'=>'বুধবার','thursday'=>'বৃহস্পতিবার','friday'=>'শুক্রবার'];
        return view('principal.routines.teacher_print', compact('school','teacher','maxPeriod','entries','days'));
    }
}
