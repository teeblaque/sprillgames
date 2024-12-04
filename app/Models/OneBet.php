<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OneBet extends Model
{
    use HasFactory;
    
    protected $fillable =['user_id', 'referrer_id', 'initial_value', 'second_value', 'winner', 'amount', 'odds', 'status', 'status', 'amount_earned'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
