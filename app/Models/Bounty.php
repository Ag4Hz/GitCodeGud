<?php

namespace App\Models;

use Database\Factories\BountyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bounty extends Model
{
    /** @use HasFactory<BountyFactory> */
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'status',
        'title',
        'description',
        'reward_xp',
        'languages',
    ];
    protected function casts(): array
    {
        return [
            'reward_xp' => 'integer',
            'status' => 'string',
            'languages' => 'array',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function issue(): belongsTo
    {
        return $this->belongsTo(Issue::class);
    }


}
