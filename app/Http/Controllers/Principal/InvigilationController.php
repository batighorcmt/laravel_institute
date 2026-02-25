<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\User;
use App\Models\SeatPlan;
use App\Models\SeatPlanRoom;
use App\Models\SeatPlanExam;
use App\Models\ExamController;
use App\Models\ExamRoomInvigilation;
use Illuminate\Support\Facades\Auth;

class InvigilationController extends Controller
{
    /**
     * Display the invigilation management dashboard
     */
    public function index(Request $request, School $school)
    {
        $user = Auth::user();
        if (!$user->isPrincipal($school->id) && !$user->isExamController($school->id)) {
            abort(403, 'Unauthorized.');
        }

        // 1. Get Teachers for the Select2 dropdowns (filtered by active schoolRoles that have the 'teacher' role)
        $teachers = User::whereHas('activeSchoolRoles', function($query) use ($school) {
            $query->where('school_id', $school->id)->whereHas('role', function($r) {
                $r->where('name', 'teacher');
            });
        })->with(['teacher' => function($query) use ($school) {
            $query->where('school_id', $school->id)->select('user_id', 'initials');
        }])->get();

        // 2. Get active Seat Plans
        $plans = SeatPlan::where('school_id', $school->id)
                    ->where('status', 'active')
                    ->orderBy('id', 'desc')
                    ->get();

        // 3. Current controller
        $currentController = ExamController::where('school_id', $school->id)
            ->where('active', true)
            ->latest()
            ->first();

        // 4. Handle PRG query params
        $sel_plan_id = $request->get('plan_id', $plans->first()?->id ?? 0);
        $sel_date = $request->get('duty_date', null);

        // 5. Build Valid Dates and Load Rooms
        $examDates = collect();
        $rooms = collect();
        $dutyMap = [];

        if ($sel_plan_id) {
            $selectedPlan = SeatPlan::find($sel_plan_id);

            // In Laravel structure, SeatPlan has a exams() relationship through seat_plan_exams table or similar.
            // Based on earlier context, Seat Plans are often tied to Exams either directly or through a mapping table.
            // Let's assume there is an 'exams' relationship on SeatPlan, and each exam has start/end dates or subjects with dates.
            // Note: Since I don't have exact visibility into Seat Plan -> Date linkage in the new schema, I will look up dates dynamically.
            if ($selectedPlan) {
                // If the app uses SeatPlan->exams->examSubjects->exam_date, we pull that.
                // For safety and compatibility with standard structure, let's fetch any exams tied to this seat plan.
                $exams = $selectedPlan->exams()->with('examSubjects')->get();
                foreach($exams as $ex) {
                    foreach($ex->examSubjects as $sub) {
                        if ($sub->exam_date) {
                            $examDates->push($sub->exam_date->format('Y-m-d'));
                        }
                    }
                }
                $examDates = $examDates->unique()->sort()->values();

                // Validate selected date
                if ($sel_date && !$examDates->contains($sel_date)) {
                    $sel_date = null;
                }

                // Get Rooms
                $rooms = SeatPlanRoom::where('seat_plan_id', $sel_plan_id)->orderBy('room_no')->get();

                // Get Existing Duties if a valid date is selected
                if ($sel_date) {
                    $duties = ExamRoomInvigilation::where('school_id', $school->id)
                        ->where('seat_plan_id', $sel_plan_id)
                        ->where('duty_date', $sel_date)
                        ->get();
                    
                    foreach ($duties as $d) {
                        $dutyMap[$d->seat_plan_room_id] = $d->teacher_id;
                    }
                }
            }
        }

        // Restore posted duties if returning from a validation error
        if (session()->has('postedDutyMap')) {
            $dutyMap = session('postedDutyMap');
        }

        return view('principal.invigilations.index', compact(
            'school', 'teachers', 'plans', 'currentController', 
            'sel_plan_id', 'sel_date', 'examDates', 'rooms', 'dutyMap'
        ));
    }

    public function setController(Request $request, School $school)
    {
        if (!Auth::user()->isPrincipal($school->id)) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // Deactivate old controllers for this school
        ExamController::where('school_id', $school->id)->update(['active' => false]);

        // Set the new one
        ExamController::create([
            'school_id' => $school->id,
            'user_id' => $request->user_id,
            'active' => true
        ]);

        $redirectRoute = request()->routeIs('teacher.*') 
            ? 'teacher.institute.exams.invigilations.index' 
            : 'principal.institute.exams.invigilations.index';

        return redirect()->route($redirectRoute, $school)
            ->with('success', 'Exam controller set successfully.');
    }

    /**
     * Store/Update room invigilation duties
     */
    public function store(Request $request, School $school)
    {
        $user = Auth::user();
        if (!$user->isPrincipal($school->id) && !$user->isExamController($school->id)) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'plan_id' => 'required|exists:seat_plans,id',
            'duty_date' => 'required|date',
            'room_teacher' => 'nullable|array'
        ]);

        $plan_id = $request->plan_id;
        $duty_date = $request->duty_date;
        $room_teacher = $request->room_teacher ?? [];

        // Enforce uniqueness: A teacher can be assigned to only one room on this date
        $teacherCounts = array_count_values(array_filter($room_teacher));
        $duplicateTeachers = array_filter($teacherCounts, fn($count) => $count > 1);

        if (count($duplicateTeachers) > 0) {
            return redirect()->route('principal.institute.exams.invigilations.index', [
                'school' => $school,
                'plan_id' => $plan_id,
                'duty_date' => $duty_date,
            ])->with('error', 'Each teacher can be assigned to only one room for this date.')
              ->with('postedDutyMap', $room_teacher);
        }

        $assignedBy = Auth::id();

        // Perform upserts
        foreach ($room_teacher as $room_id => $teacher_id) {
            if (!$teacher_id) continue;

            ExamRoomInvigilation::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'duty_date' => $duty_date,
                    'seat_plan_id' => $plan_id,
                    'seat_plan_room_id' => $room_id,
                ],
                [
                    'teacher_id' => $teacher_id,
                    'assigned_by' => $assignedBy
                ]
            );
        }

        $redirectRoute = request()->routeIs('teacher.*') 
            ? 'teacher.institute.exams.invigilations.index' 
            : 'principal.institute.exams.invigilations.index';

        return redirect()->route($redirectRoute, [
            'school' => $school,
            'plan_id' => $plan_id,
            'duty_date' => $duty_date,
        ])->with('success', 'Invigilation duties saved successfully.');
    }
}
