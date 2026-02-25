<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpnServer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class VpnServerController extends Controller
{
    /**
     * Récupère les données d'un serveur pour la modale d'édition.
     */
    public function json(VpnServer $vpnServer)
    {
        return response()->json($vpnServer);
    }

    public function index()
    {
        $servers = VpnServer::latest()->get();
        return view('content.admin.vpn_servers.index', compact('servers'));
    }

    /**
     * Stockage mutualisé pour RouterOS et WireGuard
     */
    public function store(Request $request)
    {
        try {
            $data = $this->validateServer($request);
            
            // Forçage des valeurs par défaut pour la stabilité
            $data['is_active'] = $request->boolean('is_active', true);
            $data['is_online'] = $request->boolean('is_online', false);
            
            VpnServer::create($data);

            return response()->json(['success' => true, 'message' => 'Serveur ajouté avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, VpnServer $vpn_server)
    {
        try {
            $data = $this->validateServer($request, false);
            
            $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $vpn_server->is_active;
            $data['is_online'] = $request->has('is_online') ? $request->boolean('is_online') : $vpn_server->is_online;

            $vpn_server->update($data);

            return response()->json(['success' => true, 'message' => 'Serveur mis à jour']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(VpnServer $vpn_server)
    {
        try {
            $vpn_server->delete();
            return response()->json(['success' => true, 'message' => 'Serveur supprimé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression'], 500);
        }
    }

    /**
     * Validation unifiée
     */
    protected function validateServer(Request $request, $isStore = true)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'server_type' => 'required|in:routeros,wireguard',
            'supported_protocols' => 'nullable',
        ];

        if ($request->server_type === 'wireguard') {
            $rules = array_merge($rules, [
                'wg_server_address' => 'required',
                'wg_server_public_key' => 'required',
                'wg_endpoint_address' => 'required',
                'wg_endpoint_port' => 'required|numeric',
            ]);
        } else {
            $rules = array_merge($rules, [
                'ip_address' => 'required',
                'api_user' => 'required',
                'api_port' => 'required|numeric',
            ]);
        }

        $data = $request->validate($rules);

        // Traitement des protocoles (JSON)
        if (isset($data['supported_protocols'])) {
            if (is_string($data['supported_protocols'])) {
                $decoded = json_decode($data['supported_protocols'], true);
                $data['supported_protocols'] = is_array($decoded) ? $decoded : [$data['supported_protocols']];
            }
        }

        return $data;
    }
}