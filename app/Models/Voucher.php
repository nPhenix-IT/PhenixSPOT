<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_id',
        'activated_router_id',
        'activated_router_ip',
        'activation_nas_identifier',
        'code',
        'status',
        'used_at',
        'is_active',
        'source',
        'wallet_credited_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'wallet_credited_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function activatedRouter()
    {
        return $this->belongsTo(Router::class, 'activated_router_id');
    }
}