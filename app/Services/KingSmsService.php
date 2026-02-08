<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KingSmsService
{
    public function sendSms(string $to, string $message, ?string $sender = null): bool
    {
        $baseUrl = rtrim(config('services.kingsmspro.base_url'), '/');
        $endpoint = $baseUrl . '/sms/send';
        $apiKey = config('services.kingsmspro.api_key');
        $clientId = config('services.kingsmspro.client_id');
        $defaultSender = config('services.kingsmspro.sender');
        $dlr = config('services.kingsmspro.dlr', 'no');
        $dlrUrl = config('services.kingsmspro.dlr_url');

        if (!$baseUrl || !$apiKey || !$clientId) {
            Log::warning('KingSmsPro not configured.');
            return false;
        }

        $payload = [
            'from' => $sender ?: $defaultSender,
            'to' => $to,
            'message' => $message,
            'type' => 0,
            'dlr' => $dlr,
        ];

        if (!empty($dlrUrl)) {
            $payload['url'] = $dlrUrl;
        }

        $response = Http::withHeaders([
            'APIKEY' => $apiKey,
            'CLIENTID' => $clientId,
            'Content-Type' => 'application/json',
        ])->post($endpoint, $payload);

        if ($response->failed()) {
            Log::warning('KingSmsPro request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }
}
