<?php

namespace App\Jobs;

use App\Models\BiometricAttendanceLog;
use App\Services\Biometric\AttendanceProcessingEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $engine->processPunch($this->log);
    }
}
