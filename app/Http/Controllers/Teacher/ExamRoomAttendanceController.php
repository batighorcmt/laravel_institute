<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ExamRoomAttendance;
use App\Models\ExamRoomInvigilation;
use App\Models\School;
use App\Models\SeatPlan;
use App\Models\SeatPlanRoom;
use App\Models\SeatPlanAllocation;
use App\Models\ExamSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExamRoomAttendanceController extends Controller
{
    public function index(Request $request, School $school)
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */
        
        $isPrincipal = $user->isPrincipal($school->id) || $user->isSuperAdmin() || $user->isExamController($school->id);
        
        $date = $request->get('date', now()->format('Y-m-d'));
        $planId = $request->get('plan_id');
        $roomId = $request->get('room_id');

        // 1. Get Plans
        if ($isPrincipal) {
            $plans = SeatPlan::where('school_id', $school->id)->active()->orderBy('id', 'desc')->get();
        } else {
            // Teacher: only assigned plans for this date
            $plans = SeatPlan::whereHas('rooms.invigilations', function ($q) use ($user, $date) {
                $q->where('teacher_id', $user->id)->whereDate('duty_date', $date);
            })->get();
        }

        if (!$planId && $plans->isNotEmpty()) {
            $planId = $plans->first()->id;
        }

        // 2. Get Dates (mapped to the selected plan)
        $examDates = [];
        if ($planId) {
            if ($isPrincipal) {
                $examDates = ExamSubject::whereIn('exam_id', function($query) use ($planId) {
                    $query->select('exam_id')->from('seat_plan_exams')->where('seat_plan_id', $planId);
                })->whereNotNull('exam_date')->distinct()->orderBy('exam_date')->pluck('exam_date')->map(fn($d) => $d->format('Y-m-d'))->toArray();
            } else {
                // Teacher: dates they are invigilating for this plan
                $examDates = ExamRoomInvigilation::where('seat_plan_id', $planId)
                    ->where('teacher_id', $user->id)
                    ->distinct()
                    ->orderBy('duty_date')
                    ->pluck('duty_date')
                    ->map(fn($d) => $d->format('Y-m-d'))
                    ->toArray();
            }
        }

        if (!empty($examDates) && !in_array($date, $examDates)) {
            $date = $examDates[0] ?? $date;
        }

        // 3. Get Rooms
        $rooms = [];
        if ($planId) {
            if ($isPrincipal) {
                $rooms = SeatPlanRoom::where('seat_plan_id', $planId)->orderBy('room_no')->get();
            } else {
                // Teacher: only assigned rooms for this date and plan
                $rooms = SeatPlanRoom::whereHas('invigilations', function ($q) use ($user, $date, $planId) {
                    $q->where('teacher_id', $user->id)->whereDate('duty_date', $date)->where('seat_plan_id', $planId);
                })->orderBy('room_no')->get();
            }
        }

        if (!$roomId && !empty($rooms) && count($rooms) > 0) {
            $roomId = $rooms[0]->id;
        }

        // 4. Students & Attendance
        $students = [];
        $stats = ['total' => 0, 'male' => 0, 'female' => 0, 'present' => 0, 'absent' => 0];
        $classCounts = [];

        if ($planId && $roomId) {
            // Check access
            $canAccess = $isPrincipal || ExamRoomInvigilation::where('seat_plan_id', $planId)
                ->where('seat_plan_room_id', $roomId)
                ->where('teacher_id', $user->id)
                ->whereDate('duty_date', $date)
                ->exists();

            if ($canAccess) {
                $allocations = SeatPlanAllocation::with(['student', 'student.currentEnrollment.class'])
                    ->where('seat_plan_id', $planId)
                    ->where('room_id', $roomId)
                    ->get();

                $attendances = ExamRoomAttendance::where('plan_id', $planId)
                    ->where('room_id', $roomId)
                    ->whereDate('duty_date', $date)
                    ->get()
                    ->keyBy('student_id');

                foreach ($allocations as $alloc) {
                    $student = $alloc->student;
                    if (!$student) continue;

                    $status = $attendances->has($student->id) ? $attendances->get($student->id)->status : null;
                    
                    $enrollment = $student->currentEnrollment;
                    $className = 'Unknown';
                    
                    if ($enrollment && $enrollment->class) {
                        $className = $enrollment->class->name;
                    } elseif ($student->class) {
                        $className = $student->class->name;
                    }
                    
                    $students[] = [
                        'id' => $student->id,
                        'roll' => $alloc->student->roll_no ?? $student->currentEnrollment?->roll_no,
                        'name' => $student->student_name_bn ?: $student->student_name_en,
                        'class' => $className,
                        'status' => $status,
                    ];

                    $stats['total']++;
                    $gender = strtolower($student->gender ?? '');
                    if ($gender === 'male') $stats['male']++;
                    if ($gender === 'female') $stats['female']++;
                    if ($status === 'present') $stats['present']++;
                    if ($status === 'absent') $stats['absent']++;

                    $classCounts[$className] = ($classCounts[$className] ?? 0) + 1;
                }
            }
        }

        return view('teacher.exams.room-attendance.index', compact(
            'school', 'plans', 'examDates', 'rooms', 'date', 'planId', 'roomId', 'students', 'stats', 'classCounts', 'isPrincipal'
        ));
    }

    public function mark(Request $request, School $school)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'plan_id' => 'required|exists:seat_plans,id',
            'room_id' => 'required|exists:seat_plan_rooms,id',
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:present,absent',
        ]);

        $user = Auth::user();
        /** @var \App\Models\User $user */
        
        $isPrincipal = $user->isPrincipal($school->id) || $user->isSuperAdmin() || $user->isExamController($school->id);

        if (!$isPrincipal) {
            $canAccess = ExamRoomInvigilation::where('seat_plan_id', $validated['plan_id'])
                ->where('seat_plan_room_id', $validated['room_id'])
                ->where('teacher_id', $user->id)
                ->whereDate('duty_date', $validated['date'])
                ->exists();
            if (!$canAccess) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        ExamRoomAttendance::updateOrCreate(
            [
                'school_id' => $school->id,
                'duty_date' => $validated['date'],
                'plan_id' => $validated['plan_id'],
                'room_id' => $validated['room_id'],
                'student_id' => $validated['student_id'],
            ],
            ['status' => $validated['status']]
        );

        return response()->json(['success' => true]);
    }

    public function markAll(Request $request, School $school)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'plan_id' => 'required|exists:seat_plans,id',
            'room_id' => 'required|exists:seat_plan_rooms,id',
            'mode' => 'required|in:present,absent',
        ]);

        $user = Auth::user();
        /** @var \App\Models\User $user */
        
        $isPrincipal = $user->isPrincipal($school->id) || $user->isSuperAdmin() || $user->isExamController($school->id);

        if (!$isPrincipal) {
            $canAccess = ExamRoomInvigilation::where('seat_plan_id', $validated['plan_id'])
                ->where('seat_plan_room_id', $validated['room_id'])
                ->where('teacher_id', $user->id)
                ->whereDate('duty_date', $validated['date'])
                ->exists();
            if (!$canAccess) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $studentIds = SeatPlanAllocation::where('seat_plan_id', $validated['plan_id'])
            ->where('room_id', $validated['room_id'])
            ->pluck('student_id');

        foreach ($studentIds as $sid) {
            ExamRoomAttendance::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'duty_date' => $validated['date'],
                    'plan_id' => $validated['plan_id'],
                    'room_id' => $validated['room_id'],
                    'student_id' => $sid,
                ],
                ['status' => $validated['mode']]
            );
        }

        return response()->json(['success' => true]);
    }
}
