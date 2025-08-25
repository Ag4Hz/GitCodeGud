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
    public static function getAvailableLanguages(): array
    {
        try {
            $languages = Bounty::active()
                ->where('status', 'open')
                ->whereNotNull('languages')
                ->get()
                ->pluck('languages')
                ->flatten()
                ->unique()
                ->filter()
                ->sort()
                ->values()
                ->toArray();

            return $languages;

        } catch (\Exception $e) {
            return [];
        }
    }
}
