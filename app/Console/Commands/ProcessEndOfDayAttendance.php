<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\SchoolAttendanceSetting;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\TeacherAttendance;
use App\Models\Teacher;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessEndOfDayAttendance extends Command
{
    /**
     * The name and signature of the console command.
     * Run every 5 minutes via scheduler.
     */
    protected $signature = 'app:process-end-of-day-attendance {--school= : Process only a specific school}';

    /**
     * The console command description.
     */
    protected $description = 'Auto-mark absent for students/teachers who have not punched in after the late threshold, and send notifications.';

    public function handle(PushNotificationService $pushService): void
    {
        $today = Carbon::today()->toDateString();
        $now   = Carbon::now()->format('H:i:s');

        $schoolQuery = School::query();
        if ($this->option('school')) {
            $schoolQuery->where('id', $this->option('school'));
        }

        $schools = $schoolQuery->get();

        foreach ($schools as $school) {
            $settings = SchoolAttendanceSetting::where('school_id', $school->id)->first();

            // Use defaults if not configured
            $studentLateThreshold = $settings->student_late_threshold ?? '09:30:00';
            $studentExitEnd       = $settings->student_exit_end       ?? '15:00:00';
            $teacherLateThreshold = $settings->teacher_late_threshold ?? '09:30:00';

            // ── Mark absent students ──────────────────────────────────────────
            // Only process after the student late threshold has passed
            if ($now >= $studentLateThreshold) {
                $this->markAbsentStudents($school, $today, $pushService);
            }

            // ── Send end-of-day notifications when exit window closes ─────────
            if ($now >= $studentExitEnd) {
                $this->sendEndOfDayNotifications($school, $today, $pushService);
            }

            // ── Mark absent teachers ──────────────────────────────────────────
            if ($now >= $teacherLateThreshold) {
                $this->markAbsentTeachers($school, $today);
            }

            $this->info("[{$school->name}] Processed for {$today}");
        }
    }

    /**
     * Auto-create absent records for students without any attendance today.
     */
    private function markAbsentStudents(School $school, string $today, PushNotificationService $pushService): void
    {
        // Get all active enrolled students who don't have attendance today
        $enrolledStudentIds = StudentEnrollment::where('school_id', $school->id)
            ->where('status', 'active')
            ->pluck('student_id');

        $presentStudentIds = Attendance::where('date', $today)
            ->whereIn('student_id', $enrolledStudentIds)
            ->pluck('student_id');

        $absentStudentIds = $enrolledStudentIds->diff($presentStudentIds);

        foreach ($absentStudentIds as $studentId) {
            $enrollment = StudentEnrollment::where('student_id', $studentId)
                ->where('status', 'active')
                ->latest()
                ->first();

            if (!$enrollment || !$enrollment->section_id) {
                continue;
            }

            try {
                Attendance::create([
                    'student_id'  => $studentId,
                    'class_id'    => $enrollment->class_id,
                    'section_id'  => $enrollment->section_id,
                    'date'        => $today,
                    'status'      => 'absent',
                    'medium'      => 'system',
                    'recorded_by' => null,
                ]);

                Log::info("[EndOfDay] Auto-marked absent: student_id={$studentId} school={$school->id}");
            } catch (\Throwable $e) {
                Log::error("[EndOfDay] Failed to mark absent for student_id={$studentId}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send high-priority end-of-day push notifications to guardians.
     */
    private function sendEndOfDayNotifications(School $school, string $today, PushNotificationService $pushService): void
    {
        // Only send once per day - check if already processed
        $cacheKey = "eod_notif_{$school->id}_{$today}";
        if (cache()->get($cacheKey)) {
            return;
        }

        $attendances = Attendance::with(['student'])
            ->where('date', $today)
            ->whereHas('student', fn($q) => $q->where('school_id', $school->id))
            ->get();

        foreach ($attendances as $att) {
            $student = $att->student;
            if (!$student) continue;

            try {
                $pushService->sendAttendanceNotification($student->id, $att->status, $today, 'class');
            } catch (\Throwable $e) {
                Log::error("[EndOfDay] Notification failed for student_id={$student->id}: " . $e->getMessage());
            }
        }

        // Mark as done for today so we don't send again
        cache()->put($cacheKey, true, now()->endOfDay());
        $this->info("[{$school->name}] End-of-day notifications sent.");
    }

    /**
     * Auto-create absent records for teachers without any attendance today.
     */
    private function markAbsentTeachers(School $school, string $today): void
    {
        $activeTeachers = Teacher::where('school_id', $school->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeTeachers as $teacher) {
            if (!$teacher->user_id) continue;

            $existing = TeacherAttendance::where('user_id', $teacher->user_id)
                ->where('school_id', $school->id)
                ->where('date', $today)
                ->exists();

            if (!$existing) {
                try {
                    TeacherAttendance::create([
                        'user_id'   => $teacher->user_id,
                        'school_id' => $school->id,
                        'date'      => $today,
                        'status'    => 'absent',
                        'medium'    => 'system',
                    ]);
                } catch (\Throwable $e) {
                    Log::error("[EndOfDay] Failed to mark teacher absent user_id={$teacher->user_id}: " . $e->getMessage());
                }
            }
        }
    }
}
