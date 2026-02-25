<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WireguardClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'vpn_server_id',
        'router_id',
        'client_ip',
        'client_public_key',
        'client_private_key',
        'preshared_key',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'client_private_key' => 'encrypted',
        'preshared_key' => 'encrypted',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function wireguardServer()
    {
        return $this->belongsTo(VpnServer::class, 'vpn_server_id');
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}
