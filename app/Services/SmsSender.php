<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class SmsSender
{
    public static function send(int $schoolId, string $to, string $message): bool
    {
        $apiUrl = Setting::forSchool($schoolId)->where('key','sms_api_url')->value('value');
        $apiKey = Setting::forSchool($schoolId)->where('key','sms_api_key')->value('value');
        $senderId = Setting::forSchool($schoolId)->where('key','sms_sender_id')->value('value');
        $masking = Setting::forSchool($schoolId)->where('key','sms_masking')->value('value');

        // If no API configured, simulate success to allow UI testing
        if (!$apiUrl || !$apiKey) {
            return true;
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
            if ($resp->successful()) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }
}
