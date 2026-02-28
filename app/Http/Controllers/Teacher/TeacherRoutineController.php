<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\RoutineEntry;
use Illuminate\Http\Request;

class TeacherRoutineController extends Controller
{
    public function printView(Request $request, School $school)
    {
        $user = auth()->user();
        $teacher = $user->teacher()->where('school_id', $school->id)->first();

        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error','শিক্ষক হিসেবে তথ্য পাওয়া যায়নি');
        }

        $teacherId = $teacher->id;
        
        $maxPeriod = RoutineEntry::forSchool($school->id)->where('teacher_id', $teacherId)->max('period_number') ?? 0;
        
        $entries = RoutineEntry::forSchool($school->id)->where('teacher_id', $teacherId)
            ->with(['subject:id,name','class:id,name','section:id,name'])
            ->get()
            ->groupBy(fn($e)=>$e->day_of_week.'#'.$e->period_number);
        
        $days = ['saturday'=>'শনিবার','sunday'=>'রবিবার','monday'=>'সোমবার','tuesday'=>'মঙ্গলবার','wednesday'=>'বুধবার','thursday'=>'বৃহস্পতিবার','friday'=>'শুক্রবার'];
        return view('teacher.routine.print', compact('school','teacher','maxPeriod','entries','days'));
    }
}
