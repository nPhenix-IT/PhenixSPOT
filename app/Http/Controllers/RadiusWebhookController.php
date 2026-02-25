<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Transaction;
use App\Models\User;
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
            $section = $request->header('X-FreeRadius-Section');
            $username = $request->input('User-Name.value.0') ?? $request->input('User-Name');

            // Correction : On ne rejette plus si le nom d'utilisateur est absent lors de l'accounting
            // (Utile pour les paquets Accounting-On / Accounting-Off du NAS)
            if (!$username && !str_contains($section, 'accounting')) {
                return response()->json(['reply:Reply-Message' => 'Nom d\'utilisateur manquant'], 400);
            }

            switch (true) {
                case str_contains($section, 'authorize'):
                    return $this->handleAuthorize($username);

                case str_contains($section, 'post-auth'):
                    return $this->handlePostAuth($username);

                case str_contains($section, 'accounting'):
                    // Log optionnel pour le debug
                    if (!$username) {
                        Log::info("Accounting global reçu du NAS: " . $request->input('NAS-IP-Address'));
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
     * Vérification des limites avec messages d'erreur conviviaux.
     */
    private function handleAuthorize($username)
    {
        $voucher = Voucher::where('code', $username)
            ->with(['profile'])
            ->first();

        // ERREUR : Code inexistant ou désactivé
        if (!$voucher || !$voucher->is_active) {
            return response()->json([
                'control:Auth-Type' => 'Reject',
                'reply:Reply-Message' => 'Code invalide. Achetez-en un nouveau !'
            ], 200);
        }

        $profile = $voucher->profile;

        $usage = DB::table('radacct')
            ->where('username', $username)
            ->select(
                DB::raw('SUM(acctsessiontime) as total_time'),
                DB::raw('SUM(acctinputoctets + acctoutputoctets) as total_data')
            )
            ->first();

        $totalDataUsed = (int) ($usage->total_data ?? 0);
        $totalTimeUsed = (int) ($usage->total_time ?? 0);

        // 1. ERREUR : VALIDITÉ (Expiration calendaire)
        if ($voucher->status === 'used' && $voucher->used_at && $profile->validity_period > 0) {
            $expirationDate = Carbon::parse($voucher->used_at)->addSeconds($profile->validity_period);
            if (now()->greaterThan($expirationDate)) {
                return response()->json([
                    'control:Auth-Type' => 'Reject',
                    'reply:Reply-Message' => 'Code expiré. Achetez-en un nouveau pour continuer !'
                ], 200);
            }
        }

        // 2. ERREUR : QUOTA DATA (Volume de données)
        if ($profile->data_limit > 0 && $totalDataUsed >= $profile->data_limit) {
            return response()->json([
                'control:Auth-Type' => 'Reject',
                'reply:Reply-Message' => 'Volume épuisé ! 🚀 Rachetez un ticket pour continuer.'
            ], 200);
        }

        // 3. ERREUR : LIMITE TEMPS (Durée de connexion)
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
            // --- AJOUTS ATTRIBUTS UTILES ---
            'reply:Acct-Interim-Interval' => 60, // Mise à jour des stats toutes les 60s
            'reply:Idle-Timeout' => 300,        // Déconnexion après 5min d'inactivité
        ];

        // Calcul du temps restant pour Session-Timeout
        if ($profile->session_timeout > 0) {
            $remainingTime = (int) max(0, $profile->session_timeout - $totalTimeUsed);
            $response['reply:Session-Timeout'] = $remainingTime;
        } else {
            $response['reply:Session-Timeout'] = 604800; // 1 semaine par défaut si illimité
        }

        // Calcul de la Data restante pour MikroTik
        if ($profile->data_limit > 0) {
            $remainingData = (int) ($profile->data_limit - $totalDataUsed);
            $response['reply:Mikrotik-Total-Limit'] = $remainingData;
        }

        // Gestion de l'expiration calendaire stricte (WISPr)
        if ($voucher->used_at && $profile->validity_period > 0) {
            $expiration = Carbon::parse($voucher->used_at)->addSeconds($profile->validity_period);
            $response['reply:WISPr-Session-Terminate-Time'] = $expiration->toIso8601String();
            
            // Message d'information sur la date d'expiration pour la page status.html
            $response['reply:Reply-Message'] = "Expire le: " . $expiration->format('d/m H:i');
        } else {
            $response['reply:Reply-Message'] = "Bienvenue ! Connexion etablie.";
        }

        return response()->json($response, 200);
    }

    /**
     * Phase Post-Auth : Crédit unique + Notification Telegram.
     */
    private function handlePostAuth($username)
    {
        $voucher = Voucher::where('code', $username)
            ->where('status', 'new')
            ->with(['user.wallet', 'profile'])
            ->first();

        if ($voucher) {
            try {
                DB::transaction(function () use ($voucher, $username) {
                    $voucher->update([
                        'status' => 'used',
                        'used_at' => now()
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