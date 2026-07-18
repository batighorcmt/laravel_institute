<?php

namespace App\Jobs;

use App\Models\BiometricAttendanceLog;
use App\Services\Biometric\AttendanceProcessingEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBiometricPunchJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $log;

    /**
     * Create a new job instance.
     */
    public function __construct(BiometricAttendanceLog $log)
    {
        $this->log = $log;
    }

    /**
     * Execute the job.
     */
    public function handle(AttendanceProcessingEngine $engine): void
    {
        try {
            $engine->processPunch($this->log);
        } catch (\Throwable $e) {
            Log::error("[Biometric] ProcessBiometricPunchJob failed for log_id={$this->log->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure (after retries are exhausted or an unhandled
     * exception bubbles up). Make sure the corresponding BiometricAttendanceLog
     * row is not left stuck at 'pending' forever, which skews admin dashboard counts.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("[Biometric] ProcessBiometricPunchJob permanently failed for log_id={$this->log->id}: " . $exception->getMessage());

        $this->log->update(['sync_status' => 'failed']);
    }
}
