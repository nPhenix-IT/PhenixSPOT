<?php

 namespace App\Http\Controllers\User;
 
 use App\Http\Controllers\Controller;
use App\Models\PendingVpnAccountPayment;
use App\Models\Transaction;
 use App\Models\VpnAccount;
 use App\Models\VpnServer;
 use App\Services\MikroTikApiService;
use App\Services\MoneyFusionService;
use Carbon\Carbon;
 use Illuminate\Http\Request;
 use Illuminate\Support\Facades\Auth;
 use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
 
 class VpnAccountController extends Controller
 {
     private function isUnlimitedValue($value): bool
   {
       if ($value === null) {
           return false;
       }

       $normalized = strtolower(trim((string) $value));
       return in_array($normalized, ['-1', 'illimite', 'illimité', 'unlimited', 'infini', 'infinite', '∞'], true);
   }

   private function normalizeLimit($value): int
   {
       if ($this->isUnlimitedValue($value)) {
           return PHP_INT_MAX;
       }

       if (is_numeric($value)) {
           return max(0, (int) $value);
       }

       return 0;
   }

   private function resolveVpnLimit(array $planFeatures): int
   {
       $rawLimit = $planFeatures['vpn_accounts'] ?? ($planFeatures['routers'] ?? 0);
       return $this->normalizeLimit($rawLimit);
   }

   public function index()
    {
        $user = Auth::user();
    
        $accounts = VpnAccount::where('user_id', $user->id)
            ->with('server')
            ->orderByDesc('created_at')
            ->paginate(5)
            ->withQueryString();
    
        // 🔥 Optimisation ici
        $servers = VpnServer::where('is_online', true)
            ->where('is_active', true)
            ->withCount('accounts')
            ->get()
            ->filter(function ($server) {
                $max = $server->max_accounts ?? PHP_INT_MAX;
                return $server->accounts_count < $max;
            });
    
        $hasActiveSubscription =
            $user->hasRole(['Super-admin', 'Admin'])
            || ($user->subscription && $user->subscription->isActive());
    
        $planFeatures = $hasActiveSubscription
            ? ($user->hasRole(['Super-admin', 'Admin'])
                ? ['vpn_accounts' => PHP_INT_MAX]
                : $user->subscription->plan->features)
            : [];
    
        $vpnAccountCount = $user->vpnAccounts()
            ->where('status', 'active')
            ->count();
    
        $limit = $this->resolveVpnLimit($planFeatures);
        $isAtLimit = $limit !== PHP_INT_MAX && $limit > 0 ? $vpnAccountCount >= $limit : false;
        $limitLabel = $limit === PHP_INT_MAX ? 'Illimité' : number_format($limit, 0, ',', ' ');
        $usagePercent = ($limit !== PHP_INT_MAX && $limit > 0)
            ? min(100, ($vpnAccountCount / $limit) * 100)
            : 0;
        
        $accounts->getCollection()->transform(function ($account) {

            $expires = now()->addMinutes(10)->timestamp;
            $token = $this->generateScriptToken($account, $expires);
        
            $account->script_loader_url = route('vpn.script.loader', [
                'account' => $account->id,
                'token' => $token,
                'expires' => $expires
            ]);
        
            return $account;
        });
    
        return view(
            'content.vpn.index',
            compact(
                'accounts',
                'servers',
                'hasActiveSubscription',
                'vpnAccountCount',
                'limit',
                'limitLabel',
                'usagePercent',
                'isAtLimit'
            )
        );
    }
 
     public function store(Request $request)
     {
         $user = Auth::user();
       $request->validate([
           'server_id' => 'required|exists:vpn_servers,id',
           'protocol' => 'required|in:l2tp,ovpn,sstp',
           'duration' => 'required|in:1,3,6,12',
           'use_custom_port' => 'nullable|in:on,1',
           'custom_port_number' => 'nullable|required_if:use_custom_port,on|integer|min:1|max:65535',
           'commentaire' => 'nullable|string|max:255',
           'payment_method' => 'nullable|in:wallet,moneyfusion',
         ]);
 
       $hasActiveSubscription = $user->hasRole(['Super-admin', 'Admin']) || ($user->subscription && $user->subscription->isActive());
       if (!$hasActiveSubscription) {
           return back()->with('error', 'Votre abonnement est inactif. Veuillez activer un forfait.');
       }

       $planFeatures = $user->hasRole(['Super-admin', 'Admin']) ? ['vpn_accounts' => PHP_INT_MAX] : ($user->subscription->plan->features ?? []);
       $limit = $this->resolveVpnLimit($planFeatures);
       $activeCount = $user->vpnAccounts()->where('status', 'active')->count();
       $isSupplementary = $limit !== PHP_INT_MAX && $limit > 0 ? $activeCount >= $limit : false;

       $duration = (int) $request->duration;
       $supplementaryCost = $isSupplementary ? (500 * $duration) : 0;
       $customPortCost = $request->has('use_custom_port') ? (200 * $duration) : 0;
       $totalCharge = $supplementaryCost + $customPortCost;
       $paymentMethod = $request->input('payment_method', 'wallet');

       if ($totalCharge > 0 && $paymentMethod === 'moneyfusion') {
           return $this->initiateMoneyFusionPayment($request, $user, $totalCharge, $duration);
       }

       $wallet = $user->wallet;
       if ($totalCharge > 0 && (!$wallet || $wallet->balance < $totalCharge)) {
           return back()->with('error', "Solde insuffisant ({$totalCharge} FCFA requis). Vous pouvez choisir MoneyFusion.");
       }

       return $this->createVpnAccount($request, $user, $isSupplementary, $totalCharge, true);
   }

   public function moneyfusionCallback(Request $request)
   {
       $user = Auth::user();
       $transactionId = $request->query('transaction_id');

       if (!$transactionId) {
           return redirect()->route('user.vpn.index')->with('error', 'Transaction introuvable.');
       }

       $pending = PendingVpnAccountPayment::where('transaction_id', $transactionId)
           ->where('user_id', $user->id)
           ->first();

       if (!$pending) {
           return redirect()->route('user.vpn.index')->with('error', 'Paiement VPN non trouvé.');
       }

       if ($pending->status === 'completed') {
           return redirect()->route('user.vpn.index')->with('success', 'Paiement déjà validé.');
       }

       if (!$pending->payment_token) {
           return redirect()->route('user.vpn.index')->with('error', 'Token de paiement manquant.');
       }

       $moneyFusion = app(MoneyFusionService::class);
       $statusData = $moneyFusion->checkStatus($pending->payment_token);

       if (!$moneyFusion->isPaid($statusData)) {
           return redirect()->route('user.vpn.index')->with('error', 'Le paiement MoneyFusion n\'a pas été confirmé.');
       }

       $payload = $pending->payload ?? [];
       $requestData = new Request($payload);
       $requestData->setUserResolver(fn () => $user);

       $result = $this->createVpnAccount($requestData, $user, true, (int) $pending->amount, false);
 
       if ($result['ok']) {
           $pending->update(['status' => 'completed']);
           return redirect()->route('user.vpn.index')->with('success', 'Tunnel VPN créé après paiement MoneyFusion !');
       }

       $pending->update(['status' => 'failed']);
       return redirect()->route('user.vpn.index')->with('error', $result['message']);
   }

   public function update(Request $request, $id)
   {
       $account = VpnAccount::where('user_id', Auth::id())->findOrFail($id);
       $request->validate(['commentaire' => 'nullable|string|max:255']);
       $account->update(['commentaire' => $request->commentaire]);

       return back()->with('success', 'Identification du routeur mise à jour avec succès.');
   }

   public function checkOnlineStatus($id)
   {
         try {
           $account = VpnAccount::with('server')->findOrFail($id);
           if ($account->user_id !== Auth::id() && !Auth::user()->hasRole(['admin', 'super-admin', 'Admin', 'Super-admin'])) {
               return response()->json(['error' => 'Unauthorized'], 403);
             }
             
           $apiService = new MikroTikApiService($account->server);
           $status = $apiService->checkOnlineStatus($account->username);

           return response()->json([
               'is_online' => $status['is_online'],
               'server_name' => $account->server->name,
               'remote_ip' => $status['remote_ip'],
           ]);
       } catch (\Throwable $e) {
           Log::error("Erreur Check VPN Status #{$id}: " . $e->getMessage());
           return response()->json(['is_online' => false, 'error' => 'Erreur de communication avec le serveur'], 500);
       }
   }

   public function renew(Request $request, $id)
   {
       $user = Auth::user();
       $account = VpnAccount::where('user_id', $user->id)->with('server')->findOrFail($id);
       $request->validate(['duration' => 'required|in:1,3,6,12']);

       $duration = (int) $request->duration;
       $renewCost = $account->is_supplementary ? (500 * $duration) : 0;
       $wallet = $user->wallet;

       if ($renewCost > 0 && (!$wallet || $wallet->balance < $renewCost)) {
           return back()->with('error', "Solde insuffisant ({$renewCost} FCFA requis). ");
       }

       DB::beginTransaction();
       try {
           $currentExpiry = $account->expires_at && $account->expires_at->isFuture() ? $account->expires_at : Carbon::now();
           $newExpiry = $currentExpiry->copy()->addMonths($duration);

           $account->update([
               'expires_at' => $newExpiry,
               'duration_months' => $duration,
               'status' => 'active',
               'is_active' => true,
           ]);

           if ($renewCost > 0) {
               $wallet->decrement('balance', $renewCost);
               Transaction::create([
                   'wallet_id' => $wallet->id,
                   'type' => 'debit',
                   'amount' => $renewCost,
                   'description' => "Renouvellement compte VPN supplémentaire #{$account->id} ({$duration} mois)",
                 ]);
           }

           try {
               (new MikroTikApiService($account->server))->reactivateVpnUser($account->username);
           } catch (\Throwable $mikroTikError) {
               Log::warning("MikroTik Reactivation Error for {$account->username}: " . $mikroTikError->getMessage());
           }
           
           DB::commit();
           return back()->with('success', 'Abonnement prolongé jusqu\'au ' . $newExpiry->format('d/m/Y'));
       } catch (\Throwable $e) {
           DB::rollBack();
           Log::error('Erreur Renouvellement: ' . $e->getMessage());
           return back()->with('error', 'Erreur lors du renouvellement.');
         }
   }

   public function updatePorts(Request $request, $id)
   {
       $account = VpnAccount::where('user_id', Auth::id())->with('server')->findOrFail($id);

       $request->validate([
           'port_api_target' => 'required|integer|min:1|max:65535',
           'port_winbox_target' => 'required|integer|min:1|max:65535',
           'port_web_target' => 'required|integer|min:1|max:65535',
           'port_custom_target' => 'nullable|integer|min:1|max:65535',
       ]);

       try {
           $apiService = new MikroTikApiService($account->server);
           $apiService->updateNatRule("{$account->username}-NAT-API", (int) $request->port_api_target);
           $apiService->updateNatRule("{$account->username}-NAT-Winbox", (int) $request->port_winbox_target);
           $apiService->updateNatRule("{$account->username}-NAT-Web", (int) $request->port_web_target);
 
           if ($request->filled('port_custom_target') && $account->port_custom) {
               $apiService->updateNatRule("{$account->username}-NAT-Custom", (int) $request->port_custom_target);
           }

           $account->update([
               'remote_port_api' => $request->port_api_target,
               'remote_port_winbox' => $request->port_winbox_target,
               'remote_port_web' => $request->port_web_target,
               'remote_port_custom' => $request->port_custom_target ?? $account->remote_port_custom,
           ]);

           return back()->with('success', 'Redirections mises à jour sur le routeur et enregistrées avec succès !');
       } catch (\Throwable $e) {
           Log::error('Erreur Update Ports: ' . $e->getMessage());
           return back()->with('error', 'Erreur de connexion au routeur ou lors de la mise à jour.');
       }
    }
 
     public function destroy(VpnAccount $vpnAccount)
     {
       if ($vpnAccount->user_id !== Auth::id()) {
           abort(403);
       }

       DB::transaction(function () use ($vpnAccount) {
           if ($vpnAccount->server) {
               (new MikroTikApiService($vpnAccount->server))->deleteVpnUserAndNat($vpnAccount->username);
           }
           $vpnAccount->delete();
       });

       return back()->with('success', 'Compte VPN supprimé.');
    }


   public function scriptLoader(Request $request, int $account)
    {
        $vpnAccount = $this->resolveScriptAccount($account, $request);
    
        if (!$vpnAccount) {
            abort(403, 'Invalid or expired token.');
        }
    
        $coreUrl = route('vpn.script.core', [
            'account' => $vpnAccount->id,
            'token' => $request->token,
            'expires' => $request->expires
        ]);
    
        $loader = <<<RSC
/tool fetch url="$coreUrl" mode=https check-certificate=no dst-path=vpn-core.rsc;
:delay 1s;
/import vpn-core.rsc;
/file remove vpn-core.rsc;
:log info "Installation du VPN terminée";
RSC;

        return response($loader, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

   public function scriptCore(Request $request, int $account)
    {
        $vpnAccount = $this->resolveScriptAccount($account, $request);
    
        if (!$vpnAccount) {
            abort(403, 'Invalid or expired token.');
        }
    
        $profileName = $vpnAccount->server->profile_name ?? 'default';
        $vpnName     = "VPN-" . $vpnAccount->username;
        $vpnUser     = $vpnAccount->username;
        $vpnPass     = $vpnAccount->password;
        $vpnHost     = $vpnAccount->server->domain_name ?: $vpnAccount->server->ip_address;
        $vpnIP       = $vpnAccount->server->local_ip_address;
        $commentTag  = config('variables.templateName') ?? 'CLOUD';
    
        $core = <<<RSC
# ==========================================
#        CLOUD VPN AUTO CONFIG
# ==========================================

:local profileName "$profileName";
:local vpnName "$vpnName";
:local vpnUser "$vpnUser";
:local vpnPass "$vpnPass";
:local vpnHost "$vpnHost";
:local vpnIP "$vpnIP";
:local commentTag "CLOUD";
:local tag (\$commentTag . "-VPN-MONITOR");

# -------------------------------
# CREATE PPP PROFILE
# -------------------------------
:if ([:len [/ppp profile find where name=\$profileName]] = 0) do={
    /ppp profile add name=\$profileName comment=("PROFILE-" . \$vpnUser);
}

# -------------------------------
# CREATE L2TP CLIENT
# -------------------------------
:if ([:len [/interface l2tp-client find where name=\$vpnName]] = 0) do={
    /interface l2tp-client add \
        name=\$vpnName \
        user=\$vpnUser \
        password=\$vpnPass \
        connect-to=\$vpnHost \
        profile=\$profileName \
        use-ipsec=yes \
        ipsec-secret="Str0ngP@ssWord" \
        keepalive-timeout=10 \
        use-peer-dns=no \
        add-default-route=no \
        disabled=no \
        comment=("L2TP-" . \$vpnUser);
}

:delay 2s

# -------------------------------
# NAT MASQUERADE
# -------------------------------
:if ([:len [/ip firewall nat find where out-interface=\$vpnName]] = 0) do={
    /ip firewall nat add \
        chain=srcnat \
        out-interface=\$vpnName \
        action=masquerade \
        comment=(\$commentTag . "-NAT-CLOUD-VPN");
}

:delay 2s

# -------------------------------
# NETWATCH AUTO RECONNECT
# -------------------------------
:if ([:len [/tool netwatch find where comment=(\$vpnName . "-NETWATCH")]] = 0) do={
    /tool netwatch add \
        host=\$vpnIP \
        interval=30s \
        timeout=5s \
        down-script="/interface l2tp-client disable \$vpnName; :delay 3s; /interface l2tp-client enable \$vpnName" \
        up-script=":log info \\"VPN connection restored\\"" \
        comment=(\$vpnName . "-NETWATCH");
}

# -------------------------------
# GLOBAL ROBUST MONITOR SCRIPT (ROS v7 SAFE)
# -------------------------------

:if ([:len [/system script find where name="VPN-GLOBAL-MONITOR"]] = 0) do={

    /system script add \
        name="VPN-GLOBAL-MONITOR" \
        policy=read,write,test \
        comment=\$tag \
        source=":foreach i in=[/interface l2tp-client find] do={ \
                    :local name [/interface l2tp-client get \\\$i name]; \
                    :if ([:pick \\\$name 0 4] = \"VPN-\") do={ \
                        :local disabled [/interface l2tp-client get \\\$i disabled]; \
                        :local running [/interface l2tp-client get \\\$i running]; \
                        :if (\\\$disabled = true) do={ \
                            :log warning (\"VPN \" . \\\$name . \" disabled → enabling\"); \
                            /interface l2tp-client enable \\\$i; \
                        }; \
                        :if ((\\\$disabled = false) && (\\\$running = false)) do={ \
                            :log warning (\"VPN \" . \\\$name . \" down → restarting\"); \
                            /interface l2tp-client disable \\\$i; \
                            :delay 3s; \
                            /interface l2tp-client enable \\\$i; \
                        }; \
                    }; \
                }";
}

# -------------------------------
# GLOBAL SCHEDULER
# -------------------------------

:if ([:len [/system scheduler find where name="VPN-GLOBAL-MONITOR"]] = 0) do={
    /system scheduler add \
        name="VPN-GLOBAL-MONITOR" \
        interval=5m \
        on-event="/system script run VPN-GLOBAL-MONITOR" \
        policy=read,write,test \
        comment=\$tag;
}

:log info ("Cloud VPN setup completed for " . \$vpnName);

# ==========================================
#        END CONFIG
# ==========================================
RSC;

    
        return response($core, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }



   private function resolveScriptAccount(int $accountId, Request $request): ?VpnAccount
   {
       $token = (string) $request->query('token');
       $expires = (int) $request->query('expires');

       if (!$token || !$expires || $expires < time()) {
           return null;
       }

       $account = VpnAccount::with('server')->find($accountId);

       if (!$account) {
           return null;
       }

       $expectedToken = $this->generateScriptToken($account, $expires);
       if (!hash_equals($expectedToken, $token)) {
           return null;
       }

       $account->script_core_url = route('vpn.script.core', ['account' => $account->id, 'token' => $token, 'expires' => $expires]);

       return $account;
   }

   private function generateScriptToken(VpnAccount $account, int $expires): string
   {
       $payload = $account->id . '|' . $account->user_id . '|' . $expires;
       return hash_hmac('sha256', $payload, config('app.key'));
   }

   private function initiateMoneyFusionPayment(Request $request, $user, int $amount, int $duration)
   {
       $transactionId = 'VPN-SUP-' . Str::upper(Str::random(10));

       $pending = PendingVpnAccountPayment::create([
           'transaction_id' => $transactionId,
           'user_id' => $user->id,
           'amount' => $amount,
           'duration' => $duration,
           'payload' => [
               'server_id' => $request->server_id,
               'protocol' => $request->protocol,
               'duration' => $request->duration,
               'use_custom_port' => $request->use_custom_port,
               'custom_port_number' => $request->custom_port_number,
               'commentaire' => $request->commentaire,
           ],
           'status' => 'pending',
       ]);

       $moneyFusion = app(MoneyFusionService::class);
       $returnUrl = route('user.vpn.payment-callback', ['transaction_id' => $transactionId]);
       $webhookUrl = route('public.payment.webhook');

         try {
           $response = $moneyFusion->initiateSimplePayment(
               $user,
               $amount,
               $transactionId,
               $returnUrl,
               $webhookUrl,
               'Compte VPN supplémentaire'
           );

           $pending->update(['payment_token' => $response['tokenPay'] ?? null]);

           $paymentUrl = $response['url']
               ?? $response['payment_url']
               ?? $response['redirect_url']
               ?? (isset($response['tokenPay']) ? 'https://www.pay.moneyfusion.net/pay/' . $response['tokenPay'] : null)
               ?? (isset($response['token']) ? 'https://www.pay.moneyfusion.net/pay/' . $response['token'] : null)
               ?? data_get($response, 'data.url')
               ?? $returnUrl;

           return redirect()->away($paymentUrl);
       } catch (\Throwable $e) {
           $pending->update(['status' => 'failed']);
           Log::error('Erreur init MoneyFusion VPN: ' . $e->getMessage());
           return back()->with('error', 'Impossible d\'initier le paiement MoneyFusion.');
       }
   }

   private function createVpnAccount(Request $request, $user, bool $isSupplementary, int $totalCharge, bool $chargeWallet): array
   {
       $server = VpnServer::findOrFail($request->server_id);
       $apiService = new MikroTikApiService($server);

       DB::beginTransaction();
       try {
           $username = strtolower(substr($user->email, 0, 4)) . Str::lower(Str::random(5));
           $password = Str::random(10);
           $remoteIp = $apiService->findFreeIp();

           if (!$remoteIp) {
               throw new \RuntimeException('Aucune adresse IP disponible sur ce serveur.');
           }

           $hasCustomPort = $request->has('use_custom_port');
           $ports = $apiService->assignPorts($hasCustomPort);
           $customTarget = $hasCustomPort ? (int) $request->custom_port_number : null;
           $apiService->provisionVpnAccount($username, $password, $remoteIp, $request->protocol, $ports, $customTarget);

           $protocol = $request->protocol === 'ovpn' ? 'openvpn' : $request->protocol;
           $account = VpnAccount::create([
               'user_id' => $user->id,
               'vpn_server_id' => $server->id,
               'username' => $username,
               'password' => $password,
               'vpn_type' => $protocol,
               'protocol' => $request->protocol,
               'server_address' => $server->domain_name ?? $server->ip_address,
               'local_ip_address' => $remoteIp,
               'local_ip' => $server->gateway_ip,
               'remote_ip' => $remoteIp,
               'port_api' => $ports['api'],
               'port_winbox' => $ports['winbox'],
               'port_web' => $ports['web'],
               'port_custom' => $ports['custom'] ?? null,
               'remote_port_api' => 8728,
               'remote_port_winbox' => 8291,
               'remote_port_web' => 80,
               'remote_port_custom' => $customTarget ?? 80,
               'commentaire' => $request->commentaire,
               'duration_months' => (int) $request->duration,
               'expires_at' => Carbon::now()->addMonths((int) $request->duration),
               'status' => 'active',
               'is_active' => true,
               'is_supplementary' => $isSupplementary,
               'forward_api' => true,
               'forward_winbox' => true,
               'forward_web' => true,
           ]);

           if ($chargeWallet && $totalCharge > 0) {
               $wallet = $user->wallet;
               $wallet->decrement('balance', $totalCharge);
               Transaction::create([
                   'wallet_id' => $wallet->id,
                   'type' => 'debit',
                   'amount' => $totalCharge,
                   'description' => "Création compte VPN supplémentaire #{$account->id} (" . (int) $request->duration . " mois)",
               ]);
           }

           DB::commit();
           return ['ok' => true, 'message' => 'Tunnel VPN créé'];
       } catch (\Throwable $e) {
           DB::rollBack();
           Log::error('Erreur Création VPN: ' . $e->getMessage());
           return ['ok' => false, 'message' => 'Erreur technique : ' . $e->getMessage()];
       }
     }
}
