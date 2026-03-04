<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class CurrentSubscriptionService
{
    public function activeSubscriptionFor(User $user): ?Subscription
    {
        return Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest('starts_at')
            ->first();
    }

    public function activePlanFor(User $user): ?Plan
    {
        $subscription = $this->activeSubscriptionFor($user);

        if (!$subscription) {
            return null;
        }

        return Plan::find($subscription->plan_id);
    }
}
