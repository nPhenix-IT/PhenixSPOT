<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PppoeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'price',
        'limit_type',
        'rate_limit',
        'session_timeout',
        'data_limit',
        'validity_period',
        'local_address',
        'remote_pool',
        'pool_exclusions',
        'dns_server',
        'is_active',
        'comment',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'float',
        'session_timeout' => 'integer',
        'data_limit' => 'integer',
        'validity_period' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accounts()
    {
        return $this->hasMany(PppoeAccount::class);
    }
}
