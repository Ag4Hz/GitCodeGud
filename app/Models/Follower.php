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
        'follower_id',
        'followed_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    public function follower(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follower_id');
    }

    public function followed(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followed_id');
    }
}
