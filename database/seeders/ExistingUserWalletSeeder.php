<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wallet;

class ExistingUserWalletSeeder extends Seeder
{
    public function run(): void
    {
        // Récupère tous les utilisateurs qui n'ont pas de portefeuille
        $usersWithoutWallet = User::whereDoesntHave('wallet')->get();

        foreach ($usersWithoutWallet as $user) {
            Wallet::create(['user_id' => $user->id]);
        }
    }
}