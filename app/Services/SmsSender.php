<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class SmsSender
{
    public static function send(int $schoolId, string $to, string $message): array
    {
        $apiUrl = Setting::forSchool($schoolId)->where('key','sms_api_url')->value('value');
        $apiKey = Setting::forSchool($schoolId)->where('key','sms_api_key')->value('value');
        $senderId = Setting::forSchool($schoolId)->where('key','sms_sender_id')->value('value');
        $masking = Setting::forSchool($schoolId)->where('key','sms_masking')->value('value');

        // If no API configured, simulate success to allow UI testing
        if (!$apiUrl || !$apiKey) {
            return ['success' => true, 'message' => 'SMS API not configured (test mode)', 'response' => null];
        }

        try {
            // Configurable HTTP timeouts and small retry to handle transient provider slowness
            // Set conservative defaults to avoid long blocking requests in web flows.
            // These can be overridden via .env if needed.
            $timeout = (int) (env('SMS_HTTP_TIMEOUT', 10)); // seconds
            $connectTimeout = (int) (env('SMS_HTTP_CONNECT_TIMEOUT', 3)); // seconds
            $retryAttempts = (int) (env('SMS_HTTP_RETRY_ATTEMPTS', 1));
            $retrySleepMillis = (int) (env('SMS_HTTP_RETRY_SLEEP_MS', 200));

            // Generic POST; adapt to provider requirements
            $resp = Http::retry($retryAttempts, $retrySleepMillis)
                ->withOptions(['connect_timeout' => $connectTimeout])
                ->timeout($timeout)
                ->asForm()
                ->post($apiUrl, [
                    'api_key' => $apiKey,
                    'senderid' => $senderId,
                    'masking' => $masking,
                    'number' => $to,
                    'message' => $message,
                ]);

            $responseBody = $resp->body();
            $statusCode = $resp->status();

            // Log for debugging
            \Log::info('SMS API Response', [
                'status' => $statusCode,
                'body' => $responseBody,
                'to' => $to
            ]);

            // Basic success determination from HTTP status
            $ok = $resp->successful();
            $message = $ok ? 'SMS sent successfully' : "HTTP {$statusCode}";

            // Try to detect provider-level signals inside JSON response bodies.
            // Prefer an explicit `success_message` when present (many providers use non-zero codes
            // but include a success_message). Fall back to `error_message` or response body heuristics.
            $json = @json_decode($responseBody, true);
            if (is_array($json)) {
                if (!empty($json['success_message'])) {
                    $ok = true;
                    $message = 'Provider success: ' . (string)$json['success_message'];
                } elseif (!empty($json['error_message'])) {
                    $ok = false;
                    $message = 'Provider error: ' . (string)$json['error_message'];
                } elseif (isset($json['response_code']) && is_numeric($json['response_code'])) {
                    $code = (int)$json['response_code'];
                    if ($code === 0) {
                        $ok = true;
                        $message = 'Provider response_code: 0';
                    } else {
                        // Ambiguous numeric code without explicit success_message -> treat as failure
                        $ok = false;
                        $message = 'Provider response_code: ' . (string)$code;
                    }
                }
            } else {
                // Heuristics for plain-text responses
                $lower = strtolower($responseBody);
                if (stripos($lower, 'unsuccess') !== false || stripos($lower, 'not enough balance') !== false) {
                    $ok = false;
                    $message = 'Provider reported failure: ' . substr($responseBody, 0, 200);
                } elseif (stripos($lower, 'sms submitted') !== false || stripos($lower, 'submitted successfully') !== false) {
                    $ok = true;
                    $message = 'Provider reported success: ' . substr($responseBody, 0, 200);
                }
            }

            return ['success' => $ok, 'message' => $message, 'response' => $responseBody];
        } catch (\Throwable $e) {
            \Log::error('SMS Send Exception', ['error' => $e->getMessage(), 'to' => $to]);
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage(), 'response' => null];
        }
    }
}
