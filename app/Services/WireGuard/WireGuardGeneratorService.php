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
        private readonly WireGuardServerProvisioner $provisioner, // NEW
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
                // Optionnel : s’assurer que le peer existe côté serveur
                $this->provisioner->addOrUpdatePeer($server, $existing);
                return $existing;
            }

            $clientIp = $this->allocator->allocateIp($server);
            $keys = $this->keyGenerator->generateClientKeypair();
            $psk = $this->keyGenerator->generatePresharedKey();

            $client = WireguardClient::create([
                'vpn_server_id' => $server->id,
                'router_id' => $router->id,
                'client_ip' => $clientIp,
                'client_public_key' => $keys['public'],
                'client_private_key' => $keys['private'],
                'preshared_key' => $psk,
                'is_active' => true,
            ]);

            // NEW: pousse le peer sur Ubuntu WireGuard (wg set … + save)
            $this->provisioner->addOrUpdatePeer($server, $client);

            return $client;
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
        // IMPORTANT: radius est sur le même serveur → on n’autorise que l’IP WG serveur
        $serverWgIp = $server->wg_server_address ?: $this->guessServerWgIp($server->wg_network);
        if (!$serverWgIp) {
            throw new RuntimeException('wg_server_address manquant (ou wg_network invalide) sur le serveur WireGuard.');
        }

        $lines = [];
        $lines[] = '# --- WireGuard client (PhenixSpot / idempotent)';
        
        $mtIface = "{$interfaceName}-PhenixSPOT";
        $allowedAddress = $serverWgIp . '/32';
        $routerName  = $client->router?->name ?? ('router-' . ($client->router_id ?? $client->id ?? 'x'));
        $routerLabel = preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string) $routerName);
        
        // ✅ Tag stable : prioriser router_id (stable) puis client id
        $stableId = $client->router_id ?? $client->id ?? uniqid();
        
        $peerTag = 'PhenixSpot-WG-PEER-' . $stableId;
        $peerComment = $peerTag . ' | ' . $routerLabel;
        
        // 2. Déclaration des variables RouterOS (Utilisation de \$ pour l'échappement PHP)
        $lines[] = ":local iface \"{$mtIface}\";";
        $lines[] = ":local peerTag \"{$peerTag}\";";
        $lines[] = ":local peerComment \"{$peerComment}\";";
        $lines[] = ":local serverPubKey \"{$serverPublicKey}\";";
        $lines[] = ":local endpointHost \"{$endpointAddress}\";";
        $lines[] = ":local endpointPort {$endpointPort};";
        $lines[] = ":local allowed \"{$allowedAddress}\";";
        $lines[] = ":local keepalive {$keepalive};";
        
        $psk = (string) ($client->preshared_key ?? '');
        $lines[] = ":local psk \"{$psk}\";";
        
        // 3. Gestion de l'interface
        $lines[] = ":if ([:len [/interface/wireguard find where name=\$iface]] = 0) do={";
        $lines[] = "  /interface/wireguard add name=\$iface private-key=\"{$client->client_private_key}\";";
        $lines[] = "} else={";
        $lines[] = "  /interface/wireguard set [find where name=\$iface] private-key=\"{$client->client_private_key}\";";
        $lines[] = "}";
        
        // 4. Déduplication et gestion des Peers
        $lines[] = ":local ids [/interface/wireguard/peers find where comment~\"\$peerTag\"];";
        $lines[] = ":if ([:len \$ids] > 1) do={";
        $lines[] = "  :local first true;";
        $lines[] = "  :foreach id in=\$ids do={";
        $lines[] = "    :if (\$first=true) do={ :set first false; } else={ /interface/wireguard/peers remove \$id; }";
        $lines[] = "  }";
        $lines[] = "}";
        
        $lines[] = ":local id [/interface/wireguard/peers find where comment~\"\$peerTag\"];";
        $lines[] = ":if ([:len \$id] > 0) do={";
        $lines[] = "  /interface/wireguard/peers set \$id interface=\$iface public-key=\"\$serverPubKey\" endpoint-address=\"\$endpointHost\" endpoint-port=\$endpointPort allowed-address=\"\$allowed\" persistent-keepalive=\$keepalive comment=\"\$peerComment\";";
        $lines[] = "  :if ([:len \$psk] > 0) do={ /interface/wireguard/peers set \$id preshared-key=\"\$psk\"; }";
        $lines[] = "} else={";
        $lines[] = "  /interface/wireguard/peers add interface=\$iface public-key=\"\$serverPubKey\" endpoint-address=\"\$endpointHost\" endpoint-port=\$endpointPort allowed-address=\"\$allowed\" persistent-keepalive=\$keepalive comment=\"\$peerComment\";";
        $lines[] = "  :if ([:len \$psk] > 0) do={ /interface/wireguard/peers set [find where comment=\"\$peerComment\"] preshared-key=\"\$psk\"; }";
        $lines[] = "}";
        
        // 5. IP de l'interface
        $clientAddress = $this->toMikrotikInterfaceCidr((string) $client->client_ip, 16);
        $addrTag = 'PhenixSpot-WG-ADDR-' . ($client->id ?? uniqid());
        
        $lines[] = ":local addrTag \"{$addrTag}\";";
        $lines[] = "/ip address remove [find where comment~\"\$addrTag\"];";
        $lines[] = "/ip address add address=\"{$clientAddress}\" interface=\$iface comment=\"\$addrTag\";";
        
        // Utilisation de "\n" (double quotes) pour un vrai saut de ligne
        return implode("\n", $lines);
    }

    private function guessServerWgIp(?string $network): ?string
    {
        if (!$network || !str_contains($network, '/')) return null;
        [$base] = explode('/', $network, 2);
        $p = explode('.', $base);
        if (count($p) !== 4) return null;
        return "{$p[0]}.{$p[1]}.0.1"; // ex: 10.66.0.1
    }
    
    /**
     * Convertit une IP client (souvent stockée en /32) vers l'IP d'interface MikroTik en /16.
     * Exemple: "10.99.0.3/32" => "10.99.0.3/16"
     */
    private function toMikrotikInterfaceCidr(string $clientIpCidr, int $prefix = 16): string
    {
        $clientIpCidr = trim($clientIpCidr);
    
        // On garde uniquement l’IP (sans le CIDR existant)
        $ip = explode('/', $clientIpCidr, 2)[0];
    
        // Optionnel: validation simple IPv4
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // fallback: on retourne tel quel pour ne pas casser la génération
            return $clientIpCidr;
        }
    
        return $ip . '/' . $prefix;
    }
}