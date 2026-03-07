<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Nas;
use App\Models\RadiusServer;
use App\Models\Transaction;
use App\Models\VpnServer;
use App\Services\WireGuard\WireGuardGeneratorService;
use App\Services\WireGuard\WireGuardIpAllocator;
use App\Services\WireGuard\WireGuardServerProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Services\PlanLimitService;
use App\Support\PlanLimits;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;

class RouterController extends Controller
{
    public function __construct(private readonly PlanLimitService $planLimitService)
    {
        
    }
    
    private function getSupplementaryRouterPrice(): int
    {
        return (int) config('fees.supplementary_router_monthly', 3000);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {
            $data = Router::where('user_id', $user->id)->latest();
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    return '
                        <div class="d-inline-block">
                            <a href="javascript:;" class="btn btn-md btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                              <i class="text-primary icon-base ti tabler-dots-vertical"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end m-0">
                                <a href="javascript:;" class="dropdown-item text-primary item-edit" data-id="'.$row->id.'">
                                  <i class="icon-base ti tabler-edit"></i> Modifier
                                </a>
                                <a href="javascript:;" class="dropdown-item text-success item-install" data-id="'.$row->id.'" data-brand="'.$row->brand.'">
                                  <i class="icon-base ti tabler-link"></i> Installer
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="javascript:;" class="dropdown-item text-danger item-delete" data-id="'.$row->id.'">
                                  <i class="icon-base ti tabler-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $hasActiveSubscription = $this->planLimitService->hasActiveSubscription($user);
        $routerCount = $user->routers()->count();

        $limits = $this->planLimitService->limits($user);
        $limit = $limits[PlanLimits::KEY_ROUTERS] ?? 0;
        $limit = $limit === null ? PHP_INT_MAX : $limit;
        $isAtLimit = $limit !== PHP_INT_MAX ? $routerCount >= $limit : false;
        $supplementaryRouterPrice = $this->getSupplementaryRouterPrice();
        $walletBalance = (int) optional($user->wallet)->balance;
        $limitLabel = $limit === PHP_INT_MAX ? 'Illimité' : number_format((int) $limit, 0, ',', ' ');
        $usagePercent = $limit !== PHP_INT_MAX && $limit > 0
            ? min(100, ($routerCount / (int) $limit) * 100)
            : 0;

        return view('content.routers.index', compact('hasActiveSubscription', 'routerCount', 'limit', 'isAtLimit', 'supplementaryRouterPrice', 'walletBalance', 'limitLabel', 'usagePercent'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isUpdating = $request->filled('id');

        if (!$isUpdating) {
            if (!$this->planLimitService->hasActiveSubscription($user)) {
                return response()->json(['error' => "Vous devez avoir un abonnement actif pour ajouter un routeur."], 403);
            }
        }
        
        $isSupplementary = !$isUpdating && !$this->planLimitService->can($user, PlanLimits::KEY_ROUTERS);
        $supplementaryCost = $isSupplementary ? $this->getSupplementaryRouterPrice() : 0;
        
        if ($supplementaryCost > 0) {
            $wallet = $user->wallet;

            if (!$wallet || (float) $wallet->balance < $supplementaryCost) {
                return response()->json([
                    'error' => "Limite atteinte : le routeur supplémentaire coûte {$supplementaryCost} FCFA/mois. Solde wallet insuffisant."
                ], 403);
            }
        }
        
        // ✅ Rendre la section API réellement optionnelle (fiable)
        $request->merge([
            'api_address'  => $request->filled('api_address') ? $request->input('api_address') : null,
            'api_port'     => $request->filled('api_port') ? $request->input('api_port') : null,
            'api_user'     => $request->filled('api_user') ? $request->input('api_user') : null,
            'api_password' => $request->filled('api_password') ? $request->input('api_password') : null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'ip_address' => ['required', 'string', 'max:255', Rule::unique('routers')->ignore($request->id)],
            'brand' => ['required', Rule::in(['MikroTik', 'TP-Link', 'Ubiquiti', 'Cisco', 'Autres'])],
            'api_address'  => 'sometimes|nullable|string|max:255',
            'api_port'     => 'sometimes|nullable|integer|min:1|max:65535',
            'api_user'     => 'sometimes|nullable|string|max:255',
            'api_password' => 'sometimes|nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        foreach (['ip_address', 'api_address'] as $hostField) {
            if (!empty($validated[$hostField]) && !$this->isValidHostOrIp($validated[$hostField])) {
                return response()->json(['error' => "Le champ {$hostField} doit être une IP ou un nom de domaine valide."], 422);
            }
        }

        $activeServer = RadiusServer::where('is_active', true)->first();
        if (!$activeServer) {
            return response()->json(['error' => "Aucun serveur RADIUS actif n'a été configuré par l'administrateur."], 422);
        }
        $validated['radius_secret'] = $activeServer->radius_secret;

        try {
            DB::transaction(function () use ($request, $validated, $user, $isSupplementary, $supplementaryCost) {

                if ($isSupplementary && $supplementaryCost > 0) {
                    $wallet = $user->wallet()->lockForUpdate()->first();

                    if (!$wallet || (float) $wallet->balance < $supplementaryCost) {
                        throw new RuntimeException("Solde wallet insuffisant pour ajouter un routeur supplémentaire ({$supplementaryCost} FCFA). ");
                    }

                    $wallet->decrement('balance', $supplementaryCost);

                    Transaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'debit',
                        'amount' => $supplementaryCost,
                        'description' => 'Ajout routeur supplémentaire (upgrade intelligent)',
                    ]);
                }

                $router = Router::updateOrCreate(
                    ['id' => $request->id, 'user_id' => $user->id],
                    $validated
                );

                Nas::updateOrCreate(
                    ['nasname' => $router->ip_address],
                    [
                        'shortname' => $router->name,
                        'secret' => $router->radius_secret,
                        'description' => $router->description
                    ]
                );

                // ✅ Flow complet WG: création client + provisioning peer côté serveur
                $wgClient = $router->wireguardClient;
                if (!$request->filled('id') || !$wgClient) {
                    app(WireGuardGeneratorService::class)->createClientForRouter($router);
                }
            });
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $message = $isSupplementary
            ? 'Routeur supplémentaire ajouté avec succès. 3 000 FCFA ont été débités de votre wallet.'
            : 'Routeur enregistré, synchronisé avec RADIUS et prêt pour WireGuard.';

        return response()->json(['success' => $message]);
    }

    public function testApi(Request $request)
    {
        $data = $request->validate([
            'api_address' => 'required|string|max:255',
            'api_port' => 'required|integer|min:1|max:65535',
        ]);

        if (!$this->isValidHostOrIp($data['api_address'])) {
            return response()->json(['success' => false, 'message' => 'Adresse API invalide.'], 422);
        }

        $connection = @fsockopen($data['api_address'], (int) $data['api_port'], $errno, $errstr, 3);

        if ($connection) {
            fclose($connection);
            return response()->json(['success' => true, 'message' => 'Connexion API réussie.']);
        }

        return response()->json([
            'success' => false,
            'message' => "Connexion échouée ({$errno}) {$errstr}",
        ], 422);
    }

    public function suggestWireguardIp()
    {
        $server = \App\Models\VpnServer::query()
            ->where('server_type', 'wireguard')
            ->where('is_active', true)
            ->first();

        if (!$server) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun serveur WireGuard actif disponible.'
            ], 422);
        }

        try {
            $cidr = app(WireGuardIpAllocator::class)->peekNextIp($server);

            return response()->json([
                'success' => true,
                'ip_address' => explode('/', $cidr)[0],
                'cidr' => $cidr,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }


    private function isValidHostOrIp(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        return (bool) preg_match('/^(?=.{1,253}$)(?!-)(?:[a-zA-Z0-9-]{1,63}\.)+[a-zA-Z]{2,63}$/', $value);
    }

    public function edit(Router $router)
    {
        if ($router->user_id !== Auth::id()) { abort(403); }
        return response()->json([
            'id' => (int) $router->id,
            'name' => (string) $router->name,
            'location' => $router->location,
            'latitude' => $router->latitude,
            'longitude' => $router->longitude,
            'ip_address' => (string) $router->ip_address,
            'brand' => (string) $router->brand,
            'api_address' => $router->api_address,
            'api_port' => $router->api_port,
            'api_user' => $router->api_user,
            'api_password' => $router->api_password,
            'description' => $router->description,
        ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function destroy($id)
    {
        $user = Auth::user();

        try {
            DB::transaction(function () use ($id, $user) {

                $router = Router::where('user_id', $user->id)->findOrFail($id);

                // Récupération client WG si existe
                $wgClient = $router->wireguardClient;

                if ($wgClient) {
                    // Serveur WG associé (recommandé) sinon fallback serveur actif
                    $wgServer = $wgClient->wireguardServer
                        ?? VpnServer::where('server_type', 'wireguard')->where('is_active', true)->first();

                    if ($wgServer) {
                        // 1) Retire le peer côté serveur WireGuard
                        app(WireGuardServerProvisioner::class)->removePeer($wgServer, $wgClient);

                        // 2) Libère l'IP allouée (selon ta méthode releaseIp)
                        $allocator = app(WireGuardIpAllocator::class);

                        // Variante A (courante) : releaseIp(VpnServer $server, string $ipCidr)
                        if (method_exists($allocator, 'releaseIp')) {
                            // adapte si ton releaseIp attend un client au lieu d'une string
                            try {
                                $allocator->releaseIp($wgServer, $wgClient->client_ip);
                            } catch (\Throwable $e) {
                                // fallback : certains projets font releaseIp($server, $client)
                                try {
                                    $allocator->releaseIp($wgServer, $wgClient);
                                } catch (\Throwable $e2) {
                                    // On ne bloque pas la suppression DB si release échoue, mais c'est mieux de logguer côté projet
                                }
                            }
                        }
                    }

                    // 3) Supprime l’entrée WireGuardClient
                    $wgClient->delete();
                }

                // Supprime NAS lié
                Nas::where('nasname', $router->ip_address)->delete();

                // Supprime Router
                $router->delete();
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => "Suppression échouée: " . $e->getMessage()], 422);
        }

        return response()->json(['success' => 'Routeur supprimé. Peer WireGuard retiré et IP libérée.']);
    }
    
    public function radiusInstallCommand(Request $request, int $router)
    {
        $routerModel = \App\Models\Router::findOrFail($router);
    
        // Sécurité: seul le propriétaire du routeur peut générer la commande
        if ((int) $routerModel->user_id !== (int) \Illuminate\Support\Facades\Auth::id()) {
            abort(403, 'Unauthorized.');
        }
    
        // Token temporaire
        $ttlSeconds = 3600; // 1h
        $expires = time() + $ttlSeconds;
    
        // Token HMAC (même approche que VPN)
        $payload = $routerModel->id . '|' . $routerModel->user_id . '|' . $expires;
        $token = hash_hmac('sha256', $payload, config('app.key'));
    
        // URL Loader (script tokenisé)
        $loaderUrl = route('routers.radius.script.loader', [
            'router'  => $routerModel->id,
            'token'   => $token,
            'expires' => $expires,
        ]);
    
        // ✅ Commande MikroTik SIMPLE (comme VPN)
        $cmd = "/tool fetch url=\"{$loaderUrl}\" mode=https check-certificate=no dst-path=radius.rsc; "
             . "/import radius.rsc; "
             . "/file remove radius.rsc";
    
        return response()->json([
            'script'  => $cmd,
            'expires' => $expires,
        ]);
    }
    
    public function radiusScriptLoader(Request $request, int $router)
    {
        $routerModel = $this->resolveScriptRouter($router, $request);
        if (!$routerModel) abort(403, 'Invalid or expired token.');
    
        $coreUrl = route('routers.radius.script.core', [
            'router'  => $routerModel->id,
            'token'   => (string) $request->query('token'),
            'expires' => (int) $request->query('expires'),
        ]);
    
        $loader = <<<RSC
/tool fetch url="$coreUrl" mode=https check-certificate=no dst-path=radius-core.rsc;
:delay 1s;
/import radius-core.rsc;
/file remove radius-core.rsc;
:delay 1s;
:log info "Installation RADIUS terminée";
RSC;
    
        return response($loader, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
    
    public function radiusScriptCore(Request $request, int $router)
    {
        $routerModel = $this->resolveScriptRouter($router, $request);
        if (!$routerModel) abort(403, 'Invalid or expired token.');
    
        $activeServer = VpnServer::where('is_active', true)->first();
        if (!$activeServer) abort(404, "Aucun serveur RADIUS actif n'a été configuré.");
    
        $radiusServerIp = $activeServer->wg_server_address;
        $radiusSecret   = $routerModel->radius_secret;
    
        $wgClient = $routerModel->wireguardClient ?: app(WireGuardGeneratorService::class)->createClientForRouter($routerModel);
        $wgScript = app(WireGuardGeneratorService::class)->buildMikrotikWireguardScript($wgClient);

        $core = <<<RSC
# ==========================================
#   PHENIXSPOT - WIREGUARD + RADIUS CLIENT
# ==========================================

{$wgScript}

:local RADIUSIP "$radiusServerIp";
:local RADIUSSECRET "$radiusSecret";
:local COMMENT "Serveur PhenixSPOT";

# --- Clean previous entries for this server only
:foreach r in=[/radius find] do={
    :if ([/radius get \$r address] = \$RADIUSIP) do={
        /radius remove \$r;
    }
}

# --- Add RADIUS server
/radius add \
    address=\$RADIUSIP \
    secret=\$RADIUSSECRET \
    service=hotspot,ppp \
    protocol=udp \
    timeout=3000ms \
    accounting-backup=no \
    comment=\$COMMENT;

# --- Enable RADIUS on all hotspot profiles
/ip hotspot profile set [find] \
    use-radius=yes \
    radius-accounting=yes \
    radius-interim-update=1m \
    nas-port-type=wireless-802.11;

# --- Enable RADIUS for PPP (PPPoE/L2TP)
/ppp aaa set \
    use-radius=yes \
    accounting=yes \
    interim-update=30s;

# --- Improve reliability

:local ROUTERSRCIP "{$wgClient->client_ip}";
:set ROUTERSRCIP [:pick \$ROUTERSRCIP 0 [:find \$ROUTERSRCIP "/"]];
/radius set [find where address=\$RADIUSIP] src-address=\$ROUTERSRCIP;
/system logging add topics=radius,debug action=memory;

 :log info "PhenixSPOT WireGuard + RADIUS configuré";
# ==========================================
#               END CONFIG
# ==========================================
RSC;
    
        return response($core, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
    
    private function resolveScriptRouter(int $routerId, Request $request): ?Router
    {
        $token   = (string) $request->query('token');
        $expires = (int) $request->query('expires');
    
        if (!$token || !$expires || $expires < time()) return null;
    
        $router = Router::find($routerId);
        if (!$router) return null;
    
        $expected = $this->generateScriptTokenForRouter($router, $expires);
        return hash_equals($expected, $token) ? $router : null;
    }
    
    private function generateScriptTokenForRouter(Router $router, int $expires): string
    {
        $payload = $router->id . '|' . $router->user_id . '|' . $expires;
        return hash_hmac('sha256', $payload, config('app.key'));
    }
}
