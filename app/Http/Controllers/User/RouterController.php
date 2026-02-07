<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\Nas;
use App\Models\RadiusServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;

class RouterController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($request->ajax()) {
            $data = Router::where('user_id', $user->id)->latest();
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    return '
                        <div class="d-inline-block">
                            <a href="javascript:;" class="btn btn-md btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="text-primary icon-base ti tabler-dots-vertical"></i></a>
                            <div class="dropdown-menu dropdown-menu-end m-0">
                                <a href="javascript:;" class="dropdown-item text-primary item-edit" data-id="'.$row->id.'"><i class="icon-base ti tabler-edit"></i> Modifier</a>
                                <a href="javascript:;" class="dropdown-item text-success item-install" data-id="'.$row->id.'" data-brand="'.$row->brand.'"><i class="icon-base ti tabler-link"></i> Installer</a>
                                <div class="dropdown-divider"></div>
                                <a href="javascript:;" class="dropdown-item text-danger item-delete" data-id="'.$row->id.'"><i class="icon-base ti tabler-trash"></i> Supprimer</a>
                            </div>
                        </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        $user = Auth::user();
        
        $hasActiveSubscription = $user->hasRole(['Super-admin', 'Admin']) || ($user->subscription && $user->subscription->isActive());
        
        $routerCount = $user->routers()->count();
        $planFeatures = $hasActiveSubscription ? $user->hasRole(['Super-admin', 'Admin']) || $user->subscription->plan->features : [];
        $limit = $planFeatures['routers'] ?? 0;
        return view('content.routers.index', compact('hasActiveSubscription', 'routerCount', 'limit'));
        
        // $hasActiveSubscription =  $user->subscription && $user->subscription->isActive();
        // return view('content.routers.index', compact('hasActiveSubscription'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isUpdating = $request->filled('id');

        if (!$isUpdating) {
            if (!$user->hasRole(['Super-admin', 'Admin'])) {
                $subscription = $user->subscription;
                if (!$subscription || !$subscription->isActive()) {
                    return response()->json(['error' => "Vous devez avoir un abonnement actif pour ajouter un routeur."], 403);
                }
                $planFeatures = $subscription->plan->features;
                $routerCount = $user->routers()->count();
                if ($routerCount >= $planFeatures['routers']) {
                    return response()->json(['error' => "Limite de routeurs atteinte pour votre plan."], 403);
                }
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => ['required', 'ipv4', Rule::unique('routers')->ignore($request->id)],
            'brand' => ['required', Rule::in(['MikroTik', 'TP-Link', 'Ubiquiti', 'Cisco', 'Autres'])],
            'description' => 'nullable|string',
        ]);

        $activeServer = RadiusServer::where('is_active', true)->first();
        if (!$activeServer) {
            return response()->json(['error' => 'Aucun serveur RADIUS actif n\'a été configuré par l\'administrateur.'], 422);
        }
        $validated['radius_secret'] = $activeServer->radius_secret;

        DB::transaction(function () use ($request, $validated, $user) {
            $router = Router::updateOrCreate(
                ['id' => $request->id, 'user_id' => $user->id],
                $validated
            );
            Nas::updateOrCreate(
                ['nasname' => $router->ip_address],
                ['shortname' => $router->name, 'secret' => $router->radius_secret, 'description' => $router->description]
            );
        });

        return response()->json(['success' => 'Routeur enregistré et synchronisé avec RADIUS avec succès.']);
    }

    public function edit(Router $router)
    {
        if ($router->user_id !== Auth::id()) { abort(403); }
        return response()->json($router);
    }

    public function destroy(Router $router)
    {
        if ($router->user_id !== Auth::id()) { abort(403); }
        DB::transaction(function () use ($router) {
            Nas::where('nasname', $router->ip_address)->delete();
            $router->delete();
        });
        return response()->json(['success' => 'Routeur supprimé et désynchronisé de RADIUS avec succès.']);
    }

    public function generateScript(Router $router)
    {
        if ($router->user_id !== Auth::id()) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        $activeServer = RadiusServer::where('is_active', true)->first();
        if (!$activeServer) {
            return response()->json(['error' => 'Aucun serveur RADIUS actif n\'a été configuré.'], 404);
        }
        $radiusServerIp = $activeServer->ip_address;
        $radiusSecret = $router->radius_secret;

        $script = "/radius add service=hotspot address=$radiusServerIp secret=\"$radiusSecret\" comment=\"Serveur PhenixSPOT\";\n";
        $script .= "/ip hotspot profile set [find default=yes] use-radius=yes\n";

        return response()->json(['script' => $script]);
    }
}
