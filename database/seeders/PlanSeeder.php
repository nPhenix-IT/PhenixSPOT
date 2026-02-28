<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['name' => 'Starter'],
            [
                'slug' => 'starter',
                'description' => 'Pour cybercafés, petits hotspots et hôtels locaux.',
                'price_monthly' => 10000,
                'price_annually' => 120000,
                'features' => [
                    'routers' => 1,
                    'vpn_accounts' => 1,
                    'active_users' => 1000,
                    'pppoe' => false,
                    'hotspot' => true,
                    'vouchers' => true,
                    'sales_page' => true,
                    'advanced_reports' => true,
                    'support_level' => 'Standard',
                ],
                'is_active' => true,
            ]
        );
        
        Plan::updateOrCreate(
            ['name' => 'Pro'],
            [
                'slug' => 'pro',
                'description' => 'Pour WISP locaux et zones WiFi publiques.',
                'price_monthly' => 25000,
                'price_annually' => 300000,
                'features' => [
                    'routers' => 5,
                    'vpn_accounts' => 5,
                    'active_users' => 5000,
                    'pppoe' => true,
                    'hotspot' => true,
                    'vouchers' => true,
                    'sales_page' => true,
                    'advanced_reports' => true,
                    'support_level' => 'Prioritaire',
                ],
                'is_active' => true,
            ]
        );

        Plan::updateOrCreate(
            ['name' => 'ISP'],
            [
                'slug' => 'isp',
                'description' => 'Pour fournisseurs Internet régionaux.',
                'price_monthly' => 60000,
                'price_annually' => 720000,
                'features' => [
                    'routers' => 'illimite',
                    'vpn_accounts' => 'illimite',
                    'active_users' => 'illimite',
                    'pppoe' => true,
                    'hotspot' => true,
                    'vouchers' => true,
                    'sales_page' => true,
                    'advanced_reports' => true,
                    'support_level' => 'Prioritaire',
                ],
                'is_active' => true,
            ]
        );
    }
}