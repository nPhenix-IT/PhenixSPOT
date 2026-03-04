<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class AccessPermissionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->expectsJson() || $request->has('draw')) {
            $query = Permission::query()->withCount('roles')->orderByDesc('id');

            return DataTables::eloquent($query)
                ->addColumn('actions', function (Permission $permission) {
                    return view('content.admin.access.permissions.partials.actions', compact('permission'))->render();
                })
                ->editColumn('created_at', fn (Permission $permission) => optional($permission->created_at)?->format('d/m/Y H:i'))
                ->rawColumns(['actions'])
                ->toJson();
        }

        return view('content.admin.access.permissions.index');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'is_core' => ['nullable', 'boolean'],
        ]);

        Permission::create([
            'name' => trim($data['name']),
            'guard_name' => 'web',
            'is_core' => (bool) ($data['is_core'] ?? false),
        ]);

        return $request->ajax()
            ? response()->json(['message' => 'Permission ajoutée avec succès.'])
            : back()->with('success', 'Permission ajoutée avec succès.');
    }

    public function update(Request $request, Permission $permission): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,' . $permission->id],
            'is_core' => ['nullable', 'boolean'],
        ]);

        $permission->update([
            'name' => trim($data['name']),
            'is_core' => (bool) ($data['is_core'] ?? false),
        ]);

        return $request->ajax()
            ? response()->json(['message' => 'Permission modifiée avec succès.'])
            : back()->with('success', 'Permission modifiée avec succès.');
    }

    public function destroy(Request $request, Permission $permission): RedirectResponse|JsonResponse
    {
        if ($permission->is_core) {
            return $request->ajax()
                ? response()->json(['message' => 'Une permission core ne peut pas être supprimée.'], 422)
                : back()->with('error', 'Une permission core ne peut pas être supprimée.');
        }

        $permission->delete();

        return $request->ajax()
            ? response()->json(['message' => 'Permission supprimée.'])
            : back()->with('success', 'Permission supprimée.');
    }
}
