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
        'commission_payer',
        'commission_percent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
