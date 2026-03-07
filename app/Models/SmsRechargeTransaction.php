<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsRechargeTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sms_package_id',
        'transaction_id',
        'payment_method',
        'status',
        'amount_fcfa',
        'credits',
        'payment_token',
        'meta',
    ];

    protected $casts = [
        'amount_fcfa' => 'decimal:2',
        'credits' => 'integer',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(SmsPackage::class, 'sms_package_id');
    }
}
