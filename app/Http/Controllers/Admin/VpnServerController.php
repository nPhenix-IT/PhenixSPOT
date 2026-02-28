<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpnServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RouterOS\Client;
use RouterOS\Config;

class VpnServerController extends Controller
{
    public function index()
    {
        $servers = VpnServer::withCount('accounts')->latest()->get();
        return view('content.admin.vpn_servers.index', compact('servers'));
    }

    /**
     * Récupère les données d'un serveur pour la modale d'édition.
     */
    public function json(VpnServer $vpnServer)
    {
        return response()->json($vpnServer);
    }

    /**
     * Stockage mutualisé pour RouterOS (L2TP) et WireGuard.
     */
    public function store(Request $request)
    {
        try {
            // Forçage des valeurs par défaut pour la stabilité
            $data = $this->validateServer($request, true);

            // Champs de gestion communs
            $data['is_active'] = $request->boolean('is_active', true);

            $data['is_online'] = false;

            // Compatibilité colonnes historiques
            $data['local_ip_address'] = $data['gateway_ip'] ?? ($data['wg_server_address'] ?? null);
            $data['ip_range'] = $data['ip_pool'] ?? ($data['wg_network'] ?? null);
            $data['account_limit'] = $data['max_accounts'] ?? null;

            VpnServer::create($data);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Serveur ajouté avec succès']);
            }

            return redirect()->route('admin.vpn-servers.index')->with('success', 'Serveur configuré avec succès !');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['server' => $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, VpnServer $vpn_server)
    {
        try {
            $data = $this->validateServer($request, false);
            

            $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $vpn_server->is_active;
            $data['is_online'] = $request->has('is_online') ? $request->boolean('is_online') : $vpn_server->is_online;

            // Garde le mot de passe/API private key existants si champ vide en édition
            if (($data['server_type'] ?? $vpn_server->server_type) === 'routeros' && empty($data['api_password'])) {
                unset($data['api_password']);
            }
            if (($data['server_type'] ?? $vpn_server->server_type) === 'wireguard' && empty($data['wg_server_private_key'])) {
                unset($data['wg_server_private_key']);
            }

            // Compatibilité colonnes historiques
            $data['local_ip_address'] = $data['gateway_ip'] ?? ($data['wg_server_address'] ?? $vpn_server->local_ip_address);
            $data['ip_range'] = $data['ip_pool'] ?? ($data['wg_network'] ?? $vpn_server->ip_range);
            $data['account_limit'] = $data['max_accounts'] ?? $vpn_server->account_limit;

            $vpn_server->update($data);

            return response()->json(['success' => true, 'message' => 'Serveur mis à jour']);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Serveur mis à jour']);
            }

            return redirect()->route('admin.vpn-servers.index')->with('success', 'Serveur mis à jour avec succès.');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['server' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Teste la connectivité selon le type de serveur.
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'server_id' => 'required|exists:vpn_servers,id',
        ]);

        $server = VpnServer::findOrFail($request->server_id);

        try {
            if ($server->server_type === 'wireguard') {
                $host = $server->wg_endpoint_address;
                $port = (int) ($server->wg_endpoint_port ?? 51820);

                $socket = @fsockopen($host, $port, $errno, $errstr, 3);
                if (!$socket) {
                    throw new \RuntimeException("WireGuard indisponible ({$errno}) {$errstr}");
                }
                fclose($socket);

                $server->update(['is_online' => true]);

                return $this->connectionResponse($request, true, "Connexion WireGuard OK ({$host}:{$port})");
            }

            // RouterOS (MikroTik CHR)
            $config = new Config([
                'host' => $server->ip_address,
                'user' => $server->api_user,
                'pass' => $server->api_password,
                'port' => (int) $server->api_port,
                'timeout' => 5,
            ]);

            $client = new Client($config);
            $identity = $client->query('/system/identity/print')->read();

            $server->update(['is_online' => true]);
            $routerName = $identity[0]['name'] ?? 'Inconnu';

            return $this->connectionResponse($request, true, 'Connexion RÉUSSIE ! Routeur : ' . $routerName);
        } catch (\Exception $e) {
            $server->update(['is_online' => false]);
            Log::error("Erreur test serveur VPN #{$server->id}: " . $e->getMessage());

            return $this->connectionResponse($request, false, 'ÉCHEC de connexion : ' . $e->getMessage(), 422);
        }
    }

    public function destroy(VpnServer $vpn_server)
    {
        try {
            $vpn_server->delete();
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Serveur supprimé']);
            }

            return back()->with('success', 'Serveur supprimé.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression'], 500);
            }

            return back()->with('error', 'Erreur lors de la suppression');
        }
    }

    private function connectionResponse(Request $request, bool $ok, string $message, int $status = 200)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $ok,
                'message' => $message,
            ], $status);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    /**
     * Validation unifiée selon type de serveur.
     */
    private function validateServer(Request $request, bool $isStore): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'server_type' => 'required|in:routeros,wireguard',
            'supported_protocols' => 'nullable',
            'max_accounts' => 'nullable|integer|min:1|max:100000',
            'location' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_online' => 'nullable|boolean',
        ];

        if ($request->input('server_type') === 'wireguard') {
            $rules = array_merge($rules, [
                'wg_endpoint_address' => 'required|string|max:255',
                'wg_endpoint_port' => 'required|integer|min:1|max:65535',
                'wg_interface' => 'required|string|max:64',
                'wg_server_public_key' => 'required|string|max:255',
                'wg_server_private_key' => ($isStore ? 'required' : 'nullable') . '|string|max:255',
                'wg_network' => 'required|string|max:32',
                'wg_server_address' => 'required|string|max:45',
                'wg_dns' => 'required|string|max:255',
                'wg_mtu' => 'nullable|integer|min:576|max:9000',
                'wg_persistent_keepalive' => 'nullable|integer|min:0|max:65535',
                'supported_protocols' => ['nullable', Rule::in(['wireguard'])],
            ]);
        } else {
            $rules = array_merge($rules, [
                'ip_address' => 'required|string|max:255',
                'api_user' => 'required|string|max:255',
                'api_password' => ($isStore ? 'required' : 'nullable') . '|string|max:255',
                'api_port' => 'required|integer|min:1|max:65535',
                'profile_name' => 'required|string|max:255',
                'gateway_ip' => 'required|string|max:255',
                'domain_name' => 'nullable|string|max:255',
                'ip_pool' => 'required|string|max:255',
                'supported_protocols' => ['nullable', Rule::in(['l2tp'])],
            ]);
        }

        $data = $request->validate($rules);

        // Normalisation protocoles et nettoyage de champs hors type
        if (($data['server_type'] ?? null) === 'routeros') {
            $data['supported_protocols'] = ['l2tp'];

            $data['wg_endpoint_address'] = null;
            $data['wg_endpoint_port'] = null;
            $data['wg_interface'] = null;
            $data['wg_server_public_key'] = null;
            if ($isStore) {
                $data['wg_server_private_key'] = null;
            }
            $data['wg_network'] = null;
            $data['wg_server_address'] = null;
            $data['wg_dns'] = null;
            $data['wg_mtu'] = null;
            $data['wg_persistent_keepalive'] = null;
            $data['wg_client_ip_start'] = null;
        }

        if (($data['server_type'] ?? null) === 'wireguard') {
            $data['supported_protocols'] = ['wireguard'];
        
            // Compatibilité avec le schéma historique (colonnes non-nullables)
            $data['ip_address'] = $data['wg_endpoint_address'];
            $data['api_user'] = $data['api_user'] ?? 'wireguard';
            $data['api_port'] = (int) $data['wg_endpoint_port'];
            $data['profile_name'] = $data['profile_name'] ?? 'wireguard';
            $data['gateway_ip'] = $data['wg_server_address'];
            $data['domain_name'] = $data['wg_endpoint_address'];
            $data['ip_pool'] = $data['wg_network'];
        
            if ($isStore) {
                $data['api_password'] = 'wireguard';
            }
        }

        return $data;
    }
}
