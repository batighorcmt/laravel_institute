<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\SchoolClass;
use App\Models\ExtraClass;
use App\Models\Team;
use App\Models\Teacher;
use App\Models\StudentEnrollment;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

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

    public function classSectionStudents(Request $request, Section $section)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user, $section->school_id);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        if (! $teacher || (int)$section->class_teacher_id !== (int)$teacher->id) {
            return response()->json(['message' => 'আপনি এই শাখার শ্রেণিশিক্ষক নন'], 403);
        }

        $date = $request->query('date', now()->toDateString());

        $enrollments = StudentEnrollment::where([
                'school_id' => $schoolId,
                'class_id' => $section->class_id,
                'section_id' => $section->id,
                'status' => 'active',
            ])
            ->with(['student'])
            ->orderBy('roll_no')
            ->get();

        $existing = Attendance::where('section_id', $section->id)
            ->whereDate('date', $date)
            ->pluck('status','student_id');

        $students = $enrollments->map(function($en) use ($existing) {
            $st = $en->student;
            return [
                'id' => $st?->id,
                'name' => $st?->full_name,
                'roll' => $en->roll_no,
                'photo_url' => $st?->photo_url,
                'status' => $existing[$st?->id] ?? null,
            ];
        })->values();

        return response()->json([
            'date' => $date,
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'class_id' => $section->class_id,
                'class_name' => $section->schoolClass?->name,
            ],
            'students' => $students,
        ]);
    }

    public function classSectionSubmit(Request $request, Section $section)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user, $section->school_id);
        if (! $schoolId) return response()->json(['message'=>'School context unavailable'], 422);

        $teacher = Teacher::where('user_id',$user->id)->where('school_id',$schoolId)->first();
        if (! $teacher || (int)$section->class_teacher_id !== (int)$teacher->id) {
            return response()->json(['message' => 'আপনি এই শাখার শ্রেণিশিক্ষক নন'], 403);
        }

        $data = $request->validate([
            'date' => ['required','date_format:Y-m-d'],
            'items' => ['required','array','min:1'],
            'items.*.student_id' => ['required','integer'],
            'items.*.status' => ['required','in:present,absent,late'],
        ]);

        $date = $data['date'];
        $items = collect($data['items']);

        // Fetch active students for the section to validate completeness
        $sectionStudentIds = StudentEnrollment::where([
                'school_id' => $schoolId,
                'class_id' => $section->class_id,
                'section_id' => $section->id,
                'status' => 'active',
            ])->pluck('student_id')->values();

        $submittedIds = $items->pluck('student_id')->values();
        $missing = $sectionStudentIds->diff($submittedIds);
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'সকল শিক্ষার্থীর স্ট্যাটাস নির্বাচন করা হয়নি',
                'missing_count' => $missing->count(),
            ], 422);
        }

        DB::transaction(function() use ($items, $date, $section, $user) {
            foreach ($items as $it) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $it['student_id'],
                        'date' => $date,
                        'class_id' => $section->class_id,
                        'section_id' => $section->id,
                    ],
                    [
                        'status' => $it['status'],
                        'recorded_by' => $user->id,
                    ]
                );
            }
        });

        return response()->json(['message' => 'উপস্থিতি সফলভাবে সংরক্ষিত হয়েছে']);
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
