<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_cost_fcfa',
        'default_sender_name',
    ];

    protected $casts = [
        'unit_cost_fcfa' => 'decimal:2',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'unit_cost_fcfa' => 25,
            'default_sender_name' => 'PhenixSPOT',
        ]);
    }
}
