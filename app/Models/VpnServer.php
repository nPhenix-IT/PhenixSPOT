<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpnServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'ip_address', 'api_user', 'api_password', 'api_port',
        'domain_name', 'local_ip_address', 'ip_range', 'account_limit', 'is_active',
    ];

    protected $casts = [
        'api_password' => 'encrypted',
        'is_active' => 'boolean',
    ];
}