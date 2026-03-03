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

    public function sendToToken(string $token, string $title, string $body, array $data = []): array
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
                'data' => array_map('strval', $data), // FCM v1 requires all data values to be strings
            ],
        ];

        $resp = Http::withToken($accessToken)
            ->post($url, $payload);

        return [
            'success' => $resp->successful(),
            'status' => $resp->status(),
            'body' => $resp->json(),
        ];
    }
}
