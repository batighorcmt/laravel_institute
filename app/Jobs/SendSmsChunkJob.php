<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\SmsSender;
use Illuminate\Support\Facades\Log;

class SendSmsChunkJob implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public $tries = 3;
    public $backoff = [30, 90, 180];

    /** @var int */
    protected $schoolId;
    /** @var int */
    protected $sentByUserId;
    /** @var array */
    protected $chunk;
    /** @var int */
    protected $attemptNumber;

    /**
     * @param int $schoolId
     * @param int $sentByUserId
     * @param array $chunk array<int,array{mobile:string,message:string,meta:array}>
     */
    public function __construct($schoolId, $sentByUserId, array $chunk, $attemptNumber = 1)
    {
        $this->schoolId = (int) $schoolId;
        $this->sentByUserId = (int) $sentByUserId;
        $this->chunk = $chunk;
        $this->attemptNumber = (int) $attemptNumber;
    }

    public function handle(): void
    {
        $perMessageUsleep = (int) (\env('SMS_PER_MESSAGE_USLEEP', 1000000)); // default 1s
        $failedItems = [];
        foreach ($this->chunk as $item) {
            $mobile = $item['mobile'];
            $message = $item['message'];
            $meta = $item['meta'] ?? [];

            try {
                $result = SmsSender::send($this->schoolId, $mobile, $message);
                $ok = (bool)($result['success'] ?? false);
                $respMsg = $result['message'] ?? '';
                $respBody = $result['response'] ?? null;

                // Simple local backoff for 429 from provider (compatible with PHP < 8)
                if (!$ok && strpos((string)$respMsg, 'HTTP 429') === 0) {
                    usleep(500000); // 0.5s pause
                    $result = SmsSender::send($this->schoolId, $mobile, $message);
                    $ok = (bool)($result['success'] ?? false);
                    $respMsg = $result['message'] ?? $respMsg;
                    $respBody = $result['response'] ?? $respBody;
                }

                SmsLog::query()->create([
                    'school_id' => $this->schoolId,
                    'sent_by_user_id' => $this->sentByUserId,
                    'recipient_type' => $meta['recipient_type'] ?? null,
                    'recipient_category' => $meta['recipient_category'] ?? null,
                    'recipient_id' => $meta['recipient_id'] ?? null,
                    'recipient_name' => $meta['recipient_name'] ?? null,
                    'recipient_role' => $meta['recipient_role'] ?? null,
                    'roll_number' => $meta['roll_number'] ?? null,
                    'class_name' => $meta['class_name'] ?? null,
                    'section_name' => $meta['section_name'] ?? null,
                    'recipient_number' => $mobile,
                    'message' => $message,
                    'status' => $ok ? 'sent' : 'failed',
                    'response' => ($respMsg ?: '') . (isset($respBody) ? ' | ' . substr((string)$respBody, 0, 200) : ''),
                    'message_type' => 'result_notification',
                ]);

                if (!$ok) {
                    $failedItems[] = $item;
                }

                // Pacing to avoid provider flood
                if ($perMessageUsleep > 0) { usleep($perMessageUsleep); }
            } catch (\Throwable $e) {
                Log::error('SMS Send Exception', ['error' => $e->getMessage(), 'mobile' => $mobile]);
                SmsLog::query()->create([
                    'school_id' => $this->schoolId,
                    'sent_by_user_id' => $this->sentByUserId,
                    'recipient_type' => $meta['recipient_type'] ?? null,
                    'recipient_category' => $meta['recipient_category'] ?? null,
                    'recipient_id' => $meta['recipient_id'] ?? null,
                    'recipient_name' => $meta['recipient_name'] ?? null,
                    'recipient_role' => $meta['recipient_role'] ?? null,
                    'roll_number' => $meta['roll_number'] ?? null,
                    'class_name' => $meta['class_name'] ?? null,
                    'section_name' => $meta['section_name'] ?? null,
                    'recipient_number' => $mobile,
                    'message' => $message,
                    'status' => 'failed',
                    'response' => 'Exception: ' . $e->getMessage(),
                    'message_type' => 'result_notification',
                ]);
                $failedItems[] = $item;
            }
        }

        // If any failed, re-dispatch just the failed ones with delay, up to max attempts
        $maxAttempts = (int) (\env('SMS_MAX_RETRY_ATTEMPTS', 3));
        $retryDelay = (int) (\env('SMS_RETRY_FAILED_DELAY_SEC', 300));
        if (!empty($failedItems) && $this->attemptNumber < $maxAttempts) {
            Log::info('Re-dispatching failed SMS chunk', [
                'failed_count' => count($failedItems),
                'attempt' => $this->attemptNumber + 1,
                'school_id' => $this->schoolId,
            ]);
            \App\Jobs\SendSmsChunkJob::dispatch($this->schoolId, $this->sentByUserId, $failedItems, $this->attemptNumber + 1)
                ->delay(now()->addSeconds(max(0, $retryDelay)));
        }
    }
}
