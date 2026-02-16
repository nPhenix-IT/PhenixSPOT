<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Profile;
use App\Models\User;
use Exception;

class MoneyFusionService
{
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.moneyfusion.api_url') ?? env('MONEYFUSION_API_URL') ?? 'https://api.moneyfusion.net/pay';
    }

    public function initiatePayment(
        User $user,
        Profile $profile,
        float $totalPrice,
        string $transactionId,
        string $returnUrl,
        string $webhookUrl,
        string $customerName,
        string $customerNumber
    ): array
    {
        $payload = [
            'totalPrice' => (int) $totalPrice,
            'article' => [[
                'name' => $profile->name,
                'price' => (int) $totalPrice,
            ]],
            'nomclient' => $customerName,
            'numeroSend' => $customerNumber,
            'return_url' => $returnUrl,
            'webhook_url' => $webhookUrl,
            'personal_Info' => [[
                'orderId' => $transactionId,
                'userId' => $user->id,
            ]],
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['statut'] ?? false) === true || isset($data['url'])) {
                    return $data;
                }
            }

            Log::error('MoneyFusion API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Impossible d\'initier le paiement avec Money Fusion.');
        } catch (Exception $e) {
            Log::error('MoneyFusion exception', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function checkStatus(string $tokenPay): array
    {
        $statusUrl = "https://www.pay.moneyfusion.net/paiementNotif/{$tokenPay}";
        $response = Http::get($statusUrl);
        if ($response->failed()) {
            Log::error('MoneyFusion status check failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        return $response->json(); // Retourne ['statut' => true, 'token' => '...', 'url' => '...']
    }
 
   public function initiateSimplePayment(
       User $user,
       int $amount,
       string $transactionId,
       string $returnUrl,
       string $webhookUrl,
       string $label = 'Compte VPN supplémentaire'
   ): array {
       $payload = [
           'totalPrice' => $amount,
           'article' => [[
               'name' => $label,
               'price' => $amount,
           ]],
           'nomclient' => $user->name,
           'numeroSend' => $user->phone_number ?? '00000000',
           'return_url' => $returnUrl,
           'webhook_url' => $webhookUrl,
           'personal_Info' => [[
               'orderId' => $transactionId,
               'userId' => $user->id,
           ]],
       ];

       $response = Http::withHeaders([
           'Content-Type' => 'application/json',
           'Accept' => 'application/json',
       ])->post($this->apiUrl, $payload);

       if ($response->successful()) {
           $data = $response->json();
           if (($data['statut'] ?? false) === true || isset($data['url'])) {
               return $data;
           }
       }

       Log::error('MoneyFusion simple payment error', [
           'status' => $response->status(),
           'body' => $response->body(),
       ]);
       throw new Exception('Impossible d\'initier le paiement MoneyFusion.');
   }

    public function isPaid(array $statusData): bool
    {
        $paymentStatus = $statusData['data']['statut'] ?? null;
        return ($statusData['statut'] ?? false) && $paymentStatus === 'paid';
    }
}