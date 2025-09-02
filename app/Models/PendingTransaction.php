<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingTransaction extends Model {
    use HasFactory;
    protected $fillable = ['transaction_id', 'user_id', 'profile_id', 'payment_token', 'status'];
}
