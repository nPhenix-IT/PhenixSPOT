<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PppoeAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'router_id',
        'pppoe_profile_id',
        'username',
        'password',
        'service_name',
        'ip_address',
        'is_active',
        'status',
        'last_seen_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function profile()
    {
        return $this->belongsTo(PppoeProfile::class, 'pppoe_profile_id');
    }
}
