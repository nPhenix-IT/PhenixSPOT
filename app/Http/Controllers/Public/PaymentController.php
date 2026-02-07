<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PendingTransaction;
use App\Models\Voucher;
use App\Models\Radcheck;
use App\Models\Radusergroup;
use App\Models\Transaction;
use App\Models\User;
use App\Services\KingSmsService;
use App\Services\TelegramService;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Gère la notification webhook de Money Fusion.
     * C'est la méthode la plus fiable pour confirmer un paiement.
     */
    public function webhook(Request $request)
    {
        // Enregistrer la requête entrante pour le débogage
        Log::info('Webhook Money Fusion reçu :', $request->all());

        $data = $request->all();
        $event = $data['event'] ?? null;
        $transactionId = $data['personal_Info'][0]['transaction_id'] ?? null;

        // On ne traite que les événements de paiement complété et si on a notre ID de transaction
        if ($event === 'payin.session.completed' && $transactionId) {
            $pendingTransaction = PendingTransaction::where('transaction_id', $transactionId)->first();

            // Vérifier si la transaction existe et est bien en attente pour éviter les doublons
            if ($pendingTransaction && $pendingTransaction->status === 'pending') {
                
                // DB::transaction(function () use ($pendingTransaction) {
                $profile = null;
                $code = null;

                DB::transaction(function () use ($pendingTransaction, &$profile, &$code) {
                    $user = User::find($pendingTransaction->user_id);
                    $profile = Profile::find($pendingTransaction->profile_id);

                    // 1. Créer le voucher
                    function genererCodeAleatoire($longueur = 6) {
                      $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                      $code = '';
                      for ($i = 0; $i < $longueur; $i++) {
                        $code .= $caracteres[rand(0, strlen($caracteres) - 1)];
                      }
                      return $code;
                    }
                    
                    $code = genererCodeAleatoire();
                    // $code = Str::random(8);
                    Voucher::create([
                        'user_id' => $user->id,
                        'profile_id' => $profile->id,
                        'code' => $code,
                    ]);

                    // 2. L'ajouter à FreeRADIUS
                    Radcheck::create(['username' => $code, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $code]);
                    Radusergroup::create(['username' => $code, 'groupname' => $profile->name]);

                    // 3. Créditer le portefeuille du vendeur
                    $wallet = $user->wallet;
                    // $wallet->balance += $profile->price;
                    $creditAmount = $profile->price;
                    if ($pendingTransaction->commission_payer === 'seller') {
                        $creditAmount = max(0, $profile->price - $pendingTransaction->commission_amount);
                    }
                    $wallet->balance += $creditAmount;
                    $wallet->save();

                    // 4. Enregistrer la transaction de crédit
                    Transaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'credit',
                        // 'amount' => $profile->price,
                        'amount' => $creditAmount,
                        'description' => 'Vente du voucher ' . $code,
                    ]);

                    // 5. Mettre à jour la transaction en attente comme complétée
                    $pendingTransaction->update(['status' => 'completed']);
                });

                $user = User::find($pendingTransaction->user_id);
                if ($user && $user->sms_enabled && $pendingTransaction->customer_number && $code && $profile) {
                    $message = "Votre code WiFi est: {$code}. Profil: {$profile->name}.";
                    $smsSender = $user->sms_sender ?: null;
                    app(KingSmsService::class)->sendSms($pendingTransaction->customer_number, $message, $smsSender);
                }

                if ($user && $user->telegram_bot_token && $user->telegram_chat_id && $code && $profile) {
                    $telegramMessage = "✅ <b>Nouvelle vente</b>\n";
                    $telegramMessage .= "Profil: {$profile->name}\n";
                    $telegramMessage .= "Code: {$code}\n";
                    $telegramMessage .= "Montant: {$profile->price} FCFA\n";
                    if ($pendingTransaction->customer_number) {
                        $telegramMessage .= "Client: {$pendingTransaction->customer_number}\n";
                    }
                    app(TelegramService::class)->sendMessage(
                        $user->telegram_bot_token,
                        $user->telegram_chat_id,
                        $telegramMessage
                    );
                }
            }
        }

        // Toujours retourner une réponse 200 OK pour que le webhook ne soit pas renvoyé
        return response()->json(['status' => 'success']);
    }

    /**
     * Gère le retour du client après le paiement.
     */
    public function callback(Request $request)
    {
        // Cette page est une simple confirmation pour le client.
        // La logique métier est gérée par le webhook pour plus de sécurité.
        $pageConfigs = ['myLayout' => 'blank'];
        return view('content.public.payment_status', compact('pageConfigs'));
    }
}
