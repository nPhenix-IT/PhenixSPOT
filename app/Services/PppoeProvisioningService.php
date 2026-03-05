<?php

namespace App\Services;

use App\Models\PppoeAccount;
use App\Models\Radcheck;
use App\Models\Radusergroup;
use Illuminate\Support\Facades\DB;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class PppoeProvisioningService
{
    public function sync(PppoeAccount $account): array
    {
        $this->syncRadius($account);
        $nasResult = $this->syncNas($account);

        return $nasResult;
    }

    public function delete(PppoeAccount $account): void
    {
        Radcheck::where('username', $account->username)->delete();
        Radusergroup::where('username', $account->username)->delete();

        $this->deleteNasSecret($account);
    }

    private function syncRadius(PppoeAccount $account): void
    {
        Radcheck::where('username', $account->username)->delete();

        Radcheck::create([
            'username' => $account->username,
            'attribute' => 'Cleartext-Password',
            'op' => ':=',
            'value' => $account->password,
        ]);

        Radcheck::create([
            'username' => $account->username,
            'attribute' => 'Service-Type',
            'op' => ':=',
            'value' => 'Framed-User',
        ]);

        if (!empty($account->ip_address)) {
            Radcheck::create([
                'username' => $account->username,
                'attribute' => 'Framed-IP-Address',
                'op' => ':=',
                'value' => $account->ip_address,
            ]);
        }

        Radcheck::create([
            'username' => $account->username,
            'attribute' => 'Auth-Type',
            'op' => ':=',
            'value' => $account->is_active ? 'Accept' : 'Reject',
        ]);

        Radusergroup::where('username', $account->username)->delete();
        if (!empty($account->profile?->name)) {
            $this->syncProfileLimits($account);

            Radusergroup::create([
                'username' => $account->username,
                'groupname' => $account->profile->name,
                'priority' => 1,
            ]);
        }
    }

    private function syncProfileLimits(PppoeAccount $account): void
    {
        $profile = $account->profile;
        if (!$profile || empty($profile->name)) {
            return;
        }

        DB::table('radgroupreply')->where('groupname', $profile->name)->delete();

        $attributes = [];
        if ((int) ($profile->session_timeout ?? 0) > 0) {
            $attributes[] = ['attribute' => 'Session-Timeout', 'op' => ':=', 'value' => (string) $profile->session_timeout];
        }
        if ((int) ($profile->data_limit ?? 0) > 0) {
            $attributes[] = ['attribute' => 'Mikrotik-Total-Limit', 'op' => ':=', 'value' => (string) $profile->data_limit];
        }
        if (!empty($profile->rate_limit)) {
            $attributes[] = ['attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $profile->rate_limit];
        }

        foreach ($attributes as $attr) {
            DB::table('radgroupreply')->insert([
                'groupname' => $profile->name,
                'attribute' => $attr['attribute'],
                'op' => $attr['op'],
                'value' => $attr['value'],
            ]);
        }
    }

    private function syncNas(PppoeAccount $account): array
    {
        $router = $account->router;
        if (!$router || empty($router->api_user) || empty($router->api_password) || empty($router->api_address ?? $router->ip_address)) {
            return ['status' => 'skipped', 'message' => 'Router API credentials not configured'];
        }

        try {
            $client = new Client(new Config([
                'host' => $router->api_address ?: $router->ip_address,
                'user' => $router->api_user,
                'pass' => $router->api_password,
                'port' => (int) ($router->api_port ?: 8728),
                'timeout' => 6,
            ]));

            if ($account->profile?->name) {
                $profiles = $client->query((new Query('/ppp/profile/print'))->where('name', $account->profile->name))->read();
                if (empty($profiles)) {
                    $q = (new Query('/ppp/profile/add'))->equal('name', $account->profile->name);
                    if ($account->profile->rate_limit) {
                        $q->equal('rate-limit', $account->profile->rate_limit);
                    }
                    if ($account->profile->local_address) {
                        $q->equal('local-address', $account->profile->local_address);
                    }
                    if ($account->profile->remote_pool) {
                        $q->equal('remote-address', $account->profile->remote_pool);
                    }
                    if ($account->profile->dns_server) {
                        $q->equal('dns-server', $account->profile->dns_server);
                    }
                    $client->query($q)->read();
                }
            }

            $secrets = $client->query((new Query('/ppp/secret/print'))->where('name', $account->username))->read();
            if (!empty($secrets) && isset($secrets[0]['.id'])) {
                $q = (new Query('/ppp/secret/set'))
                    ->equal('.id', $secrets[0]['.id'])
                    ->equal('password', $account->password)
                    ->equal('service', 'pppoe')
                    ->equal('disabled', $account->is_active ? 'no' : 'yes');
                if ($account->profile?->name) {
                    $q->equal('profile', $account->profile->name);
                }
                if ($account->ip_address) {
                    $q->equal('remote-address', $account->ip_address);
                }
                $client->query($q)->read();
            } else {
                $q = (new Query('/ppp/secret/add'))
                    ->equal('name', $account->username)
                    ->equal('password', $account->password)
                    ->equal('service', 'pppoe')
                    ->equal('disabled', $account->is_active ? 'no' : 'yes');
                if ($account->profile?->name) {
                    $q->equal('profile', $account->profile->name);
                }
                if ($account->ip_address) {
                    $q->equal('remote-address', $account->ip_address);
                }
                $client->query($q)->read();
            }

            return ['status' => 'ok', 'message' => 'NAS sync success'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function deleteNasSecret(PppoeAccount $account): void
    {
        $router = $account->router;
        if (!$router || empty($router->api_user) || empty($router->api_password) || empty($router->api_address ?? $router->ip_address)) {
            return;
        }

        try {
            $client = new Client(new Config([
                'host' => $router->api_address ?: $router->ip_address,
                'user' => $router->api_user,
                'pass' => $router->api_password,
                'port' => (int) ($router->api_port ?: 8728),
                'timeout' => 6,
            ]));

            $secrets = $client->query((new Query('/ppp/secret/print'))->where('name', $account->username))->read();
            if (!empty($secrets) && isset($secrets[0]['.id'])) {
                $client->query((new Query('/ppp/secret/remove'))->equal('.id', $secrets[0]['.id']))->read();
            }
        } catch (\Throwable $e) {
            // silent in delete flow
        }
    }
}
