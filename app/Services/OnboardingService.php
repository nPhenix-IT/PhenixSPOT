<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Router;
use App\Models\SalePageSetting;
use App\Models\User;
use App\Models\Voucher;

class OnboardingService
{
    public function forUser(User $user): array
    {
        $routerCount = Router::where('user_id', $user->id)->count();
        $profileCount = Profile::where('user_id', $user->id)->count();
        $voucherCount = Voucher::where('user_id', $user->id)->count();
        $salePageSetting = SalePageSetting::where('user_id', $user->id)->first();

        $salesPageConfigured = (bool) $salePageSetting;
        $loginTemplatePrepared = (bool) ($salePageSetting && !empty($salePageSetting->login_dns));

        $steps = [
            [
                'key' => 'router',
                'title' => 'Ajouter votre routeur MikroTik/NAS',
                'description' => 'Commencez par déclarer votre routeur pour activer le flux Radius.',
                'route' => route('user.routers.index'),
                'route_label' => 'Ajouter un routeur',
                'done' => $routerCount > 0,
            ],
            [
                'key' => 'profile',
                'title' => 'Créer vos profils de vente',
                'description' => 'Définissez durée, bande passante, data et prix des tickets.',
                'route' => route('user.profiles.index'),
                'route_label' => 'Créer un profil',
                'done' => $profileCount > 0,
            ],
            [
                'key' => 'voucher',
                'title' => 'Générer vos premiers vouchers',
                'description' => 'Créez des codes Wi-Fi pour commencer les ventes.',
                'route' => route('user.vouchers.index'),
                'route_label' => 'Générer des vouchers',
                'done' => $voucherCount > 0,
            ],
            [
                'key' => 'sales_page',
                'title' => 'Personnaliser votre page de vente',
                'description' => 'Configurez la page publique de vente et les options de commission.',
                'route' => route('user.sales-page.edit'),
                'route_label' => 'Configurer la page de vente',
                'done' => $salesPageConfigured,
            ],
            [
                'key' => 'login_template',
                'title' => 'Intégrer le template login sur MikroTik',
                'description' => 'Copiez le script d\'installation du template login pour finaliser l\'intégration.',
                'route' => route('user.sales-page.edit'),
                'route_label' => 'Installer le template login',
                'done' => $loginTemplatePrepared,
            ],
        ];

        $completed = collect($steps)->where('done', true)->count();
        $total = count($steps);
        $nextStep = collect($steps)->firstWhere('done', false);

        return [
            'show' => $completed < $total,
            'completed' => $completed,
            'total' => $total,
            'progress_percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'next_step' => $nextStep,
            'steps' => $steps,
        ];
    }
}
