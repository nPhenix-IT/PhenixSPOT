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
    
    /**
     * Initie un retrait (payout) MoneyFusion.
     *
     * @return array<string,mixed>
     *
     * @throws \Exception
     */
    public function initiateWithdrawal(
        string $countryCode,
        string $phone,
        float|int $amount,
        string $withdrawMode,
        ?string $webhookUrl = null
    ): array {
        $endpoint = config('services.moneyfusion.withdrawal_url', 'https://pay.moneyfusion.net/api/v1/withdraw');

        $payload = [
            'countryCode' => strtolower($countryCode),
            'phone' => $phone,
            'amount' => (int) $amount,
            'withdraw_mode' => $withdrawMode,
        ];

        if (!empty($webhookUrl)) {
            $payload['webhook_url'] = $webhookUrl;
        }

        $apiKey = $this->getWithdrawalApiKey();

        try {
            $response = Http::withHeaders([
                'moneyfusion-private-key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['statut'] ?? false) === true && !empty($data['tokenPay'] ?? null)) {
                    return $data;
                }
            }

            Log::error('MoneyFusion withdrawal API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            $message = $response->json('message') ?? 'Impossible d\'initier le retrait MoneyFusion.';
            throw new Exception($message);
        } catch (Exception $e) {
            Log::error('MoneyFusion withdrawal exception', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Convertit le moyen de paiement enregistré en mode payout MoneyFusion.
     */
    public function resolveWithdrawMode(?string $method, ?string $countryCode = null): string
    {
        $normalizedMethod = strtolower(trim((string) $method));
        $normalizedMethod = str_replace(['é', 'è', 'ê', 'ô', 'ï', ' '], ['e', 'e', 'e', 'o', 'i', '-'], $normalizedMethod);

        $country = strtolower(trim((string) ($countryCode ?: 'ci')));

        $map = [
            'ci' => [
                'orange' => 'orange-money-ci',
                'mtn' => 'mtn-ci',
                'moov' => 'moov-ci',
                'wave' => 'wave-ci',
            ],
            'sn' => [
                'orange' => 'orange-money-senegal',
                'free' => 'free-money-senegal',
                'wave' => 'wave-senegal',
                'expresso' => 'expresso-senegal',
            ],
            'bf' => [
                'orange' => 'orange-money-burkina',
                'moov' => 'moov-burkina-faso',
            ],
            'bj' => [
                'mtn' => 'mtn-benin',
                'moov' => 'moov-benin',
            ],
            'tg' => [
                't-money' => 't-money-togo',
                'moov' => 'moov-togo',
            ],
            'ml' => [
                'orange' => 'orange-money-mali',
            ],
            'cg' => [
                'orange' => 'orange-money-mali',
                'mtn' => 'mtn-cg',
            ],
            'cm' => [
                'orange' => 'orange-money-cm',
                'mtn' => 'mtn-cm',
            ],
            'cd' => [
                'airtel' => 'airtel-money-cd',
            ],
            'ga' => [
                'airtel' => 'airtel-money-ga',
                'libertis' => 'libertis-ga',
            ],
            'gh' => [
                'airtel' => 'airtel-money-gh',
                'mtn' => 'mtn-gh',
                'vodafone' => 'vodafone-gh',
            ],
            'gn' => [
                'orange' => 'orange-gn',
                'mtn' => 'mtn-gn',
            ],
            'gw' => [
                'mtn' => 'mtn-gw',
            ],
            'ke' => [
                'm-pesa' => 'm-pesa-ke',
                'mpesa' => 'm-pesa-ke',
            ],
            'mr' => [
                'bankily' => 'bankily-mr',
            ],
            'ne' => [
                'airtel' => 'airtel-money-ne',
                'mtn' => 'mtn-ne',
                'mauritel' => 'mauritel-ne',
            ],
            'ug' => [
                'mtn' => 'mtn-ug',
            ],
            'cf' => [
                'orange' => 'orange-cf',
            ],
            'rw' => [
                'mtn' => 'mtn-rw',
            ],
            'sl' => [
                'africell' => 'africell-sl',
                'orange' => 'orange-sl',
            ],
            'tz' => [
                'airtel' => 'airtel-money-tz',
                'm-pesa' => 'm-pesa-tz',
                'mpesa' => 'm-pesa-tz',
                'tigo' => 'tigo-tz',
            ],
            'td' => [
                'airtel' => 'airtel-money-td',
                'moov' => 'moov-td',
            ],
            'gm' => [
                'orange' => 'orange-gm',
            ],
            'et' => [
                'safaricom' => 'safaricom-et',
            ],
        ];

        if (!isset($map[$country])) {
            throw new Exception('Pays non supporté pour retrait MoneyFusion: ' . strtoupper($country));
        }

        foreach ($map[$country] as $needle => $withdrawMode) {
            if (str_contains($normalizedMethod, $needle)) {
                return $withdrawMode;
            }
        }

        throw new Exception('Moyen de retrait non supporté pour ' . strtoupper($country) . ': ' . ($method ?: 'N/A'));
    }

    /**
     * Normalise un event webhook payout vers un statut interne.
     */
    public function mapWithdrawalWebhookEvent(?string $event): ?string
    {
        if (!$event) {
            return null;
        }

        return match ($event) {
            'payout.session.completed' => 'completed',
            'payout.session.cancelled' => 'cancelled',
            default => null,
        };
    }

    /**
     * Détermine si un webhook payout annonce un succès.
     */
    public function isWithdrawalCompleted(array $webhookPayload): bool
    {
        return ($webhookPayload['event'] ?? null) === 'payout.session.completed';
    }

    /**
     * Récupère la clé API privée des retraits.
     *
     * @throws \Exception
     */
    protected function getWithdrawalApiKey(): string
    {
        $key = config('services.moneyfusion.private_key');

        if (!$key) {
            throw new Exception('Clé API MoneyFusion payout manquante. Configurez MONEYFUSION_PRIVATE_KEY.');
        }

        return $key;
    }
}