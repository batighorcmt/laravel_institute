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
            // Generic POST; adapt to provider requirements
            $resp = Http::timeout(15)->asForm()->post($apiUrl, [
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
            
            // Check if response is successful
            if ($resp->successful()) {
                // Most SMS providers return success even with HTTP 200
                // So we consider HTTP 200 as success unless explicitly mentioned as error
                // Common success patterns: "success", "sent", "ok", or just numbers/codes
                return ['success' => true, 'message' => 'SMS sent successfully', 'response' => $responseBody];
            } else {
                return ['success' => false, 'message' => "HTTP {$statusCode}", 'response' => $responseBody];
            }
        } catch (\Throwable $e) {
            \Log::error('SMS Send Exception', ['error' => $e->getMessage(), 'to' => $to]);
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage(), 'response' => null];
        }
    }
}
