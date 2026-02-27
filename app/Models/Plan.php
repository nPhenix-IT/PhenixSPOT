<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model {
    
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_annually',
        'features',
        'is_active',
        'trial_enabled',
        'trial_days',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'trial_enabled' => 'boolean',
        'trial_days' => 'integer',
    ];

}
