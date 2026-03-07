<?php

namespace App\Services;

use App\Models\SmsSetting;
use App\Models\SmsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SmsCreditService
{
    public function getUnitCost(): float
    {
        return (float) SmsSetting::current()->unit_cost_fcfa;
    }

    public function getSenderNameFor(User $user): ?string
    {
        return $user->sms_sender ?: SmsSetting::current()->default_sender_name;
    }

    public function hasEnoughCredit(User $user, ?float $cost = null): bool
    {
        $required = $cost ?? $this->getUnitCost();
        return (float) $user->sms_credit_balance >= $required;
    }

    public function debitAndLog(User $user, array $payload): bool
    {
        $cost = (float) ($payload['cost'] ?? $this->getUnitCost());

        return DB::transaction(function () use ($user, $payload, $cost) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

            if (!$lockedUser || (float) $lockedUser->sms_credit_balance < $cost) {
                SmsTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'debit',
                    'status' => 'blocked',
                    'units' => 1,
                    'amount_fcfa' => $cost,
                    'balance_after' => $lockedUser ? (float) $lockedUser->sms_credit_balance : 0,
                    'recipient' => $payload['recipient'] ?? null,
                    'sender_name' => $payload['sender_name'] ?? null,
                    'message' => $payload['message'] ?? null,
                    'context' => $payload['context'] ?? null,
                    'meta' => $payload['meta'] ?? ['reason' => 'insufficient_credit'],
                ]);

                return false;
            }

            $lockedUser->sms_credit_balance = (float) $lockedUser->sms_credit_balance - $cost;
            $lockedUser->save();

            SmsTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'debit',
                'status' => 'sent',
                'units' => 1,
                'amount_fcfa' => $cost,
                'balance_after' => (float) $lockedUser->sms_credit_balance,
                'recipient' => $payload['recipient'] ?? null,
                'sender_name' => $payload['sender_name'] ?? null,
                'message' => $payload['message'] ?? null,
                'context' => $payload['context'] ?? null,
                'meta' => $payload['meta'] ?? null,
            ]);

            return true;
        });
    }

    public function markLastDeliveryStatus(User $user, string $recipient, string $message, bool $sent): void
    {
        $query = SmsTransaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->where('recipient', $recipient)
            ->where('message', $message)
            ->latest('id');

        $tx = $query->first();
        if ($tx) {
            $tx->status = $sent ? 'sent' : 'failed';
            $tx->save();
        }
    }

    public function creditFromPackage(User $user, int $packageId, int $credits, float $amountFcfa = 0, string $source = 'wallet', array $meta = []): void
    {
        DB::transaction(function () use ($user, $packageId, $credits, $amountFcfa, $source, $meta) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedUser->sms_credit_balance = (float) $lockedUser->sms_credit_balance + $credits;
            $lockedUser->save();

            SmsTransaction::create([
                'user_id' => $lockedUser->id,
                'sms_package_id' => $packageId,
                'type' => 'credit',
                'status' => 'credited',
                'units' => $credits,
                'amount_fcfa' => $amountFcfa,
                'balance_after' => (float) $lockedUser->sms_credit_balance,
                'context' => 'sms_package_purchase_' . $source,
                'meta' => $meta ?: null,
            ]);
        });
    }
}
