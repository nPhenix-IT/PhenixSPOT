<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'credits',
        'price_fcfa',
        'is_active',
    ];

    protected $casts = [
        'credits' => 'integer',
        'price_fcfa' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
