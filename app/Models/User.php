<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'country_code',
        'trial_used_at',
        'sms_enabled',
        'sms_sender',
        'telegram_bot_token',
        'telegram_chat_id',
        'password',
        'slug',
        'mikrotik_host',
        'mikrotik_user',
        'mikrotik_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'mikrotik_password' => 'encrypted', // Chiffrement automatique
        'trial_used_at' => 'datetime',
        'password' => 'hashed',
        'mikrotik_password' => 'encrypted',
    ];

    public function subscription()
    {
        // Un utilisateur n'a qu'un seul abonnement à la fois
        return $this->hasOne(\App\Models\Subscription::class)->latestOfMany();
    }
    

    public function profiles()
    {
        return $this->hasMany(\App\Models\Profile::class);
    }
    

    public function vouchers()
    {
        return $this->hasMany(\App\Models\Voucher::class);
    }
    

    public function template()
    {
        return $this->hasOne(Template::class);
    }
    

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function paymentGateways()
    {
        return $this->hasMany(UserPaymentGateway::class);
    }

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function vpnAccounts()
    {
        return $this->hasMany(VpnAccount::class);
    }
    

    public function salePageSetting()
    {
        return $this->hasOne(SalePageSetting::class);
    }

}