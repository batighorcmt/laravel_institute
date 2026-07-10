<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAttendanceNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected Student $student;
    protected Attendance $attendance;
    protected string $punchType; // 'entry' or 'exit'

    public function __construct(Student $student, Attendance $attendance, string $punchType = 'entry')
    {
        $this->student = $student;
        $this->attendance = $attendance;
        $this->punchType = $punchType;
    }

    public function handle(PushNotificationService $notificationService): void
    {
        try {
            $student = $this->student;
            $attendance = $this->attendance;

            if (!$student->user_id) {
                return;
            }

            $statusMap = [
                'present' => 'উপস্থিত',
                'late'    => 'বিলম্বিত',
                'absent'  => 'অনুপস্থিত',
            ];
            $statusBn = $statusMap[$attendance->status] ?? $attendance->status;
            $dateStr = Carbon::parse($attendance->date)->format('d-m-Y');

            if ($this->punchType === 'entry') {
                $entryTime = $attendance->entry_time
                    ? Carbon::parse($attendance->entry_time)->format('h:i A')
                    : null;

                $title = '📍 বায়োমেট্রিক হাজিরা';
                $body = "{$student->full_name} আজ ({$dateStr}) স্কুলে প্রবেশ করেছে। স্ট্যাটাস: {$statusBn}" .
                    ($entryTime ? " | সময়: {$entryTime}" : '');
            } else {
                $exitTime = $attendance->exit_time
                    ? Carbon::parse($attendance->exit_time)->format('h:i A')
                    : null;
                $title = '🏫 স্কুল থেকে প্রস্থান';
                $body = "{$student->full_name} আজ ({$dateStr}) স্কুল থেকে বের হয়েছে।" .
                    ($exitTime ? " | সময়: {$exitTime}" : '');
            }

            $notificationService->sendAttendanceNotification(
                $student->id,
                $attendance->status,
                $attendance->date,
                'biometric'
            );

        } catch (\Throwable $e) {
            Log::error('Biometric attendance notification failed: ' . $e->getMessage(), [
                'student_id'    => $this->student->id ?? null,
                'attendance_id' => $this->attendance->id ?? null,
            ]);
        }
    }
}
