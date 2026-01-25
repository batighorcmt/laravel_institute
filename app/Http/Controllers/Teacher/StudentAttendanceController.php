<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AttendanceSmsService;
use Carbon\Carbon;

class StudentAttendanceController extends Controller
{
    /**
     * Display attendance page with class/section selection
     */
    public function index(School $school)
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        // Get sections where this teacher is assigned as class teacher
        $sections = Section::where('school_id', $school->id)
            ->where('class_teacher_id', $teacher->id)
            ->where('status', 'active')
            ->with('schoolClass')
            ->get();

        // Group sections by class_id
        $sectionsByClass = $sections->groupBy('class_id');
        
        $classIds = $sectionsByClass->keys();
        
        $classes = SchoolClass::forSchool($school->id)
            ->active()
            ->whereIn('id', $classIds)
            ->orderBy('numeric_value')
            ->get();

        return view('teacher.attendance.class.index', compact('school', 'teacher', 'classes', 'sectionsByClass'));
    }

    /**
     * Show attendance taking form for a specific class/section
     */
    public function take(School $school, Request $request)
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));

        if (!$classId || !$sectionId) {
            return redirect()->route('teacher.institute.attendance.class.index', $school)
                ->with('error', 'ক্লাস এবং শাখা নির্বাচন করুন।');
        }

        // Verify teacher has access to this class/section (must be class teacher)
        $section = Section::where('id', $sectionId)
            ->where('class_id', $classId)
            ->where('class_teacher_id', $teacher->id)
            ->first();

        if (!$section) {
            abort(403, 'আপনি এই শাখার ক্লাস টিচার নন। শুধুমাত্র ক্লাস টিচার হাজিরা নিতে পারবেন।');
        }

        $schoolClass = SchoolClass::findOrFail($classId);
        $section = Section::findOrFail($sectionId);

        // Get enrolled students
        $enrollments = StudentEnrollment::with('student')
            ->where('school_id', $school->id)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'active')
            ->orderBy('roll_no')
            ->get();

        // Check if attendance already exists for this date
        $existingAttendances = Attendance::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('date', $date)
            ->get()
            ->keyBy('student_id');

        $isExistingRecord = $existingAttendances->isNotEmpty();
        $existingAttendance = $existingAttendances->pluck('status', 'student_id')->toArray();
        $remarks = $existingAttendances->pluck('remarks', 'student_id')->toArray();

        return view('teacher.attendance.class.take', compact(
            'school',
            'teacher',
            'schoolClass',
            'section',
            'enrollments',
            'date',
            'isExistingRecord',
            'existingAttendance',
            'remarks'
        ));
    }

    /**
     * Store or update attendance
     */
    public function store(School $school, Request $request)
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->firstOrFail();

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|in:present,absent,late',
        ]);

        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $date = Carbon::today()->format('Y-m-d');

        // Verify teacher has access (must be class teacher of this section)
        $section = Section::where('id', $sectionId)
            ->where('class_id', $classId)
            ->where('class_teacher_id', $teacher->id)
            ->first();

        if (!$section) {
            abort(403, 'আপনি এই শাখার ক্লাস টিচার নন।');
        }

        // Capture previous statuses before modifying records
        $previousStatuses = Attendance::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('date', $date)
            ->pluck('status','student_id')
            ->toArray();

        DB::beginTransaction();
        try {
            // Delete existing attendance for today
            Attendance::where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('date', $date)
                ->delete();

            // Insert new attendance records
            foreach ($request->attendance as $studentId => $data) {
                Attendance::create([
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'student_id' => $studentId,
                    'date' => $date,
                    'status' => $data['status'],
                    'remarks' => $data['remarks'] ?? null,
                    'recorded_by' => $user->id,
                ]);
            }

            DB::commit();

            // Enqueue SMS notifications using shared service
            $smsService = new AttendanceSmsService();
            $smsService->enqueueAttendanceSms($school, $request->attendance, $classId, $sectionId, $date, true, $previousStatuses, $user->id);

            return redirect()->route('teacher.institute.attendance.class.take', [
                'school' => $school,
                'class_id' => $classId,
                'section_id' => $sectionId,
            ])->with('success', 'উপস্থিতি সফলভাবে সংরক্ষণ করা হয়েছে।');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'উপস্থিতি সংরক্ষণ করতে সমস্যা হয়েছে।');
        }
    }
}
