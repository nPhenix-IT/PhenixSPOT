<?php

namespace App\Services\WireGuard;

use App\Models\VpnServer;
use App\Models\WireguardClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WireGuardIpAllocator
{
    public function allocateIp(VpnServer $server): string
    {
        [$network, $prefix] = $this->splitSubnet($server->wg_network);

        if ($prefix !== 16) {
            throw new RuntimeException("Allocator: seul /16 est supporté actuellement ({$server->wg_network}).");
        }

        return DB::transaction(function () use ($server, $network) {
            $free = DB::table('wireguard_ip_pool')
                ->where('vpn_server_id', $server->id)
                ->where('is_allocated', false)
                ->orderBy('released_at')
                ->lockForUpdate()
                ->first();

            if ($free) {
                DB::table('wireguard_ip_pool')
                    ->where('id', $free->id)
                    ->update([
                        'is_allocated' => true,
                        'allocated_at' => now(),
                        'released_at' => null,
                        'updated_at' => now(),
                    ]);

                return $free->ip . '/32';
            }

            $newIp = $this->findFirstAvailableIp($server, $network);

            DB::table('wireguard_ip_pool')->insert([
                'vpn_server_id' => $server->id,
                'ip' => $newIp,
                'is_allocated' => true,
                'allocated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $newIp . '/32';
        });
    }


    public function peekNextIp(VpnServer $server): string
    {
        [$network, $prefix] = $this->splitSubnet($server->wg_network);

        if ($prefix !== 16) {
            throw new RuntimeException("Allocator: seul /16 est supporté actuellement ({$server->wg_network}).");
        }

        $free = DB::table('wireguard_ip_pool')
            ->where('vpn_server_id', $server->id)
            ->where('is_allocated', false)
            ->orderBy('released_at')
            ->first();

        if ($free) {
            return $free->ip . '/32';
        }

        return $this->findFirstAvailableIp($server, $network) . '/32';
    }

    public function releaseIp(VpnServer $server, string $clientIpCidr): void
    {
        $ip = explode('/', $clientIpCidr)[0];

        DB::table('wireguard_ip_pool')
            ->where('vpn_server_id', $server->id)
            ->where('ip', $ip)
            ->update([
                'is_allocated' => false,
                'released_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function findFirstAvailableIp(VpnServer $server, string $network): string
    {
        $start = $this->makeIp($network, 0, 2);
        $end = $this->makeIp($network, 255, 254);
        $reserved = [
            $network,
            $server->wg_server_address ?: $this->makeIp($network, 0, 1),
            $this->makeIp($network, 255, 255),
        ];

        $last = WireguardClient::where('vpn_server_id', $server->id)->orderByDesc('id')->value('client_ip');
        $cursor = $last ? explode('/', $last)[0] : $start;

        $maxAttempts = 200000;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $cursor = $this->nextIpInRange($cursor, $start, $end);

            if (in_array($cursor, $reserved, true)) {
                continue;
            }

            $existsClient = WireguardClient::where('vpn_server_id', $server->id)
                ->where('client_ip', $cursor . '/32')
                ->exists();

            if ($existsClient) {
                continue;
            }

            $existsPool = DB::table('wireguard_ip_pool')
                ->where('vpn_server_id', $server->id)
                ->where('ip', $cursor)
                ->exists();

            if (!$existsPool) {
                return $cursor;
            }
        }

        throw new RuntimeException("Allocator: aucune IP disponible dans {$server->wg_network}.");
    }

    private function splitSubnet(?string $subnet): array
    {
        if (!$subnet || !str_contains($subnet, '/')) {
            throw new RuntimeException('Subnet WireGuard invalide.');
        }

        [$network, $prefix] = explode('/', $subnet, 2);
        return [$network, (int) $prefix];
    }

    private function makeIp(string $network, int $third, int $fourth): string
    {
        $parts = explode('.', $network);
        if (count($parts) !== 4) {
            throw new RuntimeException("Subnet invalide: {$network}");
        }

        return "{$parts[0]}.{$parts[1]}.{$third}.{$fourth}";
    }

    private function nextIpInRange(string $current, string $start, string $end): string
    {
        [$a, $b, $c, $d] = array_map('intval', explode('.', $current));

        $d++;
        if ($d > 254) {
            $d = 0;
            $c++;
        }

        if ($c > 255) {
            return $start;
        }

        $next = "{$a}.{$b}.{$c}.{$d}";

        if ($this->ipToInt($next) > $this->ipToInt($end)) {
            return $start;
        }

        if ($this->ipToInt($next) < $this->ipToInt($start)) {
            return $start;
        }

        return $next;
    }

    private function ipToInt(string $ip): int
    {
        $p = array_map('intval', explode('.', $ip));
        return ($p[0] << 24) + ($p[1] << 16) + ($p[2] << 8) + $p[3];
    }
}
