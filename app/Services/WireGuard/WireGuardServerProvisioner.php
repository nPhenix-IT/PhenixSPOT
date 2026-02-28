<?php

namespace App\Services\WireGuard;

use App\Models\VpnServer;
use App\Models\WireguardClient;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class WireGuardServerProvisioner
{
    /**
     * Ajoute (ou met à jour) le peer sur le serveur Ubuntu WireGuard.
     *
     * Correctifs:
     * - Chemins absolus: /usr/bin/wg et /usr/bin/wg-quick
     * - Plus de mktemp+tee dans /tmp (évite Permission denied)
     * - PSK écrite en root dans /run/wg-psk-* via sudo sh -lc, puis supprimée
     * - SSH en BatchMode (jamais de prompt)
     */
    public function addOrUpdatePeer(VpnServer $server, WireguardClient $client): void
    {
        $iface = $server->wg_interface ?: 'wg0';

        $host = $this->getServerValue($server, 'ssh_host');
        $user = $this->getServerValue($server, 'ssh_user');
        $port = (int) ($this->getServerValue($server, 'ssh_port') ?: 22);
        $keyPath = $this->getServerValue($server, 'ssh_private_key_path');

        if (!$host || !$user) {
            throw new RuntimeException('WireGuardServerProvisioner: SSH host/user manquant sur le serveur WireGuard.');
        }

        if (!$client->client_public_key || !$client->client_ip) {
            throw new RuntimeException('WireGuardServerProvisioner: client_public_key ou client_ip manquant.');
        }

        $wg = '/usr/bin/wg';
        $wgQuick = '/usr/bin/wg-quick';

        $psk = $client->preshared_key ?: null;
        $allowedIps = $client->client_ip; // ex: 10.66.0.2/32

        $remoteCmdParts = [];

        if ($psk) {
            // Écriture PSK dans /run en root, puis wg set, puis suppression du fichier
            $cmd = ''
                . 'PSK_FILE=/run/wg-psk-$$; '
                . 'printf "%s" ' . escapeshellarg($psk) . ' > "$PSK_FILE"; '
                . $wg . ' set ' . escapeshellarg($iface)
                . ' peer ' . escapeshellarg($client->client_public_key)
                . ' preshared-key "$PSK_FILE"'
                . ' allowed-ips ' . escapeshellarg($allowedIps) . '; '
                . 'rm -f "$PSK_FILE"';

            $remoteCmdParts[] = 'sudo -n sh -lc ' . escapeshellarg($cmd);
        } else {
            $remoteCmdParts[] =
                'sudo -n ' . $wg . ' set ' . escapeshellarg($iface)
                . ' peer ' . escapeshellarg($client->client_public_key)
                . ' allowed-ips ' . escapeshellarg($allowedIps);
        }

        // Persister la conf dans wg0.conf
        $remoteCmdParts[] = 'sudo -n ' . $wgQuick . ' save ' . escapeshellarg($iface);

        $this->runSsh($host, $user, $port, $keyPath, implode(' && ', $remoteCmdParts), 'addOrUpdatePeer');

        // Vérif simple (ne bloque pas en cas de différence de sortie)
        $this->runSsh($host, $user, $port, $keyPath, 'sudo -n ' . $wg . ' show ' . escapeshellarg($iface), 'verifyPeer');
    }

    /**
     * Retire un peer côté serveur WireGuard.
     */
    public function removePeer(VpnServer $server, WireguardClient $client): void
    {
        $iface = $server->wg_interface ?: 'wg0';

        $host = $this->getServerValue($server, 'ssh_host');
        $user = $this->getServerValue($server, 'ssh_user');
        $port = (int) ($this->getServerValue($server, 'ssh_port') ?: 22);
        $keyPath = $this->getServerValue($server, 'ssh_private_key_path');

        if (!$host || !$user) {
            throw new RuntimeException('WireGuardServerProvisioner: SSH host/user manquant sur le serveur WireGuard.');
        }

        if (!$client->client_public_key) {
            return;
        }

        $wg = '/usr/bin/wg';
        $wgQuick = '/usr/bin/wg-quick';

        $remoteCmd =
            'sudo -n ' . $wg . ' set ' . escapeshellarg($iface)
            . ' peer ' . escapeshellarg($client->client_public_key)
            . ' remove'
            . ' && sudo -n ' . $wgQuick . ' save ' . escapeshellarg($iface);

        $this->runSsh($host, $user, $port, $keyPath, $remoteCmd, 'removePeer');
    }

    /**
     * Exécute une commande SSH en mode non-interactif.
     */
    private function runSsh(string $host, string $user, int $port, ?string $keyPath, string $remoteCmd, string $context): void
    {
        $cmd = [
            'ssh',
            '-p', (string) $port,
            '-o', 'BatchMode=yes',
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'UserKnownHostsFile=/dev/null',
        ];

        if ($keyPath) {
            $cmd[] = '-i';
            $cmd[] = $keyPath;
        }

        $cmd[] = "{$user}@{$host}";
        $cmd[] = $remoteCmd;

        $process = new Process($cmd);
        $process->setTimeout(20);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error("WireGuardServerProvisioner SSH failed ({$context})", [
                'host' => $host,
                'user' => $user,
                'stderr' => $process->getErrorOutput(),
                'stdout' => $process->getOutput(),
            ]);

            throw new RuntimeException('Provision WireGuard échoué (' . $context . '): ' . trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    /**
     * Lit la config depuis la DB (VpnServer) ou fallback config('wireguard.*').
     */
    private function getServerValue(VpnServer $server, string $field): ?string
    {
        if (isset($server->{$field}) && $server->{$field}) {
            return (string) $server->{$field};
        }

        return config("wireguard.{$field}");
    }
}
