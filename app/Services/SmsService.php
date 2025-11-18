<?php

namespace App\Services;

use App\Models\School;
use App\Models\Setting;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /** @var School */
    protected $school;
    protected $apiUrl;
    protected $apiKey;
    protected $senderId;

    public function __construct(School $school)
    {
        $this->school = $school;
        $settings = Setting::forSchool($school->id)->where('key', 'like', 'sms_%')->pluck('value', 'key');
        $this->apiUrl = $settings->get('sms_api_url');
        $this->apiKey = $settings->get('sms_api_key');
        $this->senderId = $settings->get('sms_sender_id');
    }

    public function sendSms($recipient, $message)
    {
        if (!$this->apiUrl || !$this->apiKey || !$this->senderId) {
            Log::error("SMS settings are not configured for school ID: {$this->school->id}");
            return false;
        }

        try {
            $response = Http::get($this->apiUrl, [
                'api_key' => $this->apiKey,
                'senderid' => $this->senderId,
                'number' => $recipient,
                'message' => $message,
            ]);

            SmsLog::create([
                'school_id' => $this->school->id,
                'recipient_number' => $recipient,
                'message' => $message,
                'status' => $response->successful() ? 'success' : 'failed',
                'response' => $response->body(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("SMS sending failed for school ID: {$this->school->id}. Error: " . $e->getMessage());
            SmsLog::create([
                'school_id' => $this->school->id,
                'recipient_number' => $recipient,
                'message' => $message,
                'status' => 'failed',
                'response' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
