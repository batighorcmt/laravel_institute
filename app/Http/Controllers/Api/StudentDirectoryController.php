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

        $enroll = StudentEnrollment::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active');

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
                      $qs->where('full_name', 'like', "%$s%")
                         ->orWhere('phone', 'like', "%$s%");
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
}
