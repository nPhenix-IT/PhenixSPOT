<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\VpnAccount;
use App\Models\VpnServer;
use App\Services\MikroTikApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class VpnAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($request->ajax()) {
            $data = VpnAccount::where('user_id', $user->id)->with('vpnServer')->latest();
            return DataTables::of($data)
                ->addColumn('server_name', fn($row) => $row->vpnServer->name ?? 'N/A')
                ->addColumn('status', function($row) {
                    $checked = $row->is_active ? 'checked' : '';
                    return '<div class="form-check form-switch"><input class="form-check-input voucher-status-switch" type="checkbox" data-id="'.$row->id.'" '.$checked.'></div>';
                })
                ->addColumn('action', function($row){
                    return '<button class="btn btn-sm btn-icon text-danger item-delete" data-id="'.$row->id.'"><i class="icon-base ti tabler-trash"></i></button>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        
        $servers = VpnServer::where('is_active', true)->get();
        $hasActiveSubscription = $user->hasRole(['Super-admin', 'Admin']) || ($user->subscription && $user->subscription->isActive());
        
        $planFeatures = $hasActiveSubscription ? $user->hasRole(['Super-admin', 'Admin']) || $user->subscription->plan->features : [];
        $vpnAccountCount = $user->vpnAccounts()->count();
        $limit = $planFeatures['vpn_accounts'] ?? 0;

        return view('content.vpn.index', compact('servers', 'hasActiveSubscription', 'vpnAccountCount', 'limit'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $planFeatures = $user->subscription->plan->features;
        $vpnAccountCount = $user->vpnAccounts()->count();
        $limit = $planFeatures['vpn_accounts'] ?? 0;

        if ($request->isNotFilled('id') && $vpnAccountCount >= $limit) {
            return response()->json(['error' => "Limite de comptes VPN atteinte pour votre forfait."], 403);
        }

        $data = $request->validate([
            'vpn_server_id' => 'required|exists:vpn_servers,id',
            'username' => 'required|string|min:4|max:50|unique:vpn_accounts,username,'.$request->id,
            'password' => 'required|string|min:6|max:50',
            'vpn_type' => 'required|string|in:l2tp,openvpn,sstp',
            'forward_api' => 'nullable|boolean',
            'forward_winbox' => 'nullable|boolean',
            'forward_web' => 'nullable|boolean',
        ]);

        $server = VpnServer::find($data['vpn_server_id']);

        try {
            $apiService = new MikroTikApiService($server);
            $localIp = $apiService->findNextAvailableIp();
            if (!$localIp) {
                return response()->json(['error' => 'Plus aucune adresse IP n\'est disponible sur ce serveur.'], 422);
            }

            DB::transaction(function () use ($data, $request, $server, $apiService, $localIp, $user) {
                $vpnAccount = $user->vpnAccounts()->create([
                    'vpn_server_id' => $server->id,
                    'username' => $data['username'],
                    'password' => $data['password'],
                    'vpn_type' => $data['vpn_type'],
                    'server_address' => $server->domain_name ?? $server->ip_address,
                    'local_ip_address' => $localIp,
                    'status' => 'active',
                    'forward_api' => $request->has('forward_api'),
                    'forward_winbox' => $request->has('forward_winbox'),
                    'forward_web' => $request->has('forward_web'),
                ]);
                $apiService->createVpnUser($vpnAccount);
                $apiService->createPortForwardingRules($vpnAccount);
            });

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur API MikroTik : ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Compte VPN créé avec succès.']);
    }

    public function destroy(VpnAccount $vpnAccount)
    {
        if ($vpnAccount->user_id !== Auth::id()) { abort(403); }
        try {
            DB::transaction(function () use ($vpnAccount) {
                if ($vpnAccount->vpnServer) {
                    $apiService = new MikroTikApiService($vpnAccount->vpnServer);
                    $apiService->deletePortForwardingRules($vpnAccount);
                    $apiService->deleteVpnUser($vpnAccount);
                }
                $vpnAccount->delete();
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur API MikroTik : ' . $e->getMessage()], 500);
        }
        return response()->json(['success' => 'Compte VPN supprimé.']);
    }
}