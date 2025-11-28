<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Http\Resources\TeacherDirectoryResource;

class TeacherDirectoryController extends Controller
{
    // GET /v1/teachers
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $schoolId) {
            $schoolId = method_exists($user,'firstTeacherSchoolId') ? $user->firstTeacherSchoolId() : null;
        }
        if (! $schoolId) {
            return response()->json(['message' => 'স্কুল শনাক্ত হয়নি'], 422);
        }

        // Allow teacher or principal roles only
        if (! ($user->isTeacher($schoolId) || $user->isPrincipal($schoolId) || $user->isSuperAdmin())) {
            return response()->json(['message' => 'অননুমোদিত'], 403);
        }

        $query = Teacher::query()->forSchool($schoolId)->active();
        // Optional filters
        if ($request->filled('designation')) {
            $query->where('designation', $request->get('designation'));
        }
        if ($request->filled('search')) {
            $s = trim($request->get('search'));
            $query->where(function($q) use ($s) {
                $q->where('first_name','like',"%$s%")
                  ->orWhere('last_name','like',"%$s%")
                  ->orWhere('phone','like',"%$s%");
            });
        }
        $teachers = $query->orderBy('serial_number')->orderBy('id')->limit(500)->get();

        return TeacherDirectoryResource::collection($teachers)->additional([
            'count' => $teachers->count(),
            'school_id' => $schoolId,
        ]);
    }
}
