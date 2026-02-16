<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            // PlanSeeder::class,
            UserSeeder::class,
            ExistingUserWalletSeeder::class,
        ]);
        // Message de confirmation
        $this->command->info('✅ Seed exécuté avec succès ! Base de données initialisée.');
    }

}
