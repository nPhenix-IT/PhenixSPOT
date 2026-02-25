<?php

namespace App\Services\WireGuard;

use App\Models\Router;
use App\Models\VpnServer;
use App\Models\WireguardClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WireGuardGeneratorService
{
    public function __construct(
        private readonly WireGuardIpAllocator $allocator,
        private readonly WireGuardKeyGenerator $keyGenerator,
    ) {
    }

    public function createClientForRouter(Router $router): WireguardClient
    {
        $server = VpnServer::query()
            ->where('server_type', 'wireguard')
            ->where('is_active', true)
            ->first();

        if (!$server) {
            throw new RuntimeException('Aucun serveur WireGuard actif disponible.');
        }

        return DB::transaction(function () use ($router, $server) {
            $existing = WireguardClient::where('router_id', $router->id)->first();
            if ($existing) {
                return $existing;
            }

            $clientIp = $this->allocator->allocateIp($server);
            $keys = $this->keyGenerator->generateClientKeypair();
            $psk = $this->keyGenerator->generatePresharedKey();

            return WireguardClient::create([
                'vpn_server_id' => $server->id,
                'router_id' => $router->id,
                'client_ip' => $clientIp,
                'client_public_key' => $keys['public'],
                'client_private_key' => $keys['private'],
                'preshared_key' => $psk,
                'is_active' => true,
            ]);
        });
    }

    public function buildMikrotikWireguardScript(WireguardClient $client, ?string $wgInterface = null): string
    {
        $server = $client->wireguardServer;

        $interfaceName = $wgInterface ?: ($server->wg_interface ?: 'wg-phenixspot');
        $endpointAddress = $server->wg_endpoint_address;
        $endpointPort = (int) ($server->wg_endpoint_port ?: 51820);
        $serverPublicKey = $server->wg_server_public_key;
        $keepalive = (int) ($server->wg_persistent_keepalive ?: 25);

        if (!$endpointAddress || !$serverPublicKey) {
            throw new RuntimeException('Configuration WireGuard du serveur incomplète (endpoint/public key).');
        }

        $lines = [];
        $lines[] = '# --- WireGuard client (PhenixSPOT)';
        $lines[] = "/interface wireguard add name={$interfaceName} private-key=\"{$client->client_private_key}\"";

        $peerCommand = "/interface wireguard peers add interface=\"{$interfaceName}\" public-key=\"{$serverPublicKey}\"";
        if (!empty($client->preshared_key)) {
            $peerCommand .= " preshared-key=\"{$client->preshared_key}\"";
        }

        $peerCommand .= " endpoint-address=\"{$endpointAddress}\" endpoint-port={$endpointPort} allowed-address=0.0.0.0/0 persistent-keepalive={$keepalive}";
        $lines[] = $peerCommand;
        $lines[] = "/ip address add address={$client->client_ip} interface={$interfaceName}";

        return implode("\n", $lines);
    }
}
