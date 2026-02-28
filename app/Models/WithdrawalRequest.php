<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model {
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'amount',
        'country_code',
        'withdraw_mode',
        'phone_number',
        'payment_details',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'payment_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
