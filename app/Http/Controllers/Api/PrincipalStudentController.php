<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        
        // Robust school_id resolution
        $schoolId = $request->attributes->get('current_school_id');
        if (!$schoolId) {
            $primary = $user->primarySchool();
            $schoolId = $primary?->id;
        }
        if (!$schoolId) {
            $schoolId = $user->activeSchoolRoles()->value('school_id');
        }

        // Allow principals, teachers or super admins
        if (!$user->isSuperAdmin() && !$schoolId) {
            return response()->json(['message' => 'School context not found'], 403);
        }

        $query = $request->get('q');
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $rollNo = $request->get('roll_no');
        $limit = min((int)$request->get('limit', 50), 500);

        // If no filters provided, return empty list
        if (empty($query) && empty($classId) && empty($rollNo)) {
            return response()->json([]);
        }

        $students = Student::forSchool($schoolId)->active()
            ->when($query, function($q) use ($query) {
                $q->where(function($inner) use ($query) {
                    $inner->where('student_name_en', 'like', "%{$query}%")
                        ->orWhere('student_name_bn', 'like', "%{$query}%")
                        ->orWhere('student_id', 'like', "%{$query}%")
                        ->orWhere('father_name', 'like', "%{$query}%")
                        ->orWhere('guardian_phone', 'like', "%{$query}%");
                });
            })
            ->when($classId || $sectionId || $rollNo, function($q) use ($classId, $sectionId, $rollNo) {
                $q->whereHas('enrollments', function($en) use ($classId, $sectionId, $rollNo) {
                    $en->where('status', 'active');
                    if ($classId) $en->where('class_id', (int)$classId);
                    if ($sectionId) $en->where('section_id', (int)$sectionId);
                    if ($rollNo) $en->where('roll_no', $rollNo);
                });
            })
            ->with(['enrollments' => function($en) {
                $en->where('status', 'active')->with(['class', 'section', 'group']);
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
                    'photo_url' => $student->photo_url,
                    'class_name' => $enrollment?->class?->name,
                    'section_name' => $enrollment?->section?->name,
                    'group_name' => $enrollment?->group?->name,
                    'roll_no' => $enrollment?->roll_no,
                    'roll_no_int' => $enrollment?->roll_no ? (int)$enrollment->roll_no : 9999,
                    'status' => $student->status,
                ];
            })
            ->sortBy('roll_no_int')
            ->values();

        return response()->json($students);
    }

    /**
     * Get sections for a specific class
     */
    public function getSections(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->firstTeacherSchoolId();
        $classId = $request->get('class_id');

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $sections = Section::where('school_id', $schoolId)->where('status', 'active');

        if ($classId) {
            $sections->where('class_id', $classId);
        }

        $sections = $sections->orderBy('name')->get(['id', 'name']);

        return response()->json($sections);
    }

    /**
     * Get groups for a specific class
     */
    public function getGroups(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->firstTeacherSchoolId();
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->firstTeacherSchoolId();

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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->firstTeacherSchoolId();

        if (! ($user->isPrincipal($schoolId) || $user->isTeacher($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');

        // If class+section provided, prefer subjects assigned via routine entries for that combination
        if ($classId && $sectionId) {
            $subjectIds = \App\Models\RoutineEntry::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->distinct()->pluck('subject_id')->filter()->unique()->values();

            if ($subjectIds->isEmpty()) {
                // fallback to class mappings
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
                $subjects = \App\Models\Subject::whereIn('id', $subjectIds)->active()->orderBy('name')->get(['id','name']);
            }
        } elseif ($classId) {
            // If only class is provided, get subjects mapped to that class with proper ordering
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

        return response()->json($subjects->map(fn($s)=>['id'=>$s->id,'name'=>$s->name])->values());
    }
    public function show(Request $request, int $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $schoolId = $request->attributes->get('current_school_id') ?? 
                   $user->primarySchool()?->id ?? 
                   $user->activeSchoolRoles()->first()?->school_id;

        $student = \App\Models\Student::where('school_id', $schoolId)->findOrFail($id);
        $enrollment = $student->currentEnrollment()->with(['class', 'section'])->first();

        return response()->json([
            'id' => $student->id,
            'student_id' => $student->student_id,
            'name_en' => $student->student_name_en,
            'name_bn' => $student->student_name_bn,
            'photo_url' => $student->photo_url,
            'class_name' => $enrollment?->class?->name,
            'section_name' => $enrollment?->section?->name,
            'roll_no' => $enrollment?->roll_no,
        ]);
    }
}
