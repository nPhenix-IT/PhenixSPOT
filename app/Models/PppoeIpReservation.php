<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PppoeIpReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pppoe_profile_id',
        'pppoe_account_id',
        'ip_address',
        'status',
        'note',
        'acted_by',
    ];

    public function profile()
    {
        return $this->belongsTo(PppoeProfile::class, 'pppoe_profile_id');
    }

    public function account()
    {
        return $this->belongsTo(PppoeAccount::class, 'pppoe_account_id');
    }
}
