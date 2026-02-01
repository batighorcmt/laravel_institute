<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;

class DebugController extends Controller
{
    /**
     * Return active classes for a given school_id. Query param: school_id
     */
    public function classes(Request $request)
    {
        $schoolId = $request->query('school_id') ?? $request->input('school_id');
        if (! $schoolId) {
            return response()->json(['error' => 'school_id is required'], 400);
        }

        $classes = SchoolClass::forSchool($schoolId)
            ->active()
            ->ordered()
            ->get(['id', 'name']);

        return response()->json($classes);
    }

    /**
     * Debug: return sections for a given school_id and optional class_id
     */
    public function sections(Request $request)
    {
        $schoolId = $request->query('school_id') ?? $request->input('school_id');
        $classId = $request->query('class_id') ?? $request->input('class_id');

        if (! $schoolId) {
            return response()->json(['error' => 'school_id is required'], 400);
        }

        $q = \App\Models\Section::where('school_id', $schoolId);
        if ($classId) {
            $q->whereHas('enrollments', function($q2) use ($classId) {
                $q2->where('class_id', $classId);
            });
        }

        $sections = $q->orderBy('name')->get(['id','name']);
        return response()->json($sections);
    }

    /**
     * Debug: return subjects for a given school_id and optional class_id/section_id
     */
    public function subjects(Request $request)
    {
        $schoolId = $request->query('school_id') ?? $request->input('school_id');
        $classId = $request->query('class_id') ?? $request->input('class_id');
        $sectionId = $request->query('section_id') ?? $request->input('section_id');

        if (! $schoolId) {
            return response()->json(['error' => 'school_id is required'], 400);
        }

        if ($classId && $sectionId) {
            $subjectIds = \App\Models\RoutineEntry::query()
                ->where('school_id', $schoolId)
                ->when($classId, fn($q)=>$q->where('class_id', $classId))
                ->when($sectionId, fn($q)=>$q->where('section_id', $sectionId))
                ->distinct()->pluck('subject_id')->filter()->unique()->values();

            if ($subjectIds->isEmpty()) {
                return response()->json([]);
            }

            $subjects = \App\Models\Subject::whereIn('id', $subjectIds)->orderBy('name')->get(['id','name']);
        } else {
            $subjects = \App\Models\Subject::forSchool($schoolId)->active()->orderBy('name')->get(['id','name']);
        }

        return response()->json($subjects->map(fn($s)=>['id'=>$s->id,'name'=>$s->name])->values());
    }

    /**
     * Debug: return teachers for a given school_id
     */
    public function teachers(Request $request)
    {
        $schoolId = $request->query('school_id') ?? $request->input('school_id');
        if (! $schoolId) {
            return response()->json(['error' => 'school_id is required'], 400);
        }

        $teachers = \App\Models\Teacher::where('school_id', $schoolId)->active()->with('user')->orderBy('serial_number')->get();
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
