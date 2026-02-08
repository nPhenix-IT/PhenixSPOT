<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalePageSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'primary_color',
        'login_primary_color',
        'login_ticker_text',
        'login_dns',
        'login_contact_phone_1',
        'login_contact_label_1',
        'login_contact_phone_2',
        'login_contact_label_2',
        'commission_payer',
        'commission_percent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
