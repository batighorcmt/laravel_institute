<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RoutineEntry;
use App\Models\Subject;

class TeacherSubjectController extends Controller
{
    public function forClassSection(Request $request)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('current_school_id');
        if (! $user->isTeacher($schoolId) || ! $user->teacher) {
            return response()->json(['message' => 'শুধুমাত্র শিক্ষক'], 403);
        }

        $classId = (int) $request->query('class_id');
        $sectionId = (int) $request->query('section_id');
        if (! $classId || ! $sectionId) {
            return response()->json(['message' => 'class_id এবং section_id প্রয়োজন'], 422);
        }

        $teacherId = $user->teacher->id;

        // Subjects taught by this teacher for the given class/section via routine entries
        $subjectIds = RoutineEntry::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->distinct()
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();

        if ($subjectIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get(['id','name']);
        return response()->json([
            'data' => $subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values(),
        ]);
    }
}
