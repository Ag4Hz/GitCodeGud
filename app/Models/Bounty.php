<?php

namespace App\Models;

use Database\Factories\BountyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bounty extends Model
{
    /** @use HasFactory<BountyFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'description',
        'reward_xp'
    ];

    protected $casts = [
        'reward_xp' => 'integer',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

}
