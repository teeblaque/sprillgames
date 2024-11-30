<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = ['user_id', 'reference', 'amount', 'status', 'provider'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
