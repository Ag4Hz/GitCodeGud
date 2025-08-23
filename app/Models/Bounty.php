<?php

namespace App\Models;

use Database\Factories\BountyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bounty extends Model
{
    /** @use HasFactory<BountyFactory> */
    use HasFactory, SoftDeletes;

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
            'deleted_at' => 'datetime',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeDeleted($query)
    {
        return $query->onlyTrashed();
    }

    /**
     * Check if bounty is soft deleted
     */
    public function isDeleted(): bool
    {
        return !is_null($this->deleted_at);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
