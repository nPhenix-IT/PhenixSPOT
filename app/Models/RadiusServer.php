<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiusServer extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'ip_address', 'radius_secret', 'description', 'is_active'];
    protected $casts = ['radius_secret' => 'encrypted', 'is_active' => 'boolean'];
}