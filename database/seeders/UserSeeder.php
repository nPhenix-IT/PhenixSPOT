<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@phenixspot.com',
            'password' => Hash::make('password123'),
            'slug' => 'superdmin',
        ]);
        $this->createTeam($superAdmin);
        $superAdmin->assignRole('Super-admin');

        // Admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@phenixspot.com',
            'password' => Hash::make('password123'),
            'slug' => 'admin',
        ]);
        $this->createTeam($admin);
        $admin->assignRole('Admin');

        // User
        $user = User::create([
            'name' => 'Client Test',
            'email' => 'user@phenixspot.com',
            'password' => Hash::make('password123'),
            'slug' => 'user',
        ]);
        $this->createTeam($user);
        $user->assignRole('User');
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}