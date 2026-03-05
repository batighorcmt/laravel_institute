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
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        // Determine academic year: explicit query param or current academic year
        $yearId = (int)($request->query('academic_year_id', 0));
        if (! $yearId) {
            $yearId = (int)(\App\Models\AcademicYear::forSchool($schoolId)->current()->value('id') ?? 0);
        }

        $enroll = StudentEnrollment::query()
            ->where('student_enrollments.school_id', $schoolId);

        // Status filter (default to 'active' on both student and enrollment tables)
        $status = $request->get('status', 'active');
        $enroll->where('student_enrollments.status', $status);
        $enroll->whereHas('student', function($q) use ($status) {
            $q->where('status', $status);
        });

        // Optional ordering by student columns (e.g. guardian_phone)
        $orderBy = $request->query('order_by');
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        if ($orderBy === 'guardian_phone' || $orderBy === 'phone') {
            // Join students table to allow ordering by guardian_phone while
            // still returning StudentEnrollment models. Select enrollment
            // columns explicitly to avoid column collisions.
            $enroll->leftJoin('students', 'student_enrollments.student_id', '=', 'students.id')
                ->select('student_enrollments.*')
                ->orderBy('students.guardian_phone', $direction);
        }

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

        if ($request->filled('religion')) {
            $enroll->whereHas('student', function($q) use ($request) {
                $q->where('religion', $request->get('religion'));
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
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        // Determine academic year: explicit query param or current academic year
        $yearId = (int)($request->query('academic_year_id', 0));
        if (! $yearId) {
            $yearId = (int)(\App\Models\AcademicYear::forSchool($schoolId)->current()->value('id') ?? 0);
        }

        // Load the student's enrollment for the current school and academic year (if present)
        $enrollmentQuery = StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->with(['class','section','group'])
            ->latest();

        if ($yearId) {
            $enrollmentQuery->where('academic_year_id', $yearId);
        }

        $en = $enrollmentQuery->first();

        // Attach the resolved enrollment to the student model so the resource can read it
        if ($en) {
            $student->setRelation('currentEnrollment', $en);
        } else {
            // Fallback: ensure relation exists but null to avoid undefined behaviour in resource
            $student->setRelation('currentEnrollment', null);
        }

        // Also eager-load primary student relations used by the resource
        $student->loadMissing([
            'class', 
            'optionalSubject',
            'enrollments.class', 
            'enrollments.section', 
            'enrollments.group', 
            'enrollments.academicYear',
            'teams' => function($q) {
                $q->withPivot('joined_at', 'status');
            }
        ]);

        // Calculate attendance stats for the current academic year or school
        $attendanceStats = [
            'present' => \App\Models\Attendance::where('student_id', $student->id)->where('status', 'present')->count(),
            'absent' => \App\Models\Attendance::where('student_id', $student->id)->where('status', 'absent')->count(),
            'late' => \App\Models\Attendance::where('student_id', $student->id)->where('status', 'late',)->count(),
            'leave' => \App\Models\Attendance::where('student_id', $student->id)->where('status', 'leave')->count(),
        ];
        $student->setAttribute('attendance_stats', $attendanceStats);
        $student->setAttribute('working_days', array_sum($attendanceStats));

        return (new StudentProfileResource($student));
    }

    public function getClasses(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (!$schoolId) return response()->json([], 403);

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

    public function getSections(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        $classId = $request->get('class_id');
        if (!$schoolId) return response()->json([], 403);

        $sections = \App\Models\Section::where('school_id', $schoolId)->where('status', 'active');

        if ($classId) {
            $sections->where('class_id', $classId);
        }

        $sections = $sections->orderBy('name')->get(['id', 'name']);

        return response()->json($sections);
    }

    public function getGroups(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        $classId = $request->get('class_id');
        if (!$schoolId) return response()->json([], 403);

        if ($classId) {
            $groupIds = \App\Models\StudentEnrollment::where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->whereNotNull('group_id')
                ->where('status', 'active')
                ->distinct()
                ->pluck('group_id');

            $groups = \App\Models\Group::whereIn('id', $groupIds)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $groups = \App\Models\Group::forSchool($schoolId)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return response()->json($groups);
    }

    public function meta(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);
        if (! $schoolId || ! $user->isTeacher($schoolId)) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        $classes = $this->getClasses($request)->getData();
        $sections = $this->getSections($request)->getData();
        $groups = $this->getGroups($request)->getData();

        $genders = collect(['male', 'female', 'other']);

        // Additional filters
        $academicYears = \App\Models\AcademicYear::forSchool($schoolId)
            ->orderBy('name', 'desc')
            ->get(['id', 'name'])
            ->map(fn($y) => ['id' => (int)$y->id, 'name' => $y->name])->values();

        $religions = \App\Models\Student::forSchool($schoolId)
            ->whereNotNull('religion')
            ->distinct()
            ->orderBy('religion')
            ->pluck('religion')
            ->filter()
            ->values();
        if ($religions->isEmpty()) {
            $religions = collect(['Islam', 'Hindu', 'Buddhist', 'Christian', 'Other']);
        }

        $statuses = \App\Models\StudentEnrollment::forSchool($schoolId)
            ->distinct()
            ->pluck('status')
            ->filter()
            ->values();
        if ($statuses->isEmpty()) {
            $statuses = collect(['active', 'dropped', 'TC', 'inactive']);
        }

        return response()->json([
            'classes' => $classes,
            'sections' => $sections,
            'groups' => $groups,
            'genders' => $genders,
            'academic_years' => $academicYears,
            'religions' => $religions,
            'statuses' => $statuses,
        ]);
    }

    protected function resolveSchoolId(Request $request, $user): ?int
    {
        $attr = $request->attributes->get('current_school_id');
        if ($attr) return (int)$attr;
        $firstActive = $user->firstTeacherSchoolId();
        if ($firstActive) return (int)$firstActive;
        // Fallback to any school where they have a teacher role
        $any = $user->schoolRoles()->whereHas('role', fn($q)=>$q->where('name','teacher'))->value('school_id');
        return $any ? (int)$any : null;
    }
}
