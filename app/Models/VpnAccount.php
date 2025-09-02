<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpnAccount extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'vpn_server_id', 'username', 'password', 'vpn_type',
        'server_address', 'local_ip_address',
        'forward_api', 'forward_winbox', 'forward_web', 'is_active'
    ];
    protected $casts = [
        'forward_api' => 'boolean',
        'forward_winbox' => 'boolean',
        'forward_web' => 'boolean',
        'is_active' => 'boolean',
    ];
    public function user() { return $this->belongsTo(User::class); }
    public function vpnServer() { return $this->belongsTo(VpnServer::class); }
}