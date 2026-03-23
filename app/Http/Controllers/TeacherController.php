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
use App\Models\Notice;

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
        if ($school) {
            $today = Carbon::now()->format('l'); // e.g., Monday
            $todayRoutine = RoutineEntry::where('school_id', $school->id)
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

        // Fetch 5 latest unread notices
        $schoolId = $school->id ?? 0;
        $teacherId = $user->teacher?->id;

        $unreadNotices = Notice::published()
            ->active()
            ->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                    ->orWhereNull('school_id');
            })
            ->where(function ($q) use ($user, $teacherId) {
                // Global "All" notices
                $q->where('audience_type', 'all');

                // Notices for "Teachers"
                $q->orWhere(function ($qq) use ($teacherId) {
                    $qq->where('audience_type', 'teachers')
                        ->where(function ($qqq) use ($teacherId) {
                            $qqq->doesntHave('targets')
                                ->orWhereHas('targets', function ($t) use ($teacherId) {
                                    $t->where('targetable_id', $teacherId)
                                        ->where('targetable_type', \App\Models\Teacher::class);
                                });
                        });
                });
            })
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest('publish_at')
            ->take(5)
            ->get();

        return view('teacher.dashboard', compact(
            'school',
            'assignedClasses',
            'todayRoutine',
            'pendingEvaluations',
            'unreadNotices'
        ));
    }

    public function notices(\App\Models\School $school)
    {
        return view('teacher.notices.index', compact('school'));
    }
}
