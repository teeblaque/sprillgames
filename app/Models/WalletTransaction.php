<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'wallet_id',
        'trx_reference',
        'trx_type',
        'trx_status',
        'trx_source',
        'amount',
        'balance_before',
        'balance_after',
        'ip_address',
        'domain',
        'narration',
        'is_active',
        'gateway_response',
        'payment_channel',
        'user_id'
    ];

     /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'balance_before' => 'float',
        'balance_after' => 'float',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
