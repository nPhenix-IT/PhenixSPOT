<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $botToken, string $chatId, string $message): bool
    {
        if (!$botToken || !$chatId) {
            Log::warning('Telegram not configured.');
            return false;
        }

        $endpoint = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $response = Http::post($endpoint, [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);

        if ($response->failed()) {
            Log::warning('Telegram request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }
}
