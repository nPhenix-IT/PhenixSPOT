<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingTransaction extends Model {
    use HasFactory;
    // protected $fillable = ['transaction_id', 'user_id', 'profile_id', 'payment_token', 'status'];
    protected $fillable = [
        'transaction_id',
        'user_id',
        'profile_id',
        'customer_name',
        'customer_number',
        'commission_payer',
        'commission_amount',
        'total_price',
        'payment_token',
        'status',
    ];
}
