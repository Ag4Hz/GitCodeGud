<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserProvider extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_username',
        'provider_email',
        'nickname',
        'avatar',
        'token',
        'refresh_token',
    ];

    public function providers(): HasOne
    {
        return $this -> hasOne(User::class);
    }
}
