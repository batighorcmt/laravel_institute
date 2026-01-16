<?php

namespace App\Http\Controllers\Teacher\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\SchoolClass;
use App\Models\RoutineEntry;

class CollectController extends Controller
{
    public function create(Request $request, \App\Models\School $school)
    {
        $user = $request->user();
        
        // Get the teacher record for this user and school
        $teacher = Teacher::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        // Get classes where this teacher is assigned as class teacher via sections
        $sections = Section::where('school_id', $school->id)
            ->where('class_teacher_id', $teacher->id)
            ->where('status', 'active')
            ->with('schoolClass')
            ->get();

        // Get unique class IDs from sections
        $classIdsFromSections = $sections->pluck('class_id')->unique();

        // Also get classes where teacher teaches via routine entries
        $routineClasses = RoutineEntry::where('school_id', $school->id)
            ->where('teacher_id', $teacher->id)
            ->distinct()
            ->pluck('class_id')
            ->unique();

        // Combine both sources and get unique class IDs
        $allClassIds = $classIdsFromSections->merge($routineClasses)->unique();

        // Fetch the actual class models
        $classes = SchoolClass::forSchool($school->id)
            ->active()
            ->whereIn('id', $allClassIds)
            ->ordered()
            ->get();

        // Group sections by class for easier access in the view
        $sectionsByClass = $sections->groupBy('class_id');

        return view('billing.collect', [
            'school' => $school,
            'teacher' => $teacher,
            'classes' => $classes,
            'sectionsByClass' => $sectionsByClass,
        ]);
    }
}
