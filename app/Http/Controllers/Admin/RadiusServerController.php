<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RadiusServer;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;

class RadiusServerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = RadiusServer::latest();
            return DataTables::of($data)
                ->addColumn('status', fn($row) => $row->is_active ? '<span class="badge bg-label-success">Actif</span>' : '<span class="badge bg-label-secondary">Inactif</span>')
                ->addColumn('action', function($row){
                    return '
                        <div class="d-inline-block">
                            <a href="javascript:;" class="btn btn-md text-primary btn-icon item-edit" data-id="'.$row->id.'"><i class="icon-base ti tabler-edit"></i></a>
                            <a href="javascript:;" class="btn btn-md text-danger btn-icon item-delete" data-id="'.$row->id.'"><i class="icon-base ti tabler-trash"></i></a>
                        </div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('content.admin.radius_servers.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:radius_servers,name',
            'ip_address' => 'required|ipv4',
            'radius_secret' => 'required|string|min:6',
            'description' => 'nullable|string',
        ]);
        $validated['is_active'] = $request->has('is_active');

        RadiusServer::create($validated);
        return response()->json(['success' => 'Serveur RADIUS ajouté avec succès.']);
    }

    public function edit(RadiusServer $radiusServer)
    {
        return response()->json($radiusServer);
    }

    public function update(Request $request, RadiusServer $radiusServer)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('radius_servers')->ignore($radiusServer->id)],
            'ip_address' => 'required|ipv4',
            'radius_secret' => 'nullable|string|min:6',
            'description' => 'nullable|string',
        ]);
        $validated['is_active'] = $request->has('is_active');

        if (!$request->filled('radius_secret')) {
            unset($validated['radius_secret']);
        }

        $radiusServer->update($validated);
        return response()->json(['success' => 'Serveur RADIUS mis à jour avec succès.']);
    }

    public function destroy(RadiusServer $radiusServer)
    {
        $radiusServer->delete();
        return response()->json(['success' => 'Serveur RADIUS supprimé avec succès.']);
    }
}
