<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpnAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vpn_server_id',
        'username',
        'password',
        'vpn_type',
        'protocol',
        'server_address',
        'local_ip_address',
        'local_ip',
        'remote_ip',
        'port_api',
        'port_winbox',
        'port_web',
        'port_custom',
        'remote_port_api',
        'remote_port_winbox',
        'remote_port_web',
        'remote_port_custom',
        'commentaire',
        'duration_months',
        'expires_at',
        'status',
        'is_supplementary',
        'forward_api',
        'forward_winbox',
        'forward_web',
        'is_active',
    ];

    protected $casts = [
        'forward_api' => 'boolean',
        'forward_winbox' => 'boolean',
        'forward_web' => 'boolean',
        'is_active' => 'boolean',
        'is_supplementary' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vpnServer()
    {
        return $this->belongsTo(VpnServer::class, 'vpn_server_id');
    }

    public function server()
    {
        return $this->vpnServer();
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && (!$this->expires_at || $this->expires_at->isFuture());
    }
}