<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'plan_id',
        'transaction_id',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];
}

