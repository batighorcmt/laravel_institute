<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Models\Section;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrincipalStudentController extends Controller
{
    /**
     * Search students for autocomplete
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id');

        // Allow principals, teachers (for their school), or superadmins
        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = $request->get('q');
        $limit = $request->get('limit', 10);

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        $students = Student::forSchool($schoolId)
            ->where(function($q) use ($query) {
                $q->where('student_name_en', 'like', "%{$query}%")
                  ->orWhere('student_name_bn', 'like', "%{$query}%")
                  ->orWhere('student_id', 'like', "%{$query}%")
                  ->orWhere('father_name', 'like', "%{$query}%")
                  ->orWhere('mother_name', 'like', "%{$query}%")
                  ->orWhere('guardian_phone', 'like', "%{$query}%");
            })
            ->with(['enrollments' => function($en) {
                $en->with(['class', 'section', 'group']);
            }])
            ->limit($limit)
            ->get()
            ->map(function($student) {
                $enrollment = $student->enrollments->first();
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name_en' => $student->student_name_en,
                    'name_bn' => $student->student_name_bn,
                    'full_name' => $student->full_name,
                    'father_name' => $student->father_name,
                    'guardian_phone' => $student->guardian_phone,
                    'class_name' => $enrollment ? $enrollment->class->name : null,
                    'section_name' => $enrollment ? $enrollment->section->name : null,
                    'group_name' => $enrollment ? $enrollment->group->name : null,
                    'status' => $student->status,
                ];
            });

        return response()->json($students);
    }

    /**
     * Get sections for a specific class
     */
    public function getSections(Request $request)
    {
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id');
        $classId = $request->get('class_id');

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $sections = Section::where('school_id', $schoolId);

        if ($classId) {
            // Get sections that have enrollments for this class
            $sections->whereHas('enrollments', function($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        $sections = $sections->orderBy('name')->get(['id', 'name']);

        return response()->json($sections);
    }

    /**
     * Get groups for a specific class
     */
    public function getGroups(Request $request)
    {
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id');
        $classId = $request->get('class_id');

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $groups = Group::where('school_id', $schoolId);

        if ($classId) {
            // Get groups that have enrollments for this class
            $groups->whereHas('enrollments', function($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        $groups = $groups->orderBy('name')->get(['id', 'name']);

        return response()->json($groups);
    }

    /**
     * Get classes available in the school (for principal)
     */
    public function getClasses(Request $request)
    {
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id');

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $classes = \App\Models\SchoolClass::forSchool($schoolId)
            ->active()->ordered()
            ->get(['id','name','numeric_value'])
            ->map(fn($c)=>[
                'id' => (int)$c->id,
                'name' => $c->name,
                'numeric_value' => (int)$c->numeric_value,
            ])->values();

        return response()->json($classes);
    }

    /**
     * Get subjects for the school. Optionally filter by class_id/section_id when provided.
     */
    public function getSubjects(Request $request)
    {
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id');

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');

        // If class+section provided, prefer subjects assigned via routine entries for that combination
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
}
