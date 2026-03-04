<?php

namespace App\Services;

use App\Models\User;
use App\Support\PlanLimits;

class RadiusLimitGuardService
{
    public function __construct(private readonly PlanLimitService $planLimitService) {}

    public function canVoucherConnect(User $owner): bool
    {
        return $this->planLimitService->can($owner, PlanLimits::KEY_VOUCHERS_CONNECTED, 1);
    }
}
