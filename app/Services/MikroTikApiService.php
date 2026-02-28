<?php

 namespace App\Services;
 
 use App\Models\VpnAccount;
 use App\Models\VpnServer;
 use Illuminate\Support\Facades\Auth;
 use RouterOS\Client;
 use RouterOS\Config;
 use RouterOS\Query;
 
 class MikroTikApiService
 {
   private Client $client;
   private VpnServer $server;
 
     public function __construct(VpnServer $server)
     {
         $this->server = $server;
       $config = new Config([
           'host' => $server->ip_address,
           'user' => $server->api_user,
           'pass' => $server->api_password,
           'port' => (int) $server->api_port,
           'timeout' => 8,
       ]);

       $this->client = new Client($config);
     }
 
   public function findFreeIp(): ?string
     {
       $pool = $this->server->ip_pool ?: $this->server->ip_range;
       $poolParts = $pool ? explode('-', $pool) : [];
       if (count($poolParts) !== 2) {
           return null;
         }
         
       $startIp = ip2long(trim($poolParts[0]));
       $endIp = ip2long(trim($poolParts[1]));

       $usedIps = VpnAccount::where('vpn_server_id', $this->server->id)
           ->where('status', 'active')
           ->pluck('remote_ip')
           ->filter()
           ->map(fn ($ip) => ip2long($ip))
           ->toArray();

       for ($i = $startIp; $i <= $endIp; $i++) {
           if (!in_array($i, $usedIps, true)) {
               return long2ip($i);
           }
         }

       return null;
     }
     
   public function assignPorts(bool $needCustom = false): array
     {
       $usedPorts = VpnAccount::where('vpn_server_id', $this->server->id)
           ->get()
           ->flatMap(fn ($acc) => [$acc->port_api, $acc->port_winbox, $acc->port_web, $acc->port_custom])
           ->filter()
           ->toArray();

       $newPorts = [];
       $needed = ['api', 'winbox', 'web'];
       if ($needCustom) {
           $needed[] = 'custom';
       }

       foreach ($needed as $type) {
           do {
               $port = rand(10000, 60000);
           } while (in_array($port, $usedPorts, true) || in_array($port, $newPorts, true));

           $newPorts[$type] = $port;
         }

       return $newPorts;
     }
     
   public function provisionVpnAccount(string $username, string $password, string $remoteIp, string $protocol, array $ports, ?int $customTarget = null): void
     {
       $this->client->query((new Query('/ppp/secret/add'))
           ->equal('name', $username)
           ->equal('password', $password)
           ->equal('profile', $this->server->profile_name)
           ->equal('local-address', $this->server->gateway_ip)
           ->equal('remote-address', $remoteIp)
           ->equal('service', $protocol === 'ovpn' ? 'ovpn' : 'any')
           ->equal('comment', 'Client VPN ID: ' . Auth::id()))
           ->read();

       $services = [
           ['port' => $ports['api'], 'to' => 8728, 'label' => 'API'],
           ['port' => $ports['winbox'], 'to' => 8291, 'label' => 'Winbox'],
           ['port' => $ports['web'], 'to' => 80, 'label' => 'Web'],
       ];

       if (isset($ports['custom']) && $customTarget) {
           $services[] = ['port' => $ports['custom'], 'to' => $customTarget, 'label' => 'Custom'];
       }

       foreach ($services as $srv) {
           $this->client->query((new Query('/ip/firewall/nat/add'))
               ->equal('chain', 'dstnat')
               ->equal('protocol', 'tcp')
               ->equal('dst-port', (string) $srv['port'])
               ->equal('action', 'dst-nat')
               ->equal('to-addresses', $remoteIp)
               ->equal('to-ports', (string) $srv['to'])
               ->equal('comment', "{$username}-NAT-{$srv['label']}"))
               ->read();
         }
     }
     
   public function checkOnlineStatus(string $username): array
     {
       $query = (new Query('/ppp/active/print'))->where('name', $username);
       $activeConnections = $this->client->query($query)->read();

       $isOnline = count($activeConnections) > 0;

       return [
           'is_online' => $isOnline,
           'remote_ip' => $isOnline ? ($activeConnections[0]['address'] ?? 'N/A') : null,
       ];
   }

   public function updateNatRule(string $comment, int $newToPort): void
   {
       $rules = $this->client->query((new Query('/ip/firewall/nat/print'))->where('comment', $comment))->read();
       if (isset($rules[0]['.id'])) {
           $this->client->query((new Query('/ip/firewall/nat/set'))
               ->equal('.id', $rules[0]['.id'])
               ->equal('to-ports', (string) $newToPort))
               ->read();
         }
         
     }
     
   public function reactivateVpnUser(string $username): void
     {
      
       $secrets = $this->client->query((new Query('/ppp/secret/print'))->where('name', $username))->read();
       if (!empty($secrets) && isset($secrets[0]['.id'])) {
           $this->client->query((new Query('/ppp/secret/set'))
               ->equal('.id', $secrets[0]['.id'])
               ->equal('disabled', 'no'))
               ->read();
       }
   }

   public function deleteVpnUserAndNat(string $username): void
    {
        // Récupérer toutes les règles NAT
        $rules = $this->client
            ->query(new Query('/ip/firewall/nat/print'))
            ->read();
    
        foreach ($rules as $rule) {
    
            if (
                isset($rule['.id'], $rule['comment']) &&
                str_contains($rule['comment'], "{$username}-NAT-")
            ) {
                $this->client
                    ->query(
                        (new Query('/ip/firewall/nat/remove'))
                            ->equal('.id', $rule['.id'])
                    )
                    ->read();
            }
        }
    
        // Supprimer le secret PPP
        $secrets = $this->client
            ->query((new Query('/ppp/secret/print'))->where('name', $username))
            ->read();
    
        if (!empty($secrets) && isset($secrets[0]['.id'])) {
            $this->client
                ->query(
                    (new Query('/ppp/secret/remove'))
                        ->equal('.id', $secrets[0]['.id'])
                )
                ->read();
        }
    }

}
