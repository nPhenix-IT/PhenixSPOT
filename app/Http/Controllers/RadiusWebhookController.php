<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RadiusWebhookController extends Controller
{
    /**
     * Gère les requêtes entrantes de FreeRADIUS.
     */
    public function handle(Request $request)
    {
        try {
            Log::info('Webhook FreeRADIUS Reçu:', $request->all());

            $action = $request->header('X-FreeRadius-Section');
            $username = $request->input('username');

            if (!$username) {
                return response()->json(['Reply-Message' => 'Username is required'], 400);
            }

            switch ($action) {
                case 'authorize':
                    return $this->authorizeRequest($username);

                case 'authenticate':
                    return $this->authenticateRequest($username);

                case 'post-auth':
                    $this->postAuth($username);
                    return response()->json(['status' => 'success']);
                
                case 'accounting':
                    Log::info('Requête Accounting reçue pour : '.$username, $request->all());
                    return response()->json(['status' => 'success']);

                default:
                    Log::warning('Webhook: Action non gérée', ['action' => $action]);
                    return response()->json(['status' => 'action not handled']);
            }
        } catch (\Throwable $e) {
            Log::error('Erreur fatale dans RadiusWebhookController: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Retourne un rejet RADIUS générique pour éviter que FreeRADIUS ne se bloque
            return response()->json(['control:Auth-Type' => 'Reject', 'reply:Reply-Message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Vérifie si un utilisateur est autorisé et renvoie ses attributs.
     */
    private function authorizeRequest($username)
    {
        $voucher = Voucher::where('code', $username)->where('status', 'new')->where('is_active', true)->with('profile')->first();

        if (!$voucher || !$voucher->profile) {
            Log::warning("Webhook Authorize: Voucher non trouvé, inactif ou déjà utilisé pour: " . $username);
            return response()->json(['control:Auth-Type' => 'Reject', 'reply:Reply-Message' => 'Voucher invalide ou expire.'], 200);
        }

        $profile = $voucher->profile;
        $attributes = [
            'control:Auth-Type' => 'Accept',
            'reply:Simultaneous-Use' => $profile->device_limit,
        ];

        if ($profile->session_timeout > 0) {
            $attributes['reply:Session-Timeout'] = $profile->session_timeout;
        }

        if ($profile->data_limit > 0) {
            $attributes['reply:Mikrotik-Total-Limit'] = $profile->data_limit;
        }

        if (!empty($profile->rate_limit)) {
            $attributes['reply:Mikrotik-Rate-Limit'] = $profile->rate_limit;
        }

        Log::info("Webhook Authorize: Autorisation accordée pour " . $username, $attributes);
        return response()->json($attributes, 200);
    }

    /**
     * Confirme l'authentification de l'utilisateur.
     */
    private function authenticateRequest($username)
    {
        $voucher = Voucher::where('code', $username)->where('status', 'new')->where('is_active', true)->first();

        if ($voucher) {
            // Un code 204 "No Content" est interprété comme un succès par rlm_rest pour l'authentification.
            return response()->noContent();
        }

        Log::warning("Webhook Authenticate: Voucher non trouvé pour: " . $username);
        return response()->json(['control:Auth-Type' => 'Reject'], 200);
    }

    /**
     * Met à jour le statut du voucher et crédite le portefeuille après une connexion réussie.
     */
    private function postAuth($username)
    {
        $voucher = Voucher::where('code', $username)->where('status', 'new')->with('user.wallet', 'profile')->first();

        if (!$voucher || !$voucher->user || !$voucher->user->wallet || !$voucher->profile) {
            Log::info("Webhook Post-Auth: Voucher ou ses relations non trouvés pour: " . $username);
            return;
        }

        try {
            DB::transaction(function () use ($voucher) {
                $voucher->status = 'used';
                $voucher->used_at = now();
                $voucher->save();

                $wallet = $voucher->user->wallet;
                $price = $voucher->profile->price;
                $wallet->balance += $price;
                $wallet->save();

                Transaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $price,
                    'description' => 'Vente du voucher ' . $voucher->code,
                ]);

                Log::info("Webhook Post-Auth: Voucher " . $voucher->code . " traité. Portefeuille de l'utilisateur " . $voucher->user->id . " crédité de " . $price);
            });
        } catch (\Exception $e) {
            Log::error("Webhook Post-Auth: Erreur lors du traitement du voucher " . $username . " : " . $e->getMessage());
        }
    }
}
