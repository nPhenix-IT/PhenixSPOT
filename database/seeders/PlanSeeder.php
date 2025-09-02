<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::create([
            'name' => 'Essentiel',
            'slug' => 'essentiel',
            'price' => 200.00,
            'interval' => 'month',
            'features' => json_encode([
                'routers' => 1,
                'vouchers' => 500,
                'vpn_accounts' => 1,
            ]),
        ]);

        Plan::create([
            'name' => 'Performance',
            'slug' => 'performance',
            'price' => 2500.00,
            'interval' => 'month',
            'features' => json_encode([
                'routers' => 5,
                'vouchers' => 2500,
                'vpn_accounts' => 5,
            ]),
        ]);
    }
}
