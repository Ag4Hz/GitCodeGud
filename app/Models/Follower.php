<?php

namespace App\Models;

use Database\Factories\FollowerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Follower extends Model
{
    /** @use HasFactory<FollowerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'followed_id'
    ];


    public function follower(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_id');
    }

    public function followed(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followed_id');
    }
}
