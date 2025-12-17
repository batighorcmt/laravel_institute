<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Http\Resources\StudentDirectoryResource;
use App\Http\Resources\StudentProfileResource;

class StudentDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->firstTeacherSchoolId();
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        // Determine academic year: explicit query param or current academic year
        $yearId = (int)($request->query('academic_year_id', 0));
        if (! $yearId) {
            $yearId = (int)(\App\Models\AcademicYear::forSchool($schoolId)->current()->value('id') ?? 0);
        }

        $enroll = StudentEnrollment::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active');

        if ($yearId) {
            $enroll->where('academic_year_id', $yearId);
        }

        // Filters
        if ($request->filled('class_id')) {
            $enroll->where('class_id', (int)$request->get('class_id'));
        }
        if ($request->filled('section_id')) {
            $enroll->where('section_id', (int)$request->get('section_id'));
        }
        if ($request->filled('group_id')) {
            $enroll->where('group_id', (int)$request->get('group_id'));
        }
        if ($request->filled('gender')) {
            $enroll->whereHas('student', function($q) use ($request) {
                $q->where('gender', $request->get('gender'));
            });
        }
        if ($request->filled('search')) {
            $s = trim($request->get('search'));
            $enroll->where(function($q) use ($s) {
                $q->where('roll_no', 'like', "%$s%")
                  ->orWhere('student_id', 'like', "%$s%")
                  ->orWhereHas('student', function($qs) use ($s) {
                      // Search against actual columns on students table
                      $qs->where('student_name_bn', 'like', "%$s%")
                         ->orWhere('student_name_en', 'like', "%$s%")
                         ->orWhere('guardian_phone', 'like', "%$s%");
                  });
            });
        }

        $enroll->with(['student','class:id,name','section:id,name','group:id,name']);
        $enroll->orderBy('class_id')->orderBy('section_id')->orderBy('roll_no');
        $perPage = (int)($request->get('per_page', 40));
        if ($perPage < 10) $perPage = 40;
        if ($perPage > 200) $perPage = 200;

        $p = $enroll->paginate($perPage);
        $items = $p->getCollection()->map(function($en) {
            return StudentDirectoryResource::make($en)->resolve();
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $p->currentPage(),
                'last_page' => $p->lastPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
            ],
        ]);
    }

    public function show(Request $request, Student $student)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->firstTeacherSchoolId();
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        $student->load([
            'currentEnrollment.class','currentEnrollment.section','currentEnrollment.group',
        ]);
        return (new StudentProfileResource($student));
    }

    public function meta(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id') ?? $user->firstTeacherSchoolId();
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        $yearId = (int)($request->query('academic_year_id', 0));
        if (! $yearId) {
            $yearId = (int)(\App\Models\AcademicYear::forSchool($schoolId)->current()->value('id') ?? 0);
        }

        // Classes: all active classes ordered by numeric_value then name
        $classes = \App\Models\SchoolClass::forSchool($schoolId)
            ->active()->ordered()
            ->get(['id','name','numeric_value'])
            ->map(fn($c)=>[
                'id' => (int)$c->id,
                'name' => $c->name,
                'numeric_value' => (int)$c->numeric_value,
            ])->values();

        $classId = (int)($request->query('class_id', 0));
        $sections = collect();
        $groups = collect();
        $genders = collect();

        if ($classId) {
            // Sections: active for the class
            $sections = \App\Models\Section::where([
                    'school_id' => $schoolId,
                    'class_id' => $classId,
                    'status' => 'active',
                ])
                ->orderBy('name')
                ->get(['id','name'])
                ->map(fn($s)=>['id'=>(int)$s->id,'name'=>$s->name])->values();

            // Groups: those that actually exist in current year enrollments for the class
            $groupIds = \App\Models\StudentEnrollment::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->when($yearId, fn($q)=>$q->where('academic_year_id', $yearId))
                ->whereNotNull('group_id')
                ->where('status','active')
                ->distinct()->pluck('group_id');

            $groups = \App\Models\Group::whereIn('id', $groupIds)
                ->orderBy('name')
                ->get(['id','name'])
                ->map(fn($g)=>['id'=>(int)$g->id,'name'=>$g->name])->values();

            // Genders present in the selected class (current year)
            $genders = \App\Models\StudentEnrollment::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->when($yearId, fn($q)=>$q->where('academic_year_id', $yearId))
                ->where('status','active')
                ->whereHas('student', function($q){ $q->whereIn('gender',["male","female","other"]); })
                ->with(['student:id,gender'])
                ->get()->pluck('student.gender')->filter()->unique()->values();
        }

        return response()->json([
            'classes' => $classes,
            'sections' => $sections,
            'groups' => $groups,
            'genders' => $genders,
        ]);
    }
}
