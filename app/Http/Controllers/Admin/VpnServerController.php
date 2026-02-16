<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpnServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Config;

class VpnServerController extends Controller
{
    // public function index(Request $request)
    public function index()
    {
        $servers = VpnServer::withCount('accounts')->latest()->get();
        return view('content.admin.vpn_servers.index', compact('servers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateServer($request, true);
        $data['supported_protocols'] = ["l2tp", "ovpn", "sstp", "wireguard"];
        $data['is_online'] = false;
        $data['is_active'] = true;
        $data['local_ip_address'] = $data['gateway_ip'];
        $data['ip_range'] = $data['ip_pool'];
        $data['account_limit'] = $data['max_accounts'];

        VpnServer::create($data);

        return redirect()->route('admin.vpn-servers.index')->with('success', 'Serveur configuré avec succès !');
    }

    public function edit(VpnServer $vpnServer)
    {
        return view('content.admin.vpn_servers.edit', compact('vpnServer'));
    }
    

    public function update(Request $request, VpnServer $vpnServer)
    {
        $data = $this->validateServer($request, false);

        if (!$request->filled('api_password')) {
            unset($data['api_password']);
        }
        
        $data['local_ip_address'] = $data['gateway_ip'];
        $data['ip_range'] = $data['ip_pool'];
        $data['account_limit'] = $data['max_accounts'];

        $vpnServer->update($data);
        return redirect()->route('admin.vpn-servers.index')->with('success', 'Serveur mis à jour avec succès.');
    }

    public function testConnection(Request $request)
    {
        $request->validate([
            'server_id' => 'required|exists:vpn_servers,id',
        ]);

        $server = VpnServer::findOrFail($request->server_id);

        try {
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

            return back()->with('success', 'Connexion RÉUSSIE ! Routeur : ' . ($identity[0]['name'] ?? 'Inconnu'));
        } catch (\Exception $e) {
            $server->update(['is_online' => false]);
            Log::error("Erreur MikroTik {$server->ip_address}: " . $e->getMessage());
            return back()->with('error', 'ÉCHEC de connexion : ' . $e->getMessage());
        }
    }

    public function destroy(VpnServer $vpnServer)
    {
        $vpnServer->delete();
        return back()->with('success', 'Serveur supprimé.');
    }

    private function validateServer(Request $request, bool $isStore): array
    {
        $passwordRule = $isStore ? 'required|string' : 'nullable|string';

        return $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'domain_name' => 'nullable|string|max:255',
            'profile_name' => 'required|string|max:255',
            'api_user' => 'required|string|max:255',
            'api_password' => $passwordRule,
            'api_port' => 'required|integer',
            'gateway_ip' => 'required|ip',
            'ip_pool' => 'required|string|max:255',
            'max_accounts' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
        ]);
    }
}