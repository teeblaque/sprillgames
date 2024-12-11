<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Bet extends Model
{
    use HasFactory;
    
    protected $fillable =['user_id', 'uuid', 'values', 'amount_earned', 'amount', 'status', 'system_value'];

    protected $casts = [
        'values' => 'array',
        'system_value' => 'array'
    ];

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