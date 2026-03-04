<?php

namespace App\Services;

use App\Models\Radcheck;
use App\Models\Radusergroup;
use App\Models\Subscription;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class VoucherLifecycleService
{
    /**
     * Désactive/réactive les vouchers NON utilisés (status=new)
     * selon l'état de l'abonnement utilisateur.
     */
    public function syncActivationWithSubscription(): array
    {
        $now = now();

        $activeUserIds = Subscription::query()
            ->where('status', 'active')
            ->where('ends_at', '>', $now)
            ->pluck('user_id')
            ->unique()
            ->values();

        $deactivated = 0;
        $reactivated = 0;

        Voucher::query()
            ->where('status', 'new')
            ->where('is_active', true)
            ->when($activeUserIds->isNotEmpty(), fn ($q) => $q->whereNotIn('user_id', $activeUserIds))
            ->when($activeUserIds->isEmpty(), fn ($q) => $q)
            ->chunkById(500, function ($vouchers) use (&$deactivated) {
                $ids = $vouchers->pluck('id')->all();
                if (empty($ids)) {
                    return;
                }

                $count = Voucher::query()
                    ->whereIn('id', $ids)
                    ->update(['is_active' => false]);

                $deactivated += $count;
            });

        if ($activeUserIds->isNotEmpty()) {
            Voucher::query()
                ->where('status', 'new')
                ->where('is_active', false)
                ->whereIn('user_id', $activeUserIds)
                ->chunkById(500, function ($vouchers) use (&$reactivated) {
                    $ids = $vouchers->pluck('id')->all();
                    if (empty($ids)) {
                        return;
                    }

                    $count = Voucher::query()
                        ->whereIn('id', $ids)
                        ->update(['is_active' => true]);

                    $reactivated += $count;
                });
        }

        return [
            'deactivated' => $deactivated,
            'reactivated' => $reactivated,
        ];
    }

    /**
     * 1) Marque "expired" les vouchers utilisés dont la validité est dépassée.
     * 2) Supprime les vouchers expirés 24h après leur date d'expiration.
     */
    public function expireAndCleanupUsedVouchers(): array
    {
        $now = now();
        $expired = 0;
        $deleted = 0;

        Voucher::query()
            ->with('profile:id,validity_period')
            ->where('status', 'used')
            ->whereNotNull('used_at')
            ->chunkById(500, function ($vouchers) use ($now, &$expired) {
                foreach ($vouchers as $voucher) {
                    $validity = (int) ($voucher->profile->validity_period ?? 0);
                    if ($validity <= 0 || !$voucher->used_at) {
                        continue;
                    }

                    $expiresAt = $voucher->used_at->copy()->addSeconds($validity);
                    if ($now->greaterThanOrEqualTo($expiresAt)) {
                        $voucher->forceFill([
                            'status' => 'expired',
                            'is_active' => false,
                        ])->save();
                        $expired++;
                    }
                }
            });

        Voucher::query()
            ->with('profile:id,validity_period')
            ->where('status', 'expired')
            ->whereNotNull('used_at')
            ->chunkById(500, function ($vouchers) use ($now, &$deleted) {
                foreach ($vouchers as $voucher) {
                    $validity = (int) ($voucher->profile->validity_period ?? 0);
                    if ($validity <= 0 || !$voucher->used_at) {
                        continue;
                    }

                    $expiresAt = $voucher->used_at->copy()->addSeconds($validity);
                    $deleteAfter = $expiresAt->copy()->addDay();

                    if ($now->greaterThanOrEqualTo($deleteAfter)) {
                        DB::transaction(function () use ($voucher, &$deleted) {
                            Radcheck::query()->where('username', $voucher->code)->delete();
                            Radusergroup::query()->where('username', $voucher->code)->delete();
                            $voucher->delete();
                            $deleted++;
                        });
                    }
                }
            });

        return [
            'expired_marked' => $expired,
            'deleted_after_24h' => $deleted,
        ];
    }
}
