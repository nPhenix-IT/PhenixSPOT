<?php

use App\Services\VoucherLifecycleService;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('vouchers:sync-subscription-state', function (VoucherLifecycleService $service) {
    /** @var ClosureCommand $this */
    $stats = $service->syncActivationWithSubscription();

    $this->info('Synchronisation abonnement/vouchers terminée.');
    $this->line('Désactivés: ' . (int) ($stats['deactivated'] ?? 0));
    $this->line('Réactivés: ' . (int) ($stats['reactivated'] ?? 0));
})->purpose('Désactive/réactive les vouchers non utilisés selon l’état de l’abonnement');

Artisan::command('vouchers:expire-and-cleanup', function (VoucherLifecycleService $service) {
    /** @var ClosureCommand $this */
    $stats = $service->expireAndCleanupUsedVouchers();

    $this->info('Cycle expiration/purge vouchers terminé.');
    $this->line('Passés à expired: ' . (int) ($stats['expired_marked'] ?? 0));
    $this->line('Supprimés (expiration +24h): ' . (int) ($stats['deleted_after_24h'] ?? 0));
})->purpose('Marque expirés les vouchers utilisés et supprime ceux expirés depuis plus de 24h');





Artisan::command('vpn:deactivate-expired', function (\App\Services\VpnLifecycleService $service) {
    /** @var ClosureCommand $this */
    $stats = $service->deactivateExpiredAccounts();

    $this->info('Surveillance expiration VPN terminée.');
    $this->line('Comptes marqués expirés: ' . (int) ($stats['marked_expired'] ?? 0));
    $this->line('Comptes désactivés sur MikroTik: ' . (int) ($stats['disabled_on_router'] ?? 0));
    $this->line('Erreurs routeur: ' . (int) ($stats['router_errors'] ?? 0));
})->purpose("Désactive automatiquement les comptes VPN à leur date d'expiration");

Schedule::command('vouchers:sync-subscription-state')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('vouchers:expire-and-cleanup')
    ->hourly()
    ->withoutOverlapping();


Schedule::command('vpn:deactivate-expired')
    ->everyTenMinutes()
    ->withoutOverlapping();