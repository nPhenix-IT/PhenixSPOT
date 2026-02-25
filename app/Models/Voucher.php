<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;
    // protected $fillable = ['user_id', 'profile_id', 'code', 'status', 'used_at', 'is_active'];
    protected $fillable = [
        'user_id',
        'profile_id',
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

    public function user() { return $this->belongsTo(User::class); }
    public function profile() { return $this->belongsTo(Profile::class); }
}