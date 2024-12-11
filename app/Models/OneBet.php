<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OneBet extends Model
{
    use HasFactory;
    
    protected $fillable =['user_id', 'uuid', 'referrer_id', 'initial_value', 'second_value', 'winner', 'amount', 'odds', 'status', 'status', 'amount_earned'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cs) {
            $cs->uuid = Str::orderedUuid();
        });
    }
}
