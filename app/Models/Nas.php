<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nas extends Model
{
    use HasFactory;
    protected $table = 'nas';
    public $timestamps = false;
    protected $fillable = [
        'nasname', 'shortname', 'type', 'ports', 'secret', 'server', 'community', 'description',
    ];
}