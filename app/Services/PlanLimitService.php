<?php

namespace App\Services;

use App\Models\User;
use App\Support\PlanLimits;

class PlanLimitService
{
    public function __construct(
        private readonly CurrentSubscriptionService $currentSubscriptionService,
        private readonly PlanUsageService $planUsageService,
    ) {}

    public function hasActiveSubscription(User $user): bool
    {
        if ($user->hasRole(['Super-admin', 'Admin'])) {
            return true;
        }

        return $this->currentSubscriptionService->activeSubscriptionFor($user) !== null;
    }

    public function limits(User $user): array
    {
        if ($user->hasRole(['Super-admin', 'Admin'])) {
            return [
                PlanLimits::KEY_ROUTERS => null,
                PlanLimits::KEY_VPN_ACCOUNTS => null,
                PlanLimits::KEY_VOUCHERS_CONNECTED => null,
            ];
        }

        $plan = $this->currentSubscriptionService->activePlanFor($user);
        $features = is_array($plan?->features) ? $plan->features : [];

        return [
            PlanLimits::KEY_ROUTERS => PlanLimits::parseLimit($features[PlanLimits::KEY_ROUTERS] ?? 0),
            PlanLimits::KEY_VPN_ACCOUNTS => PlanLimits::parseLimit($features[PlanLimits::KEY_VPN_ACCOUNTS] ?? 0),
            PlanLimits::KEY_VOUCHERS_CONNECTED => PlanLimits::parseLimit($features[PlanLimits::KEY_VOUCHERS_CONNECTED] ?? ($features['active_users'] ?? 0)),
        ];
    }

    public function usage(User $user): array
    {
        return $this->planUsageService->usage($user);
    }

    public function remaining(User $user): array
    {
        $limits = $this->limits($user);
        $usage = $this->usage($user);
        $remaining = [];

        foreach ($limits as $key => $limit) {
            $remaining[$key] = $limit === null ? null : max(0, $limit - (int) ($usage[$key] ?? 0));
        }

        return $remaining;
    }

    public function can(User $user, string $key, int $increment = 1): bool
    {
        $limit = $this->limits($user)[$key] ?? 0;
        if ($limit === null) {
            return true;
        }

        $usage = $this->usage($user);
        return ((int) ($usage[$key] ?? 0) + $increment) <= $limit;
    }
}
