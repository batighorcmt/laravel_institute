<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class FcmService
{
    protected string $serviceAccountPath;
    protected string $projectId;

    public function __construct()
    {
        $this->serviceAccountPath = config('fcm.service_account_path');
        $json = json_decode(file_get_contents($this->serviceAccountPath), true);
        $this->projectId = $json['project_id'];
    }

    protected function getAccessToken(): string
    {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $this->serviceAccountPath);
        $token = $credentials->fetchAuthToken(HttpHandlerFactory::build());
        return $token['access_token'];
    }

    public function sendToToken(string $token, string $title, string $body, array $data = [], ?int $userId = null, ?int $noticeId = null): array
    {
        $accessToken = $this->getAccessToken();
        
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map('strval', $data),
                'android' => [
                    'notification' => [
                        'sound' => 'notice_sound',
                        'channel_id' => 'notice_channel_v1',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'notice_sound.mp3',
                        ],
                    ],
                ],
            ],
        ];

        $resp = Http::withToken($accessToken)
            ->post($url, $payload);

        $success = $resp->successful();

        // Log to database
        try {
            \App\Models\NotificationLog::create([
                'notice_id' => $noticeId,
                'user_id' => $userId,
                'device_token' => $token,
                'title' => $title,
                'body' => $body,
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $success ? null : ($resp->json('error.message') ?? 'Unknown Error'),
                'response_payload' => $resp->json(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('FCM Log Error: ' . $e->getMessage());
        }

        // If FCM says the token is unregistered/invalid (not a transient error), remove it so
        // future notifications stop retrying against a dead token.
        if (! $success && $this->isUnregisteredTokenError($resp)) {
            try {
                \App\Models\DeviceToken::where('token', $token)->delete();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('FCM DeviceToken cleanup error: ' . $e->getMessage());
            }
        }

        return [
            'success' => $success,
            'status' => $resp->status(),
            'body' => $resp->json(),
        ];
    }

    /**
     * Determine whether an FCM v1 error response indicates the token is
     * definitively dead (unregistered/invalid), as opposed to a transient
     * failure (network issue, quota, internal error, etc.).
     */
    protected function isUnregisteredTokenError($resp): bool
    {
        // FCM v1 "unregistered" errors surface as HTTP 404 with a FcmError
        // detail whose errorCode is UNREGISTERED.
        $errorCode = null;
        $details = $resp->json('error.details') ?? [];
        foreach ($details as $detail) {
            if (isset($detail['errorCode'])) {
                $errorCode = $detail['errorCode'];
                break;
            }
        }

        if ($errorCode === 'UNREGISTERED') {
            return true;
        }

        // Fallback: HTTP 404 "Requested entity was not found" also means the
        // token/registration no longer exists.
        if ($resp->status() === 404) {
            return true;
        }

        return false;
    }
}
