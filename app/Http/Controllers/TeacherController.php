<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\RoutineEntry;
use App\Models\AcademicYear;
use App\Models\Homework;
use App\Models\LessonEvaluation;
use Carbon\Carbon;

class TeacherController extends Controller
{
    /**
     * Show teacher dashboard.
     */
     public function dashboard(Request $request)
     {
         $user = Auth::user();
         /** @var User $user */

         $school = $user->primarySchool();
         $currentYear = AcademicYear::forSchool($school->id ?? 0)->current()->first();

         // Assigned classes
         $assignedClasses = collect();
         if ($school) {
             $assignedClasses = SchoolClass::where('school_id', $school->id)
                 ->where('class_teacher_id', $user->id)
                 ->with('school')
                 ->get();
         }

         // Today's routine
         $todayRoutine = collect();
         if ($school && $currentYear) {
             $today = Carbon::now()->format('l'); // e.g., Monday
             $todayRoutine = RoutineEntry::where('school_id', $school->id)
                 ->where('academic_year_id', $currentYear->id)
                 ->where('day_of_week', $today)
                 ->where('teacher_id', $user->id)
                 ->with(['class', 'subject'])
                 ->orderBy('start_time')
                 ->get();
         }

         // Pending tasks: lesson evaluations to submit
         $pendingEvaluations = LessonEvaluation::where('teacher_id', $user->id)
             ->where('status', 'pending')
             ->count();

         return view('teacher.dashboard', compact(
             'school',
             'assignedClasses',
             'todayRoutine',
             'pendingEvaluations'
         ));
     }
}
