<?php
namespace App\Services;

use App\Models\VpnAccount;
use App\Models\VpnServer;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class MikroTikApiService
{
    private $client;
    private $server;

    public function __construct(VpnServer $server)
    {
        $this->server = $server;
        try {
            $config = new Config([
                'host' => $server->ip_address,
                'user' => $server->api_user,
                'pass' => $server->api_password,
                'port' => (int) $server->api_port,
                'timeout' => 10,
            ]);
            $this->client = new Client($config);
        } catch (\Throwable $e) {
            throw new \Exception('Impossible de se connecter au serveur VPN : ' . $e->getMessage());
        }
    }

    public function createVpnUser(VpnAccount $account): bool
    {
        $query = (new Query('/ppp/secret/add'))
            ->equal('name', $account->username)
            ->equal('password', $account->password)
            ->equal('service', $account->vpn_type)
            ->equal('profile', 'default-encryption')
            ->equal('remote-address', $account->local_ip_address)
            ->equal('comment', 'Créé par PhenixSPOT pour ' . $account->user->name);

        $this->client->query($query)->read();
        return true;
    }

    public function deleteVpnUser(VpnAccount $account): bool
    {
        $secrets = $this->client->query((new Query('/ppp/secret/print'))->where('name', $account->username))->read();
        if (!empty($secrets)) {
            $this->client->query((new Query('/ppp/secret/remove'))->equal('.id', $secrets[0]['.id']))->read();
            return true;
        }
        return false;
    }

    public function toggleVpnUser(VpnAccount $account, bool $status): void
    {
        $secrets = $this->client->query((new Query('/ppp/secret/print'))->where('name', $account->username))->read();
        if (!empty($secrets)) {
            $query = (new Query('/ppp/secret/set'))
                ->equal('.id', $secrets[0]['.id'])
                ->equal('disabled', $status ? 'no' : 'yes');
            $this->client->query($query)->read();
        }
    }

    public function createPortForwardingRules(VpnAccount $account): void
    {
        $rules = [];
        if ($account->forward_api) $rules[8728] = 'api';
        if ($account->forward_winbox) $rules[8291] = 'winbox';
        if ($account->forward_web) $rules[80] = 'web';

        foreach ($rules as $port => $name) {
            $publicPort = $this->findNextAvailablePublicPort();

            $query = (new Query('/ip/firewall/nat/add'))
                ->equal('chain', 'dstnat')
                ->equal('action', 'dst-nat')
                ->equal('protocol', 'tcp')
                ->equal('dst-address', $this->server->ip_address)
                ->equal('dst-port', $publicPort)
                ->equal('to-addresses', $account->local_ip_address)
                ->equal('to-ports', $port)
                ->equal('comment', "NAT pour {$account->username} ({$name})");

            $this->client->query($query)->read();
        }
    }

    public function deletePortForwardingRules(VpnAccount $account): void
    {
        $commentToFind = "NAT pour {$account->username}";
        $rules = $this->client->query((new Query('/ip/firewall/nat/print'))->where('comment', '~', $commentToFind))->read();

        foreach ($rules as $rule) {
            $this->client->query((new Query('/ip/firewall/nat/remove'))->equal('.id', $rule['.id']))->read();
        }
    }

    public function findNextAvailableIp(): ?string
    {
        if (!$this->server->ip_range) {
            throw new \Exception("La plage d'adresses IP n'est pas configurée pour ce serveur.");
        }

        list($startIp, $endIp) = explode('-', $this->server->ip_range);
        $start = ip2long($startIp);
        $end = ip2long($endIp);

        $usedIps = VpnAccount::where('vpn_server_id', $this->server->id)
            ->pluck('local_ip_address')
            ->all();

        for ($i = $start; $i <= $end; $i++) {
            $currentIp = long2ip($i);
            if (!in_array($currentIp, $usedIps)) {
                return $currentIp;
            }
        }

        return null; // Aucune IP disponible
    }

    private function findNextAvailablePublicPort(int $startPort = 1000, int $endPort = 65000): int
    {
        $natRules = $this->client->query('/ip/firewall/nat/print')->read();
        $usedPorts = array_column($natRules, 'dst-port');

        for ($port = $startPort; $port <= $endPort; $port++) {
            if (!in_array($port, $usedPorts)) {
                return $port;
            }
        }

        throw new \Exception("Aucun port public disponible pour la redirection NAT.");
    }
}