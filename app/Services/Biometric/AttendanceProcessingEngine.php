<?php

namespace App\Services\Biometric;

use App\Models\BiometricAttendanceLog;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Phase 5: Attendance Processing Engine
 * 
 * Flow:
 *   BiometricAttendanceLog → Find Student/Teacher → Create/Update Attendance → Notify Guardian
 *
 * Duplicate Prevention:
 *   One student = one attendance record per day.
 *   Multiple punches = updates entry_time (first) and exit_time (last).
 *
 * Late Logic (configurable per school – currently default times):
 *   Before 08:45 AM  → Present
 *   08:45 – 09:30 AM → Late
 *   After 09:30 AM   → Absent
 */
class AttendanceProcessingEngine
{
    public function processPunch(BiometricAttendanceLog $log): void
    {
        $schoolId    = $log->school_id;
        $biometricId = $log->biometric_id;
        $punchTime   = Carbon::parse($log->punch_time);
        $date        = $punchTime->toDateString();

        // ── Identify user ────────────────────────────────────────────────────
        $student = Student::where('school_id', $schoolId)
                        ->where('biometric_id', $biometricId)
                        ->first();

        if ($student) {
            $this->processStudentAttendance($student, $punchTime, $date, $log);
            return;
        }

        $teacher = Teacher::where('school_id', $schoolId)
                        ->where('biometric_id', $biometricId)
                        ->first();

        if ($teacher) {
            $this->processTeacherAttendance($teacher, $punchTime, $date, $log);
            return;
        }

        Log::warning("[Biometric] Unknown biometric_id: {$biometricId} at school {$schoolId}");
        $log->update(['sync_status' => 'failed']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function processStudentAttendance(
        Student $student, Carbon $punchTime, string $date, BiometricAttendanceLog $log
    ): void {
        $attendance = Attendance::where('student_id', $student->id)
                        ->where('date', $date)
                        ->first();

        // ── Calculate status based on punch time ─────────────────────────────
        // TODO: Load from school settings table in future
        $lateTime   = Carbon::parse($date . ' 08:45:00');
        $absentTime = Carbon::parse($date . ' 09:30:00');

        $status = 'present';
        if ($punchTime->gt($lateTime) && $punchTime->lte($absentTime)) {
            $status = 'late';
        } elseif ($punchTime->gt($absentTime)) {
            $status = 'absent';
        }

        if (!$attendance) {
            // ── First punch of the day → Entry ────────────────────────────────
            $enrollment = $student->currentEnrollment;
            $attendance = Attendance::create([
                'student_id'  => $student->id,
                'class_id'    => $student->class_id,
                'section_id'  => $enrollment?->section_id,
                'date'        => $date,
                'status'      => $status,
                'entry_time'  => $punchTime,
                'recorded_by' => null,
            ]);

            // ── Phase 5: Send Flutter push notification (Entry) ───────────────
            \App\Jobs\SendAttendanceNotificationJob::dispatch($student, $attendance, 'entry');

        } else {
            // ── Subsequent punches → Update entry/exit ───────────────────────
            $changed = false;

            if (!$attendance->entry_time || $punchTime->lt(Carbon::parse($attendance->entry_time))) {
                $attendance->entry_time = $punchTime;
                $changed = true;
            }

            if (!$attendance->exit_time || $punchTime->gt(Carbon::parse($attendance->exit_time))) {
                // Only send exit notification if this is genuinely later than entry
                $isNewExit = $attendance->exit_time === null ||
                    $punchTime->gt(Carbon::parse($attendance->exit_time));

                $attendance->exit_time = $punchTime;
                $changed = true;

                if ($isNewExit) {
                    // ── Phase 5: Send exit notification ──────────────────────
                    \App\Jobs\SendAttendanceNotificationJob::dispatch($student, $attendance, 'exit');
                }
            }

            if ($changed) {
                $attendance->save();
            }
        }

        $log->update(['sync_status' => 'processed']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function processTeacherAttendance(
        Teacher $teacher, Carbon $punchTime, string $date, BiometricAttendanceLog $log
    ): void {
        $attendance = TeacherAttendance::where('user_id', $teacher->user_id)
                        ->where('school_id', $teacher->school_id)
                        ->where('date', $date)
                        ->first();

        if (!$attendance) {
            TeacherAttendance::create([
                'user_id'       => $teacher->user_id,
                'school_id'     => $teacher->school_id,
                'date'          => $date,
                'check_in_time' => $punchTime->format('H:i:s'),
                'status'        => 'present',
            ]);
        } else {
            // Update check-out if this punch is later than check-in
            $checkIn = Carbon::parse($date . ' ' . $attendance->check_in_time);
            if ($punchTime->gt($checkIn)) {
                $attendance->update(['check_out_time' => $punchTime->format('H:i:s')]);
            }
        }

        $log->update(['sync_status' => 'processed']);
    }
}
