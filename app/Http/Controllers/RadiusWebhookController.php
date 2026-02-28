<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Router;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RadiusWebhookController extends Controller
{
    /**
     * Point d'entrée principal pour FreeRADIUS.
     */
    public function handle(Request $request)
    {
        try {
            $section = $this->extractScalar($request->header('X-FreeRadius-Section')) ?? '';
            $username = $this->extractScalar(
                $request->input('User-Name.value.0') ?? $request->input('User-Name')
            );

            // Correction : On ne rejette plus si le nom d'utilisateur est absent lors de l'accounting
            // (Utile pour les paquets Accounting-On / Accounting-Off du NAS)
            if (!$username && !str_contains($section, 'accounting')) {
                return response()->json(['reply:Reply-Message' => 'Nom d\'utilisateur manquant'], 400);
            }

            switch (true) {
                case str_contains($section, 'authorize'):
                    return $this->handleAuthorize($username);

                case str_contains($section, 'post-auth'):
                    return $this->handlePostAuth($username, $request);

                case str_contains($section, 'accounting'):
                    // Log optionnel pour le debug
                    if (!$username) {
                        $nasIp = $this->extractScalar($request->input('NAS-IP-Address'));
                        Log::info('Accounting global reçu du NAS', [
                            'nas_ip' => $nasIp,
                            'section' => $section,
                        ]);
                    }
                    return response()->json(['control:Auth-Type' => 'Accept']);

                default:
                    return response()->json(['control:Auth-Type' => 'Accept']);
            }

        } catch (\Throwable $e) {
            Log::error('Radius Webhook Error: ' . $e->getMessage());
            return response()->json(['control:Auth-Type' => 'Reject'], 500);
        }
    }

    /**
     * Normalise les valeurs potentiellement envoyées par FreeRADIUS en tableaux imbriqués.
     */
    private function extractScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) || is_numeric($value)) {
            $normalized = trim((string) $value);
            return $normalized === '' ? null : $normalized;
        }

        if (is_array($value)) {
            if (array_key_exists('value', $value)) {
                return $this->extractScalar($value['value']);
            }

            $first = reset($value);
            return $this->extractScalar($first);
        }

        return null;
    }

    /**
     * Vérification des limites avec messages d'erreur conviviaux.
     */
    private function handleAuthorize($username)
    {
        $voucher = Voucher::where('code', $username)
            ->with(['profile'])
            ->first();

        // 1. ERREUR : Code inexistant ou désactivé
        if (!$voucher || !$voucher->is_active) {
            return response()->json([
                'control:Auth-Type' => 'Reject',
                'reply:Reply-Message' => 'Code invalide. Achetez-en un nouveau !'
            ], 200);
        }

        $profile = $voucher->profile;

        // 2. ERREUR : VALIDITÉ CALENDAIRE (On vérifie avant le reste)
        if ($voucher->status === 'used' && $voucher->used_at && $profile->validity_period > 0) {
            $expirationDate = Carbon::parse($voucher->used_at)->addSeconds($profile->validity_period);
            if (now()->greaterThan($expirationDate)) {
                return response()->json([
                    'control:Auth-Type' => 'Reject',
                    'reply:Reply-Message' => 'Code expiré. Achetez-en un nouveau pour continuer !'
                ], 200);
            }
        }

        // --- VÉRIFICATION : NOMBRE DE SESSIONS SIMULTANÉES ---
        if ($profile && $profile->device_limit > 0) {
            // On compte les sessions qui n'ont pas de 'acctstoptime'
            // Optionnel : On filtre sur les sessions des dernières 24h pour ignorer les vieux bugs de déco
            $activeSessionsCount = DB::table('radacct')
                ->where('username', $username)
                ->whereNull('acctstoptime')
                ->where('acctstarttime', '>', now()->subDay())
                ->count();

            if ($activeSessionsCount >= $profile->device_limit) {
                return response()->json([
                    'control:Auth-Type' => 'Reject',
                    'reply:Reply-Message' => "Limite d'appareils atteinte ({$profile->device_limit} max). Deconnectez l'autre appareil !"
                ], 200);
            }
        }

        $usage = DB::table('radacct')
            ->where('username', $username)
            ->select(
                DB::raw('SUM(acctsessiontime) as total_time'),
                DB::raw('SUM(acctinputoctets + acctoutputoctets) as total_data')
            )
            ->first();

        $totalDataUsed = (int) ($usage->total_data ?? 0);
        $totalTimeUsed = (int) ($usage->total_time ?? 0);

        // 3. ERREUR : QUOTA DATA
        if ($profile->data_limit > 0 && $totalDataUsed >= $profile->data_limit) {
            return response()->json([
                'control:Auth-Type' => 'Reject',
                'reply:Reply-Message' => 'Volume épuisé ! 🚀 Rachetez un ticket pour continuer.'
            ], 200);
        }

        // 4. ERREUR : LIMITE TEMPS
        if ($profile->session_timeout > 0 && $totalTimeUsed >= $profile->session_timeout) {
            return response()->json([
                'control:Auth-Type' => 'Reject',
                'reply:Reply-Message' => 'Temps fini ! ⏳ Achetez un ticket pour rester en ligne.'
            ], 200);
        }

        // Préparation de la réponse de succès
        $response = [
            'control:Auth-Type' => 'Accept',
            'reply:Mikrotik-Rate-Limit' => $profile->rate_limit ?? '10M/10M',
            
            // CONFIGURATION ACCOUNTING
            'reply:Acct-Interim-Interval' => 60, 
            'reply:Idle-Timeout' => 300,        
        ];

        // Session-Timeout dynamique
        if ($profile->session_timeout > 0) {
            $remainingTime = (int) max(0, $profile->session_timeout - $totalTimeUsed);
            $response['reply:Session-Timeout'] = $remainingTime;
        } else {
            $response['reply:Session-Timeout'] = 604800; // 1 semaine
        }

        // Mikrotik-Total-Limit
        if ($profile->data_limit > 0) {
            $remainingData = (int) ($profile->data_limit - $totalDataUsed);
            $response['reply:Mikrotik-Total-Limit'] = $remainingData;
        }

        // WISPr & Message d'accueil
        if ($voucher->used_at && $profile->validity_period > 0) {
            $expiration = Carbon::parse($voucher->used_at)->addSeconds($profile->validity_period);
            $response['reply:WISPr-Session-Terminate-Time'] = $expiration->toIso8601String();
            $response['reply:Reply-Message'] = "Expire le: " . $expiration->format('d/m H:i');
        } else {
            $response['reply:Reply-Message'] = "Bienvenue ! Connexion etablie.";
        }

        return response()->json($response, 200);
    }

    /**
     * Phase Post-Auth : Crédit unique + Notification Telegram.
     */
    private function handlePostAuth($username, Request $request)
    {
        $voucher = Voucher::where('code', $username)
            ->where('status', 'new')
            ->with(['user.wallet', 'profile'])
            ->first();

        if ($voucher) {
            try {
                $nasIp = $this->extractScalar(
                    $request->input('NAS-IP-Address.value.0') ?? $request->input('NAS-IP-Address')
                );
                $nasIdentifier = $this->extractScalar(
                    $request->input('NAS-Identifier.value.0') ?? $request->input('NAS-Identifier')
                );

                $router = null;
                if ($nasIp) {
                    $router = Router::where('ip_address', $nasIp)
                        ->orWhere('api_address', $nasIp)
                        ->first();
                }

                DB::transaction(function () use ($voucher, $username, $router, $nasIp, $nasIdentifier) {
                    $voucher->update([
                        'status' => 'used',
                        'used_at' => now(),
                        'activated_router_id' => $router?->id,
                        'activated_router_ip' => $nasIp,
                        'activation_nas_identifier' => $nasIdentifier,
                    ]);

                    if ($voucher->source === 'manual_generation' && 
                        $voucher->user && 
                        $voucher->user->wallet && 
                        $voucher->profile && 
                        $voucher->profile->price > 0) {
                        
                        $amount = $voucher->profile->price;
                        $user = $voucher->user;
                        $wallet = $user->wallet;
                        
                        $wallet->increment('balance', $amount);
                        
                        Transaction::create([
                            'wallet_id' => $wallet->id,
                            'type' => 'credit',
                            'amount' => $amount,
                            'description' => "Vente code physique: {$voucher->code}",
                        ]);

                        $this->sendTelegramNotification($user, $voucher, $wallet->fresh()->balance);
                        
                        Log::info("Crédit et Notification Telegram effectués pour: $username");
                    }
                });

                Log::info('Voucher first activation router captured', [
                    'voucher_code' => $voucher->code,
                    'username' => $username,
                    'router_id' => $router?->id,
                    'router_ip' => $nasIp,
                    'nas_identifier' => $nasIdentifier,
                ]);
            } catch (\Exception $e) {
                Log::error("Erreur Post-Auth pour $username: " . $e->getMessage());
            }
        }

        return response()->json(['control:Auth-Type' => 'Accept']);
    }

    /**
     * Envoie la notification de vente au vendeur via Telegram.
     */
    private function sendTelegramNotification($user, $voucher, $currentBalance)
    {
        if ($user->telegram_bot_token && $user->telegram_chat_id) {
            $profile = $voucher->profile;
            
            $telegramMessage = "🛒 <b>Nouvelle vente - Ticket Physique</b>\n\n";
            $telegramMessage .= "Pass: {$profile->name}\n";
            $telegramMessage .= "Code: <code>{$voucher->code}</code>\n";
            $telegramMessage .= "Gain: <b>" . number_format($profile->price, 0, ',', ' ') . " FCFA</b>\n\n";
            
            if ($currentBalance !== null) {
                $telegramMessage .= "💰 <b>Nouveau solde: " . number_format($currentBalance, 0, ',', ' ') . " FCFA</b>\n";
            }

            try {
                app(TelegramService::class)->sendMessage(
                    $user->telegram_bot_token,
                    $user->telegram_chat_id,
                    $telegramMessage
                );
            } catch (\Exception $e) {
                Log::warning("Erreur Telegram vendeur {$user->name}: " . $e->getMessage());
            }
        }
    }
}