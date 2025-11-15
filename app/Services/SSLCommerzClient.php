<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SSLCommerzClient
{
    public function initiate(array $payload, bool $sandbox = true): ?string
    {
        $url = $sandbox ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php' : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
        $response = Http::asForm()->post($url, $payload);
        if ($response->successful()) {
            $data = $response->json();
            return $data['GatewayPageURL'] ?? null;
        }
        return null;
    }
}
