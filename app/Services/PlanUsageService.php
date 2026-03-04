<?php

namespace App\Services;

use App\Models\User;
use App\Support\PlanLimits;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PlanUsageService
{
    public function routersCount(User $user): int
    {
        return (int) DB::table('routers')
            ->where('user_id', $user->id)
            ->count();
    }

    public function vpnAccountsCount(User $user): int
    {
        return (int) DB::table('vpn_accounts')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->count();
    }

    public function vouchersConnectedCount(User $user): int
    {
        return (int) $this->activeRadacctQuery()
            ->whereExists(function (Builder $query) use ($user) {
                $query->selectRaw('1')
                    ->from('vouchers')
                    ->where('vouchers.user_id', $user->id)
                    ->whereRaw('LOWER(TRIM(vouchers.code)) = LOWER(TRIM(radacct.username))');
            })
            ->selectRaw('COUNT(DISTINCT LOWER(TRIM(radacct.username))) as aggregate')
            ->value('aggregate');
    }

    public function usage(User $user): array
    {
        return [
            PlanLimits::KEY_ROUTERS => $this->routersCount($user),
            PlanLimits::KEY_VPN_ACCOUNTS => $this->vpnAccountsCount($user),
            PlanLimits::KEY_VOUCHERS_CONNECTED => $this->vouchersConnectedCount($user),
        ];
    }

    private function activeRadacctQuery(): Builder
    {
        return DB::table('radacct')
            ->whereNull('radacct.acctstoptime')
            ->where(function (Builder $query) {
                $query->where('radacct.acctupdatetime', '>', now()->subDay())
                    ->orWhere(function (Builder $fallback) {
                        $fallback->whereNull('radacct.acctupdatetime')
                            ->where('radacct.acctstarttime', '>', now()->subDay());
                    });
            });
    }
}
