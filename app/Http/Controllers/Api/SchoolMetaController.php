<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Teacher;

class SchoolMetaController extends Controller
{
    protected function resolveSchoolId(Request $request)
    {
        return $request->query('school_id') ??
               $request->attributes->get('current_school_id') ??
               $request->user()->primarySchool()?->id;
    }

    public function classes(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->resolveSchoolId($request);

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $classes = SchoolClass::forSchool($schoolId)->active()->ordered()->get(['id','name','bangla_name']);
        
        return response()->json($classes->map(fn($c)=>['id'=>$c->id,'name'=>$c->name,'bangla_name'=>$c->bangla_name])->values());
    }

    public function sections(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->resolveSchoolId($request);
        $classId = $request->get('class_id');

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Section::where('sections.school_id', $schoolId)->where('sections.status', 'active');
        if ($classId) {
            $query->where('sections.class_id', $classId);
        }

        $sections = $query->with('class:id,name,bangla_name')->ordered()->get(['sections.id','sections.name','sections.bangla_name','sections.class_id']);

        return response()->json($sections->map(fn($s)=>[
            'id'=>$s->id,
            'name'=>$s->name,
            'bangla_name'=>$s->bangla_name,
            'class_id'=>$s->class_id,
            'class_name'=>$s->class?->name,
            'class_bangla_name'=>$s->class?->bangla_name
        ])->values());
    }

    public function groups(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->resolveSchoolId($request);

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $groups = \App\Models\Group::forSchool($schoolId)->where('status', 'active')->get(['id','name']);
        return response()->json($groups);
    }

    public function subjects(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->resolveSchoolId($request);
        $classId = $request->get('class_id');

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($classId) {
            $subjects = \App\Models\Subject::whereHas('classMappings', function($q) use ($classId) {
                    $q->where('class_id', $classId)->where('status', 'active');
                })
                ->join('class_subjects', 'subjects.id', '=', 'class_subjects.subject_id')
                ->where('class_subjects.class_id', $classId)
                ->select('subjects.id', 'subjects.name')
                ->orderByRaw('COALESCE(class_subjects.order_no, 9999)')
                ->orderBy('subjects.name')
                ->get();
        } else {
            $subjects = \App\Models\Subject::forSchool($schoolId)->active()->orderBy('name')->get(['id','name']);
        }

        return response()->json($subjects);
    }

    public function teachers(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->resolveSchoolId($request);

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teachers = Teacher::forSchool($schoolId)->active()->with('user')->orderBy('serial_number')->get();

        $out = $teachers->map(function($t){
            return [
                'id' => $t->id,
                'user_id' => $t->user_id,
                'name' => $t->user?->name ?? $t->full_name,
                'designation' => $t->designation,
            ];
        })->values();

        return response()->json($out);
    }

    public function school(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        $school = \App\Models\School::find($schoolId, ['id', 'name', 'name_bn', 'address', 'address_bn', 'logo', 'phone', 'email']);

        if ($school && $school->logo) {
            $school->logo_url = asset('storage/' . $school->logo);
        }

        // Include list of academic years for the school, with current marker
        $years = \App\Models\AcademicYear::forSchool($schoolId)
                    ->orderByDesc('start_date')
                    ->get(['id','name','name_bn','start_date','end_date','is_current']);

        $outYears = $years->map(function($y){
            return [
                'id' => $y->id,
                'name' => $y->name,
                'name_bn' => $y->name_bn,
                'start_date' => $y->start_date?->toDateString(),
                'end_date' => $y->end_date?->toDateString(),
                'is_current' => (bool)$y->is_current,
            ];
        })->values();

        $current = $years->firstWhere('is_current', true);

        $payload = $school ? $school->toArray() : null;
        $payload = array_merge($payload ?? [], [
            'academic_years' => $outYears,
            'current_academic_year' => $current ? [ 'id'=>$current->id, 'name'=>$current->name, 'name_bn'=>$current->name_bn ] : null
        ]);

        return response()->json($payload);
    }
}
