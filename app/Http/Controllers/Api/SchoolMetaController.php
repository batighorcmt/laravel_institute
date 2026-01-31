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
        return $request->query('school_id') ?? $request->attributes->get('current_school_id');
    }

    public function classes(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->resolveSchoolId($request);

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $classes = SchoolClass::forSchool($schoolId)->active()->ordered()->get(['id','name']);

        return response()->json($classes->map(fn($c)=>['id'=>$c->id,'name'=>$c->name])->values());
    }

    public function sections(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->resolveSchoolId($request);
        $classId = $request->get('class_id');

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Section::where('school_id', $schoolId);
        if ($classId) {
            $query->whereHas('enrollments', function($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        $sections = $query->orderBy('name')->get(['id','name']);
        return response()->json($sections->map(fn($s)=>['id'=>$s->id,'name'=>$s->name])->values());
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
            ];
        })->values();

        return response()->json($out);
    }
}
