<?php

namespace App\Services;

use App\Jobs\SendSmsChunkJob;

class SmsDispatch
{
    /**
     * Dispatch SMS payload chunks (sync or queued based on config).
     *
     * @param  array<int, array{mobile: string, message: string, meta?: array}>  $payloads
     * @return int Number of chunks dispatched
     */
    public static function dispatchChunks(int $schoolId, ?int $sentByUserId, array $payloads, int $attemptNumber = 1): int
    {
        if ($payloads === []) {
            return 0;
        }

        $chunkSize = max(1, (int) config('sms.chunk_size', 50));
        $chunks = array_chunk($payloads, $chunkSize);
        $batchDelaySec = (int) config('sms.batch_delay_sec', 5);

        foreach ($chunks as $idx => $chunk) {
            static::dispatchChunk(
                $schoolId,
                $sentByUserId,
                $chunk,
                $attemptNumber,
                $idx * $batchDelaySec
            );
        }

        return count($chunks);
    }

    /**
     * @param  array<int, array{mobile: string, message: string, meta?: array}>  $chunk
     */
    public static function dispatchChunk(int $schoolId, ?int $sentByUserId, array $chunk, int $attemptNumber = 1, int $delaySeconds = 0): void
    {
        if ($chunk === []) {
            return;
        }

        if (static::shouldSendImmediately()) {
            (new SendSmsChunkJob($schoolId, $sentByUserId ?? 0, $chunk, $attemptNumber))->handle();

            return;
        }

        $dispatch = SendSmsChunkJob::dispatch($schoolId, $sentByUserId, $chunk, $attemptNumber);

        if ($delaySeconds > 0) {
            $dispatch->delay(now()->addSeconds($delaySeconds));
        }
    }

    public static function shouldSendImmediately(): bool
    {
        if (config('queue.default') === 'sync') {
            return true;
        }

        return (bool) config('sms.send_immediately', true);
    }
}
