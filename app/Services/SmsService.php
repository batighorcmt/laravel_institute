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

    /**
     * Send an SMS and persist log with optional message type meta.
     * @param string $recipient Mobile number
     * @param string $message Text content
     * @param string|null $messageType e.g. admission_accept|admission_reject
     * @param array $extra Additional log fields (whitelisted)
     */
    public function sendSms($recipient, $message, $messageType = null, array $extra = [])
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

            SmsLog::create(array_filter([
                'school_id' => $this->school->id,
                'recipient_number' => $recipient,
                'message' => $message,
                'status' => $response->successful() ? 'success' : 'failed',
                'response' => $response->body(),
                'message_type' => $messageType,
            ]) + $extra);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("SMS sending failed for school ID: {$this->school->id}. Error: " . $e->getMessage());
            SmsLog::create(array_filter([
                'school_id' => $this->school->id,
                'recipient_number' => $recipient,
                'message' => $message,
                'status' => 'failed',
                'response' => $e->getMessage(),
                'message_type' => $messageType,
            ]) + $extra);
            return false;
        }
    }
}
