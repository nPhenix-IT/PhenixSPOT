<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'name', 'price', 'limit_type', 'rate_limit', 
        'device_limit', 'session_timeout', 'data_limit', 'validity_period'
    ];

    public function user() { return $this->belongsTo(User::class); }
}
