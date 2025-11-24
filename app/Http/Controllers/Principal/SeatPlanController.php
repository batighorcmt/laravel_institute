<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\SeatPlan;
use App\Models\SeatPlanRoom;
use App\Models\SeatPlanAllocation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Exam;
use App\Models\Student;
use Illuminate\Http\Request;

class SeatPlanController extends Controller
{
    public function index(School $school)
    {
        $seatPlans = SeatPlan::with(['rooms'])
            ->forSchool($school->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('principal.seat-plans.index', compact('school', 'seatPlans'));
    }

    public function create(School $school)
    {
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $exams = Exam::forSchool($school->id)->active()->get();

        return view('principal.seat-plans.create', compact('school', 'classes', 'exams'));
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shift' => 'nullable|string|max:50',
            'status' => 'required|in:draft,active,completed',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id',
            'exam_ids' => 'nullable|array',
            'exam_ids.*' => 'exists:exams,id',
        ]);

        $seatPlan = SeatPlan::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'shift' => $validated['shift'] ?? null,
            'status' => $validated['status'],
        ]);

        // Attach classes
        if (!empty($validated['class_ids'])) {
            foreach ($validated['class_ids'] as $classId) {
                $seatPlan->seatPlanClasses()->create(['class_id' => $classId]);
            }
        }

        // Attach exams
        if (!empty($validated['exam_ids'])) {
            foreach ($validated['exam_ids'] as $examId) {
                $seatPlan->seatPlanExams()->create(['exam_id' => $examId]);
            }
        }

        return redirect()
            ->route('principal.institute.seat-plans.show', [$school, $seatPlan])
            ->with('success', 'সিট প্ল্যান সফলভাবে তৈরি করা হয়েছে');
    }

    public function show(School $school, SeatPlan $seatPlan)
    {
        $seatPlan->load(['rooms.allocations.student', 'classes', 'exams']);

        return view('principal.seat-plans.show', compact('school', 'seatPlan'));
    }

    public function edit(School $school, SeatPlan $seatPlan)
    {
        $seatPlan->load(['seatPlanClasses', 'seatPlanExams']);
        $classes = SchoolClass::forSchool($school->id)->orderBy('numeric_value')->get();
        $exams = Exam::forSchool($school->id)->active()->get();

        return view('principal.seat-plans.edit', compact('school', 'seatPlan', 'classes', 'exams'));
    }

    public function update(Request $request, School $school, SeatPlan $seatPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shift' => 'nullable|string|max:50',
            'status' => 'required|in:draft,active,completed',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id',
            'exam_ids' => 'nullable|array',
            'exam_ids.*' => 'exists:exams,id',
        ]);

        $seatPlan->update([
            'name' => $validated['name'],
            'shift' => $validated['shift'] ?? null,
            'status' => $validated['status'],
        ]);

        // Sync classes
        $seatPlan->seatPlanClasses()->delete();
        if (!empty($validated['class_ids'])) {
            foreach ($validated['class_ids'] as $classId) {
                $seatPlan->seatPlanClasses()->create(['class_id' => $classId]);
            }
        }

        // Sync exams
        $seatPlan->seatPlanExams()->delete();
        if (!empty($validated['exam_ids'])) {
            foreach ($validated['exam_ids'] as $examId) {
                $seatPlan->seatPlanExams()->create(['exam_id' => $examId]);
            }
        }

        return redirect()
            ->route('principal.institute.seat-plans.show', [$school, $seatPlan])
            ->with('success', 'সিট প্ল্যান সফলভাবে আপডেট করা হয়েছে');
    }

    public function destroy(School $school, SeatPlan $seatPlan)
    {
        $seatPlan->delete();

        return redirect()
            ->route('principal.institute.seat-plans.index', $school)
            ->with('success', 'সিট প্ল্যান সফলভাবে মুছে ফেলা হয়েছে');
    }

    // Room Management
    public function manageRooms(School $school, SeatPlan $seatPlan)
    {
        $rooms = $seatPlan->rooms()->get();

        return view('principal.seat-plans.rooms', compact('school', 'seatPlan', 'rooms'));
    }

    public function storeRoom(Request $request, School $school, SeatPlan $seatPlan)
    {
        $validated = $request->validate([
            'room_no' => 'required|string|max:50',
            'title' => 'nullable|string|max:255',
            'columns_count' => 'required|integer|in:1,2,3',
            'col1_benches' => 'required|integer|min:0',
            'col2_benches' => 'required|integer|min:0',
            'col3_benches' => 'required|integer|min:0',
        ]);

        $validated['seat_plan_id'] = $seatPlan->id;

        SeatPlanRoom::create($validated);

        return redirect()
            ->route('principal.institute.seat-plans.rooms', [$school, $seatPlan])
            ->with('success', 'রুম সফলভাবে যুক্ত করা হয়েছে');
    }

    public function updateRoom(Request $request, School $school, SeatPlan $seatPlan, SeatPlanRoom $room)
    {
        $validated = $request->validate([
            'room_no' => 'required|string|max:50',
            'title' => 'nullable|string|max:255',
            'columns_count' => 'required|integer|in:1,2,3',
            'col1_benches' => 'required|integer|min:0',
            'col2_benches' => 'required|integer|min:0',
            'col3_benches' => 'required|integer|min:0',
        ]);

        $room->update($validated);

        return redirect()
            ->route('principal.institute.seat-plans.rooms', [$school, $seatPlan])
            ->with('success', 'রুম সফলভাবে আপডেট করা হয়েছে');
    }

    public function destroyRoom(School $school, SeatPlan $seatPlan, SeatPlanRoom $room)
    {
        $room->delete();

        return redirect()
            ->route('principal.institute.seat-plans.rooms', [$school, $seatPlan])
            ->with('success', 'রুম সফলভাবে মুছে ফেলা হয়েছে');
    }

    // Seat Allocation
    public function allocateSeats(School $school, SeatPlan $seatPlan)
    {
        $rooms = $seatPlan->rooms()->with('allocations.student')->get();
        $students = Student::forSchool($school->id)
            ->whereIn('class_id', $seatPlan->seatPlanClasses()->pluck('class_id'))
            ->orderBy('student_id')
            ->get();

        return view('principal.seat-plans.allocate', compact('school', 'seatPlan', 'rooms', 'students'));
    }

    public function storeAllocation(Request $request, School $school, SeatPlan $seatPlan)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:seat_plan_rooms,id',
            'student_id' => 'required|exists:students,id',
            'col_no' => 'required|integer',
            'bench_no' => 'required|integer',
            'position' => 'required|in:Left,Right',
        ]);

        $validated['seat_plan_id'] = $seatPlan->id;

        SeatPlanAllocation::create($validated);

        return response()->json(['success' => true, 'message' => 'Seat allocated successfully']);
    }

    public function removeAllocation(School $school, SeatPlan $seatPlan, SeatPlanAllocation $allocation)
    {
        $allocation->delete();

        return response()->json(['success' => true, 'message' => 'Allocation removed successfully']);
    }

    // Print Seat Plan
    public function printRoom(School $school, SeatPlan $seatPlan, SeatPlanRoom $room)
    {
        $room->load('allocations.student');

        return view('principal.seat-plans.print-room', compact('school', 'seatPlan', 'room'));
    }

    public function printAll(School $school, SeatPlan $seatPlan)
    {
        $rooms = $seatPlan->rooms()->with('allocations.student')->get();

        return view('principal.seat-plans.print-all', compact('school', 'seatPlan', 'rooms'));
    }

    // Search student for allocation
    public function searchStudents(Request $request, School $school, SeatPlan $seatPlan)
    {
        $search = $request->get('q');

        $students = Student::forSchool($school->id)
            ->whereIn('class_id', $seatPlan->seatPlanClasses()->pluck('class_id'))
            ->where(function ($query) use ($search) {
                $query->where('student_name_en', 'like', "%{$search}%")
                    ->orWhere('student_name_bn', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'student_id', 'student_name_en', 'student_name_bn', 'class_id']);

        return response()->json($students);
    }

    // Find student seat
    public function findStudent(Request $request, School $school, SeatPlan $seatPlan)
    {
        $search = $request->get('search');

        $allocation = SeatPlanAllocation::where('seat_plan_id', $seatPlan->id)
            ->whereHas('student', function ($query) use ($search) {
                $query->where('student_id', 'like', "%{$search}%")
                    ->orWhere('student_name_en', 'like', "%{$search}%")
                    ->orWhere('student_name_bn', 'like', "%{$search}%");
            })
            ->with(['student', 'room'])
            ->first();

        if ($allocation) {
            return view('principal.seat-plans.find-result', compact('allocation'));
        }

        return view('principal.seat-plans.find-result', ['allocation' => null]);
    }
}
