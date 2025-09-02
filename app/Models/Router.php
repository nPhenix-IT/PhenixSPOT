<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'ip_address', 'radius_secret', 'brand', 'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}