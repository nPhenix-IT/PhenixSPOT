<?php

namespace App\Services;

use App\Models\VpnAccount;
use Illuminate\Support\Facades\Log;

class VpnLifecycleService
{
    /**
     * Désactive les comptes VPN expirés côté application et côté MikroTik.
     */
    public function deactivateExpiredAccounts(): array
    {
        $now = now();
        $markedExpired = 0;
        $disabledOnRouter = 0;
        $routerErrors = 0;

        VpnAccount::query()
            ->with('server')
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->chunkById(200, function ($accounts) use (&$markedExpired, &$disabledOnRouter, &$routerErrors) {
                foreach ($accounts as $account) {
                    $account->forceFill([
                        'status' => 'expired',
                        'is_active' => false,
                    ])->save();

                    $markedExpired++;

                    try {
                        if ($account->server) {
                            app(MikroTikApiService::class, ['server' => $account->server])
                                ->deactivateVpnUser($account->username);
                            $disabledOnRouter++;
                        }
                    } catch (\Throwable $e) {
                        $routerErrors++;
                        Log::warning('VPN expiration monitor: failed to disable user on MikroTik', [
                            'vpn_account_id' => $account->id,
                            'username' => $account->username,
                            'vpn_server_id' => $account->vpn_server_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return [
            'marked_expired' => $markedExpired,
            'disabled_on_router' => $disabledOnRouter,
            'router_errors' => $routerErrors,
        ];
    }
}
