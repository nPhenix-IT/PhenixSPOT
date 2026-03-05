<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PppoeAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pppoe_account_id',
        'acted_by',
        'action',
        'status',
        'message',
        'context',
        'provisioned_at',
    ];

    protected $casts = [
        'context' => 'array',
        'provisioned_at' => 'datetime',
    ];
}
