<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\SchoolClass;
use App\Models\ExtraClass;
use App\Models\Team;
use App\Models\Teacher;

class TeacherStudentAttendanceController extends Controller
{
    public function modules(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        $classEnabled = $teacher ? Section::where('school_id',$schoolId)->where('class_teacher_id',$teacher->id)->where('status','active')->exists() : false;
        // ExtraClass model links teacher_id to users.id
        $extraEnabled = ExtraClass::where('school_id',$schoolId)->where('status','active')->where('teacher_id',$user->id)->exists();
        $teamEnabled = Team::forSchool($schoolId)->active()->exists();

        return response()->json([
            'class_attendance' => $classEnabled,
            'extra_class_attendance' => $extraEnabled,
            'team_attendance' => $teamEnabled,
        ]);
    }

    public function classMeta(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        if (! $teacher) return response()->json(['data'=>[]]);

        $sections = Section::where('school_id',$schoolId)
            ->where('class_teacher_id',$teacher->id)
            ->where('status','active')
            ->with(['schoolClass:id,name'])
            ->orderBy('class_id')
            ->orderBy('name')
            ->get(['id','name','class_id']);

        $byClass = [];
        foreach ($sections as $sec) {
            $cid = $sec->class_id;
            if (!isset($byClass[$cid])) {
                $byClass[$cid] = [
                    'class_id' => $cid,
                    'class_name' => $sec->schoolClass?->name,
                    'sections' => [],
                ];
            }
            $byClass[$cid]['sections'][] = ['id'=>$sec->id,'name'=>$sec->name];
        }
        return response()->json(array_values($byClass));
    }

    public function extraMeta(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);
        $rows = ExtraClass::where('school_id',$schoolId)
            ->where('status','active')
            ->where('teacher_id',$user->id)
            ->with(['schoolClass:id,name','section:id,name','subject:id,name'])
            ->orderByDesc('id')
            ->get(['id','class_id','section_id','subject_id','name','schedule']);
        $data = $rows->map(fn($r)=>[
            'id'=>$r->id,
            'name'=>$r->name,
            'schedule'=>$r->schedule,
            'class_name'=>$r->schoolClass?->name,
            'section_name'=>$r->section?->name,
            'subject_name'=>$r->subject?->name,
        ])->values();
        return response()->json($data);
    }

    public function teamMeta(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);
        $teams = Team::forSchool($schoolId)->active()->orderBy('name')->get(['id','name','type']);
        return response()->json($teams);
    }

    protected function resolveSchoolId(Request $request, $user, $explicit = null): ?int
    {
        if ($explicit) return (int)$explicit;
        $attr = $request->attributes->get('current_school_id');
        if ($attr) return (int)$attr;
        $firstActive = $user->firstTeacherSchoolId();
        if ($firstActive) return (int)$firstActive;
        $any = $user->schoolRoles()->whereHas('role', fn($q)=>$q->where('name','teacher'))->value('school_id');
        return $any ? (int)$any : null;
    }
}
