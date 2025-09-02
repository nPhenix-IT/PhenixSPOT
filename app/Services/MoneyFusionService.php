<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MoneyFusionService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $secretKey;

    public function __construct(string $apiKey, string $secretKey)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey; // Bien que non utilisé dans l'API Web, c'est une bonne pratique de l'avoir
        $this->apiUrl = 'https://api.moneyfusion.net/pay'; // Remplacez par l'URL de production réelle
    }

    public function initiatePayment(int $amount, string $customerName, string $customerNumber, string $transactionId)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            // Ajoutez d'autres en-têtes si requis par Money Fusion, comme une clé API
        ])->post($this->apiUrl, [
            'totalPrice' => $amount,
            'article' => [['name' => 'Voucher WiFi', 'price' => $amount]],
            'nomclient' => $customerName,
            'numeroSend' => $customerNumber,
            'personal_Info' => [['transaction_id' => $transactionId]],
            'return_url' => route('public.payment.callback'),
            'webhook_url' => route('public.payment.webhook'),
        ]);

        if ($response->failed() || !$response->json('statut')) {
            // Gérer l'erreur, par exemple en lançant une exception
            throw new \Exception('Impossible d\'initier le paiement avec Money Fusion.');
        }

        return $response->json(); // Retourne ['statut' => true, 'token' => '...', 'url' => '...']
    }
}