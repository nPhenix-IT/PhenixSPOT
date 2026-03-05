<?php

namespace App\Services;

use App\Models\PppoeIpReservation;
use App\Models\PppoeProfile;

class PppoeIpPoolService
{
    public function allocate(PppoeProfile $profile, int $userId, ?int $accountId = null, ?int $actedBy = null): ?string
    {
        [$start, $end] = $this->parseRange($profile->remote_pool);
        if (!$start || !$end) {
            return null;
        }

        $used = $profile->accounts()
            ->whereNotNull('ip_address')
            ->pluck('ip_address')
            ->map(fn ($ip) => trim((string) $ip))
            ->filter()
            ->values()
            ->all();

        $reserved = PppoeIpReservation::query()
            ->where('pppoe_profile_id', $profile->id)
            ->whereIn('status', ['reserved', 'allocated'])
            ->pluck('ip_address')
            ->map(fn ($ip) => trim((string) $ip))
            ->filter()
            ->values()
            ->all();

        $excluded = $this->excludedIps($profile);
        $taken = array_unique(array_merge($used, $reserved, $excluded));

        for ($cursor = ip2long($start); $cursor <= ip2long($end); $cursor++) {
            $ip = long2ip($cursor);
            if (in_array($ip, $taken, true)) {
                continue;
            }

            PppoeIpReservation::updateOrCreate(
                ['pppoe_profile_id' => $profile->id, 'ip_address' => $ip],
                [
                    'user_id' => $userId,
                    'pppoe_account_id' => $accountId,
                    'status' => 'allocated',
                    'acted_by' => $actedBy,
                    'note' => 'Dynamic allocation',
                ]
            );

            return $ip;
        }

        return null;
    }

    public function release(?int $profileId, ?string $ipAddress): void
    {
        if (!$profileId || !$ipAddress) {
            return;
        }

        PppoeIpReservation::query()
            ->where('pppoe_profile_id', $profileId)
            ->where('ip_address', trim($ipAddress))
            ->delete();
    }

    public function isIpTaken(int $profileId, string $ip, ?int $ignoreAccountId = null): bool
    {
        $query = \App\Models\PppoeAccount::query()
            ->where('pppoe_profile_id', $profileId)
            ->where('ip_address', trim($ip));

        if ($ignoreAccountId) {
            $query->where('id', '!=', $ignoreAccountId);
        }

        return $query->exists();
    }

    private function excludedIps(PppoeProfile $profile): array
    {
        $raw = trim((string) ($profile->pool_exclusions ?? ''));
        if ($raw === '') {
            return PppoeIpReservation::query()
                ->where('pppoe_profile_id', $profile->id)
                ->where('status', 'excluded')
                ->pluck('ip_address')
                ->map(fn ($ip) => trim((string) $ip))
                ->filter()->values()->all();
        }

        return collect(preg_split('/[,;\s]+/', $raw) ?: [])
            ->map(fn ($ip) => trim((string) $ip))
            ->filter()
            ->values()
            ->all();
    }

    private function parseRange(?string $range): array
    {
        $value = trim((string) $range);
        if ($value === '' || !str_contains($value, '-')) {
            return [null, null];
        }

        [$start, $end] = array_map('trim', explode('-', $value, 2));
        if (!filter_var($start, FILTER_VALIDATE_IP) || !filter_var($end, FILTER_VALIDATE_IP)) {
            return [null, null];
        }

        if (ip2long($start) > ip2long($end)) {
            return [null, null];
        }

        return [$start, $end];
    }
}
