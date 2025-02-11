<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiruWebhook extends Model
{
    protected $fillable = ['event', 'result'];
}
