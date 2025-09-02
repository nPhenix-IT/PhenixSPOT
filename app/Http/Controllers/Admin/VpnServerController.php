<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpnServer;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class VpnServerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = VpnServer::latest();
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    return '
                        <div class="d-inline-block">
                            <a href="javascript:;" class="btn btn-sm btn-icon item-edit" data-id="'.$row->id.'"><i class="icon-base ti tabler-edit"></i></a>
                            <a href="javascript:;" class="btn btn-sm btn-icon item-delete" data-id="'.$row->id.'"><i class="icon-base ti tabler-trash"></i></a>
                        </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('content.admin.vpn_servers.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ipv4',
            'api_user' => 'required|string|max:255',
            'api_password' => 'required|string|min:6',
            'api_port' => 'required|integer',
            'domain_name' => 'nullable|string',
            'local_ip_address' => 'nullable|ipv4',
            'ip_range' => 'nullable|string',
            'account_limit' => 'required|integer|min:1',
        ]);
        $validated['is_active'] = $request->has('is_active');

        VpnServer::create($validated);
        return response()->json(['success' => 'Serveur VPN ajouté avec succès.']);
    }
    
    public function update(Request $request, VpnServer $vpnServer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ipv4',
            'api_user' => 'required|string|max:255',
            'api_password' => 'nullable|string|min:6',
            'api_port' => 'required|integer',
            'domain_name' => 'nullable|string',
            'local_ip_address' => 'nullable|ipv4',
            'ip_range' => 'nullable|string',
            'account_limit' => 'required|integer|min:1',
        ]);
        $validated['is_active'] = $request->has('is_active');

        if (!$request->filled('api_password')) {
            unset($validated['api_password']);
        }

        $vpnServer->update($validated);
        return response()->json(['success' => 'Serveur VPN mis à jour avec succès.']);
    }

    public function edit(VpnServer $vpnServer)
    {
        return response()->json($vpnServer);
    }

    public function destroy(VpnServer $vpnServer)
    {
        $vpnServer->delete();
        return response()->json(['success' => 'Serveur VPN supprimé avec succès.']);
    }

    public function testConnection(Request $request)
    {
        $data = $request->validate([
            'ip_address' => 'required|ipv4',
            'api_user' => 'required|string',
            'api_password' => 'required|string',
            'api_port' => 'required|integer',
        ]);

        try {
            $config = new Config([
                'host' => $data['ip_address'],
                'user' => $data['api_user'],
                'pass' => $data['api_password'],
                'port' => (int) $data['api_port'],
                'timeout' => 5,
            ]);
            $client = new Client($config);
            return response()->json(['success' => 'Connexion réussie !']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Échec de la connexion.'], 422);
        }
    }
}