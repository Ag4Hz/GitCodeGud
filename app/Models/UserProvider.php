<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserProvider extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_username',
        'provider_email',
        'token',
        'refresh_token',
    ];

    public function providers(): HasMany
    {
        return $this -> hasMany(User::class);
    }
}
