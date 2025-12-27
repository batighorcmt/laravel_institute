<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\SmsSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 90, 180];

    public function __construct(
        protected int $schoolId,
        protected int $sentByUserId,
        /** @var array<int,array{mobile:string,message:string,meta:array}> */
        protected array $chunk
    ) {}

    public function handle(): void
    {
        foreach ($this->chunk as $item) {
            $mobile = $item['mobile'];
            $message = $item['message'];
            $meta = $item['meta'] ?? [];

            try {
                $result = SmsSender::send($this->schoolId, $mobile, $message);
                $ok = (bool)($result['success'] ?? false);
                $respMsg = $result['message'] ?? '';
                $respBody = $result['response'] ?? null;

                // Simple local backoff for 429 from provider
                if (!$ok && str_starts_with((string)$respMsg, 'HTTP 429')) {
                    usleep(500_000); // 0.5s pause
                    $result = SmsSender::send($this->schoolId, $mobile, $message);
                    $ok = (bool)($result['success'] ?? false);
                    $respMsg = $result['message'] ?? $respMsg;
                    $respBody = $result['response'] ?? $respBody;
                }

                SmsLog::create([
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

                // Small pacing to avoid provider flood
                usleep(150_000); // 0.15s
            } catch (\Throwable $e) {
                \Log::error('SMS Send Exception', ['error' => $e->getMessage(), 'mobile' => $mobile]);
                SmsLog::create([
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
            }
        }
    }
}
