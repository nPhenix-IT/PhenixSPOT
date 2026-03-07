<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sms_package_id',
        'type',
        'status',
        'units',
        'amount_fcfa',
        'balance_after',
        'recipient',
        'sender_name',
        'message',
        'context',
        'meta',
    ];

    protected $casts = [
        'units' => 'integer',
        'amount_fcfa' => 'decimal:2',
        'balance_after' => 'decimal:2',
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
