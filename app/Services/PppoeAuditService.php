<?php

namespace App\Services;

use App\Models\PppoeAccount;
use App\Models\PppoeAuditLog;
use Illuminate\Support\Facades\Auth;

class PppoeAuditService
{
    public function log(
        int $userId,
        string $action,
        string $status = 'ok',
        ?string $message = null,
        array $context = [],
        ?PppoeAccount $account = null,
        bool $provisioned = false
    ): void {
        PppoeAuditLog::create([
            'user_id' => $userId,
            'pppoe_account_id' => $account?->id,
            'acted_by' => Auth::id(),
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'context' => $context,
            'provisioned_at' => $provisioned ? now() : null,
        ]);
    }
}
