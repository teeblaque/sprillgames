<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Subscription extends Model
{
    protected $fillable = ['user_id', 'status', 'amount'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cs) {
            $cs->uuid = Str::orderedUuid();
            $cs->referrer_code = generateRandomAlphaNumeric(8);
        });
    }
}