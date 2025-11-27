<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FcmService
{
    protected string $serverKey;

    public function __construct()
    {
        $this->serverKey = config('fcm.server_key');
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
        ];
        $resp = Http::withHeaders([
            'Authorization' => 'key '.$this->serverKey,
            'Content-Type' => 'application/json'
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);
        return [
            'success' => $resp->successful(),
            'status' => $resp->status(),
            'body' => $resp->json(),
        ];
    }
}
