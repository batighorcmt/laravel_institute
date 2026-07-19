<?php

namespace App\Services\Biometric;

use App\Models\BiometricAttendanceLog;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\TeacherAttendance;
use App\Models\SchoolAttendanceSetting;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 5: Attendance Processing Engine
 *
 * Flow:
 *   BiometricAttendanceLog → Find Student/Teacher → Create/Update Attendance → Notify Guardian
 *
 * Duplicate Prevention:
 *   One student = one attendance record per day.
 *   Multiple punches = updates entry_time (first punch) and exit_time (last punch).
 *
 * Late Logic (loaded from school_attendance_settings):
 *   Before student_entry_end   → Present
 *   student_entry_end to late_threshold → Late
 *   After late_threshold       → Absent (punch recorded but status is absent)
 *
 * Medium:
 *   Attendances created here are always tagged medium = 'biometric'.
 */
class AttendanceProcessingEngine
{
    /** @var array<int, SchoolAttendanceSetting> In-memory cache per request */
    private array $settingsCache = [];

    public function processPunch(BiometricAttendanceLog $log): void
    {
        $schoolId    = $log->school_id;
        $biometricId = $this->normalizeBiometricId($log->biometric_id);
        $punchTime   = Carbon::parse($log->punch_time);
        $date        = $punchTime->toDateString();
        $timeOnly    = $punchTime->format('H:i:s');

        $settings = $this->getSettings($schoolId);

        // ── Identify user ──────────────────────────────────────────────────────
        $student = Student::where('school_id', $schoolId)
                        ->where(function ($query) use ($biometricId) {
                            $query->where('biometric_id', $biometricId)
                                  ->orWhere('biometric_id', 'like', '%' . $biometricId);
                        })
                        ->first();

        if ($student) {
            if ($student->status !== 'active') {
                Log::warning("[Biometric] Skipping inactive student {$student->id} ({$student->biometric_id}) at school {$schoolId}");
                $log->update(['sync_status' => 'failed']);
                return;
            }

            if (preg_replace('/\D/', '', $student->biometric_id) !== $biometricId) {
                $student->biometric_id = $biometricId;
                $student->save();
            }
            $this->processStudentAttendance($student, $punchTime, $timeOnly, $date, $log, $settings);
            return;
        }

        $teacher = Teacher::where('school_id', $schoolId)
                        ->where('biometric_id', $biometricId)
                        ->first();

        if ($teacher) {
            if ($teacher->status !== 'active') {
                Log::warning("[Biometric] Skipping inactive teacher {$teacher->id} ({$teacher->biometric_id}) at school {$schoolId}");
                $log->update(['sync_status' => 'failed']);
                return;
            }

            if (preg_replace('/\D/', '', $teacher->biometric_id) !== $biometricId) {
                $teacher->biometric_id = $biometricId;
                $teacher->save();
            }
            $this->processTeacherAttendance($teacher, $punchTime, $timeOnly, $date, $log, $settings);
            return;
        }

        Log::warning("[Biometric] Unknown biometric_id: {$biometricId} at school {$schoolId}");
        $log->update(['sync_status' => 'failed']);
    }

    private function normalizeBiometricId(?string $biometricId): string
    {
        return trim(preg_replace('/\D/', '', (string) $biometricId));
    }

    /**
     * Fetch school settings from cache or DB (using sensible defaults if not configured).
     */
    private function getSettings(int $schoolId): object
    {
        if (isset($this->settingsCache[$schoolId])) {
            return $this->settingsCache[$schoolId];
        }

        $settings = SchoolAttendanceSetting::where('school_id', $schoolId)->first();

        if (!$settings) {
            // Return a default settings object if the school hasn't configured yet
            $settings = new SchoolAttendanceSetting([
                'student_entry_start'    => '07:00:00',
                'student_entry_end'      => '08:45:00',
                'student_late_threshold' => '09:30:00',
                'student_exit_start'     => '13:00:00',
                'student_exit_end'       => '15:00:00',
                'teacher_check_in_start' => '08:00:00',
                'teacher_check_in_end'   => '09:00:00',
                'teacher_late_threshold' => '09:30:00',
                'teacher_check_out_start'=> '14:00:00',
                'teacher_check_out_end'  => '17:00:00',
            ]);
        }

        $this->settingsCache[$schoolId] = $settings;
        return $settings;
    }

    /**
     * Check if a punch time falls within a start..end window.
     * Handles windows that span midnight (start > end).
     */
    private function isWithinWindow(Carbon $time, Carbon $start, Carbon $end): bool
    {
        if ($start->lte($end)) {
            return $time->between($start, $end, true);
        }
        // Window spans midnight: true if time >= start OR time <= end
        return $time->gte($start) || $time->lte($end);
    }

    // ──────────────────────────────────────────────────────────────────────────
    private function processStudentAttendance(
        Student $student, Carbon $punchTime, string $timeOnly, string $date,
        BiometricAttendanceLog $log, object $settings
    ): void {
        $entryEnd      = Carbon::parse($date . ' ' . $settings->student_entry_end);
        $lateThreshold = Carbon::parse($date . ' ' . $settings->student_late_threshold);
        $exitStart     = Carbon::parse($date . ' ' . $settings->student_exit_start);
        $exitEnd       = Carbon::parse($date . ' ' . $settings->student_exit_end);

        // Determine if this is an exit punch (based on configured exit window)
        $isExitWindow = $this->isWithinWindow($punchTime, $exitStart, $exitEnd);

        $attendance = Attendance::where('student_id', $student->id)
                        ->where('date', $date)
                        ->first();

        if (!$attendance) {
            // ── First punch of the day → Entry ──────────────────────────────
            $enrollment = $student->currentEnrollment;

            if (!$enrollment || !$enrollment->section_id) {
                Log::warning("[Biometric] Student {$student->id} has no current enrollment or section. Cannot save attendance.");
                $log->update(['sync_status' => 'failed']);
                return;
            }

            // Calculate status only based on entry time
            $status = 'present';
            if ($punchTime->gt($entryEnd) && $punchTime->lte($lateThreshold)) {
                $status = 'late';
            } elseif ($punchTime->gt($lateThreshold)) {
                $status = 'absent';
            }

            try {
                $attendance = DB::transaction(function () use ($student, $enrollment, $date, $status, $isExitWindow, $timeOnly) {
                    return Attendance::create([
                        'student_id'  => $student->id,
                        'school_id'   => $student->school_id,
                        'class_id'    => $enrollment->class_id ?? $student->class_id,
                        'section_id'  => $enrollment->section_id,
                        'date'        => $date,
                        'status'      => $status,
                        'entry_time'  => $isExitWindow ? null : $timeOnly, // Entry only if in entry window
                        'exit_time'   => $isExitWindow ? $timeOnly : null,  // Exit only if in exit window
                        'medium'      => 'biometric',
                        'recorded_by' => null,
                    ]);
                });

                // ── Send entry notification ───────────────────────────────────
                if (!$isExitWindow) {
                    \App\Jobs\SendAttendanceNotificationJob::dispatch($student, $attendance, 'entry');
                }
            } catch (QueryException $e) {
                if (!$this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }

                // Another concurrent punch already created today's attendance row
                // between our SELECT and INSERT. Treat as already processed and
                // fall through to the normal "update existing" path instead of failing.
                $attendance = Attendance::where('student_id', $student->id)
                    ->where('date', $date)
                    ->first();

                if (!$attendance) {
                    Log::error("[Biometric] Unique violation on attendance insert but row not found for student_id={$student->id} date={$date}: " . $e->getMessage());
                    $log->update(['sync_status' => 'failed']);
                    return;
                }

                $this->updateStudentAttendancePunch($attendance, $student, $isExitWindow, $timeOnly);
            }

        } else {
            $this->updateStudentAttendancePunch($attendance, $student, $isExitWindow, $timeOnly);
        }

        $log->update(['sync_status' => 'processed']);
    }

    /**
     * Apply a subsequent punch (entry/exit update) to an already-existing
     * attendance row for a student.
     */
    private function updateStudentAttendancePunch(Attendance $attendance, Student $student, bool $isExitWindow, string $timeOnly): void
    {
        $changed = false;

        if (!$isExitWindow) {
            // Update entry time if earlier than current
            if (!$attendance->entry_time || $timeOnly < $attendance->entry_time) {
                $attendance->entry_time = $timeOnly;
                $changed = true;
            }
        } else {
            // Update exit time if later than current
            $isNewExit = !$attendance->exit_time || $timeOnly > $attendance->exit_time;

            if ($isNewExit) {
                $attendance->exit_time = $timeOnly;
                $changed = true;

                // Send exit notification
                \App\Jobs\SendAttendanceNotificationJob::dispatch($student, $attendance, 'exit');
            }
        }

        // Upgrade medium to biometric if it was web/mobile
        if ($attendance->medium !== 'biometric') {
            $attendance->medium = 'biometric';
            $changed = true;
        }

        if ($changed) {
            $attendance->save();
        }
    }

    /**
     * Determine whether a QueryException is caused by a unique-constraint
     * violation (MySQL error code 1062 / SQLSTATE 23000), as opposed to
     * some other database error that should not be swallowed.
     */
    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        return $e->getCode() === '23000' || (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062);
    }

    // ──────────────────────────────────────────────────────────────────────────
    private function processTeacherAttendance(
        Teacher $teacher, Carbon $punchTime, string $timeOnly, string $date,
        BiometricAttendanceLog $log, object $settings
    ): void {
        $checkOutStart = Carbon::parse($date . ' ' . $settings->teacher_check_out_start);
        $checkOutEnd   = Carbon::parse($date . ' ' . $settings->teacher_check_out_end);
        $lateThreshold = Carbon::parse($date . ' ' . $settings->teacher_late_threshold);

        $isExitWindow = $this->isWithinWindow($punchTime, $checkOutStart, $checkOutEnd);

        $attendance = TeacherAttendance::where('user_id', $teacher->user_id)
                        ->where('school_id', $teacher->school_id)
                        ->where('date', $date)
                        ->first();

        if (!$attendance) {
            $status = 'present';
            if ($punchTime->gt(Carbon::parse($date . ' ' . $settings->teacher_check_in_end))
                && $punchTime->lte($lateThreshold)) {
                $status = 'late';
            } elseif ($punchTime->gt($lateThreshold)) {
                $status = 'present'; // Still record the punch even if late
            }

            try {
                DB::transaction(function () use ($teacher, $date, $isExitWindow, $timeOnly, $status) {
                    TeacherAttendance::create([
                        'user_id'        => $teacher->user_id,
                        'school_id'      => $teacher->school_id,
                        'date'           => $date,
                        'check_in_time'  => $isExitWindow ? null : $timeOnly,
                        'check_out_time' => $isExitWindow ? $timeOnly : null,
                        'status'         => $status,
                        'medium'         => 'biometric',
                    ]);
                });
            } catch (QueryException $e) {
                if (!$this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }

                // Another concurrent punch already created today's row.
                $attendance = TeacherAttendance::where('user_id', $teacher->user_id)
                    ->where('school_id', $teacher->school_id)
                    ->where('date', $date)
                    ->first();

                if (!$attendance) {
                    Log::error("[Biometric] Unique violation on teacher attendance insert but row not found for user_id={$teacher->user_id} date={$date}: " . $e->getMessage());
                    $log->update(['sync_status' => 'failed']);
                    return;
                }

                $this->updateTeacherAttendancePunch($attendance, $isExitWindow, $timeOnly);
            }
        } else {
            $this->updateTeacherAttendancePunch($attendance, $isExitWindow, $timeOnly);
        }

        $log->update(['sync_status' => 'processed']);
    }

    /**
     * Apply a subsequent punch (check-in/check-out update) to an
     * already-existing attendance row for a teacher.
     */
    private function updateTeacherAttendancePunch(TeacherAttendance $attendance, bool $isExitWindow, string $timeOnly): void
    {
        if ($isExitWindow) {
            // Update check-out if this punch is later
            if (!$attendance->check_out_time || $timeOnly > $attendance->check_out_time) {
                $attendance->check_out_time = $timeOnly;
            }
        } else {
            // Update check-in if this punch is earlier
            if (!$attendance->check_in_time || $timeOnly < $attendance->check_in_time) {
                $attendance->check_in_time = $timeOnly;
            }
        }
        if ($attendance->medium !== 'biometric') {
            $attendance->medium = 'biometric';
        }
        $attendance->save();
    }
}
