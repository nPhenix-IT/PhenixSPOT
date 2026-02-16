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
    ];

    protected $casts = [
        'supported_protocols' => 'array',
        'api_password' => 'encrypted',
        'is_online' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function accounts()
    {
        return $this->hasMany(VpnAccount::class, 'vpn_server_id');
    }
}