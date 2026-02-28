<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpnServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'domain_name',
        'profile_name',
        'api_user',
        'api_password',
        'api_port',
        'local_ip_address',
        'ip_range',
        'gateway_ip',
        'ip_pool',
        'location',
        'account_limit',
        'max_accounts',
        'supported_protocols',
        'is_online',
        'is_active',
        
        'server_type',
      'wg_network',
      'wg_server_address',
      'wg_server_public_key',
      'wg_server_private_key',
      'wg_endpoint_address',
      'wg_endpoint_port',
      'wg_interface',
      'wg_dns',
      'wg_mtu',
      'wg_persistent_keepalive',
      'wg_client_ip_start',
    ];

    protected $casts = [
        'supported_protocols' => 'array',
        'api_password' => 'encrypted',
        'wg_server_private_key' => 'encrypted',
        'is_online' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function accounts()
    {
        return $this->hasMany(VpnAccount::class, 'vpn_server_id');
    }
}