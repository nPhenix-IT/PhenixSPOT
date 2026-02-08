<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Router;
use App\Models\Profile;

class PendingTransaction extends Model {
    use HasFactory;
<<<<<<< HEAD
    protected $fillable = [
        'transaction_id',
        'user_id',
        'profile_id',
        'customer_name',
        'customer_number',
=======
    // protected $fillable = ['transaction_id', 'user_id', 'profile_id', 'payment_token', 'status'];
    protected $fillable = [
        'transaction_id',
        'user_id',
        'router_id',
        'profile_id',
        'customer_name',
        'customer_number',
        'login_url',
        'voucher_code',
>>>>>>> master
        'commission_payer',
        'commission_amount',
        'total_price',
        'payment_token',
        'status',
    ];
<<<<<<< HEAD
=======
    
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }
>>>>>>> master
}
