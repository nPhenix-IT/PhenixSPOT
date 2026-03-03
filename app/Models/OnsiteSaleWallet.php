<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnsiteSaleWallet extends Model
{
    protected $table = 'onsite_sale_wallet';

    protected $fillable = [
        'user_id',
        'voucher_id',
        'router_id',
        'type',
        'amount',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }
}