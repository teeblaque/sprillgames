<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBank extends Model
{
    use HasFactory;

    public $fillable = ['user_id', 'bank_code', 'account_name', 'account_number', 'bank_name','provider', 'transfer_recipient'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
