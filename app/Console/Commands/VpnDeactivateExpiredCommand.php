<?php

namespace App\Console\Commands;

use App\Services\VpnLifecycleService;
use Illuminate\Console\Command;

class VpnDeactivateExpiredCommand extends Command
{
    protected $signature = 'vpn:deactivate-expired';

    protected $description = "Désactive automatiquement les comptes VPN à leur date d'expiration";

    public function handle(VpnLifecycleService $service): int
    {
        $stats = $service->deactivateExpiredAccounts();

        $this->info('Surveillance expiration VPN terminée.');
        $this->line('Comptes marqués expirés: ' . (int) ($stats['marked_expired'] ?? 0));
        $this->line('Comptes désactivés sur MikroTik: ' . (int) ($stats['disabled_on_router'] ?? 0));
        $this->line('Erreurs routeur: ' . (int) ($stats['router_errors'] ?? 0));

        return self::SUCCESS;
    }
}
