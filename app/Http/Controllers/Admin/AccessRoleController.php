<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class AccessRoleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->expectsJson() || $request->has('draw')) {
            $query = Role::query()->withCount('users')->with('permissions')->orderByDesc('id');

            return DataTables::eloquent($query)
                ->addColumn('permissions_list', fn (Role $role) => e($role->permissions->pluck('name')->implode(', ')))
                ->addColumn('actions', fn (Role $role) => view('content.admin.access.roles.partials.actions', compact('role'))->render())
                ->filterColumn('permissions_list', function ($query, $keyword) {
                    $query->whereHas('permissions', function ($permissionQuery) use ($keyword) {
                        $permissionQuery->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('permissions_list', false)
                ->rawColumns(['actions'])
                ->toJson();
        }

        $permissions = Permission::query()->orderBy('name')->get();

        return view('content.admin.access.roles.index', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => trim($data['name']),
            'guard_name' => 'web',
        ]);

        $permissionNames = Permission::whereIn('id', $data['permissions'] ?? [])->pluck('name')->all();
        $role->syncPermissions($permissionNames);

        return $request->ajax()
            ? response()->json(['message' => 'Rôle créé avec succès.'])
            : back()->with('success', 'Rôle créé avec succès.');
    }

    public function update(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->update(['name' => trim($data['name'])]);

        $permissionNames = Permission::whereIn('id', $data['permissions'] ?? [])->pluck('name')->all();
        $role->syncPermissions($permissionNames);

        return $request->ajax()
            ? response()->json(['message' => 'Rôle mis à jour.'])
            : back()->with('success', 'Rôle mis à jour.');
    }
}
