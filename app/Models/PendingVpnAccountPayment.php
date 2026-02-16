<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingVpnAccountPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'amount',
        'duration',
        'payload',
        'payment_token',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
