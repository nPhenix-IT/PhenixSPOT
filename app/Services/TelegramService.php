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
            $body = (string) $response->body();

            // Fallback robuste: certains messages dynamiques peuvent casser le parse HTML Telegram
            // (ex: balises incomplètes provenant d'un nom de pass/texte). On réessaie sans parse_mode.
            if (str_contains(strtolower($body), "can't parse entities")) {
                $fallbackResponse = Http::post($endpoint, [
                    'chat_id' => $chatId,
                    'text' => $message,
                ]);

                if ($fallbackResponse->successful()) {
                    return true;
                }

                Log::warning('Telegram fallback request failed.', [
                    'status' => $fallbackResponse->status(),
                    'body' => $fallbackResponse->body(),
                ]);
                return false;
            }

            Log::warning('Telegram request failed.', [
                'status' => $response->status(),
                // 'body' => $response->body(),
                'body' => $body,
            ]);
            return false;
        }

        return true;
    }
}
