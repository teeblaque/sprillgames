<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCard extends Model
{
    use HasFactory;

    protected $fillable = ['authorization_code', 'user_id', 'brand', 'bin', 'last4', 'exp_month', 'exp_year', 'bank'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
