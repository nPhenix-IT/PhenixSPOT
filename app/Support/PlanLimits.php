<?php

namespace App\Support;

final class PlanLimits
{
    public const KEY_ROUTERS = 'routers';
    public const KEY_VPN_ACCOUNTS = 'vpn_accounts';
    public const KEY_VOUCHERS_CONNECTED = 'vouchers_connected';

    public static function parseLimit(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value < 0 ? null : $value;
        }

        $normalized = trim(mb_strtolower((string) $value));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['-1', 'illimite', 'illimité', 'unlimited', 'infini', '∞', 'infinite'], true)) {
            return null;
        }

        if (preg_match('/^-?\d+$/', $normalized) === 1) {
            $number = (int) $normalized;
            return $number < 0 ? null : $number;
        }

        if (preg_match('/(-?\d+)/', $normalized, $matches) === 1) {
            $number = (int) ($matches[1] ?? 0);
            return $number < 0 ? null : $number;
        }

        return null;
    }

    public static function format(?int $limit): string
    {
        return $limit === null ? 'Illimité' : number_format($limit, 0, ',', ' ');
    }
}
