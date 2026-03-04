<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'Super-admin',
            'Admin',
            'User',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        $permissions = [
            'access.permissions.view',
            'access.permissions.manage',
            'access.roles.view',
            'access.roles.manage',
            'users.view',
            'users.create',
            'users.update',
            'users.toggle',
            'users.assign-plan',
            'users.impersonate',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        Role::findByName('Super-admin', 'web')->syncPermissions($permissions);

        Role::findByName('Admin', 'web')->syncPermissions([
            'users.view',
            'users.update',
            'users.assign-plan',
        ]);
    }
}