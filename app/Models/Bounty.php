<?php

namespace App\Models;

use Database\Factories\BountyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Bounty extends Model
{
    /** @use HasFactory<BountyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'issue_id',
        'organization_id',
        'status',
        'title',
        'description',
        'reward_xp',
        'views',
        'languages',
    ];

    protected function casts(): array
    {
        return [
            'reward_xp' => 'integer',
            'views' => 'integer',
            'status' => 'string',
            'languages' => 'array',
            'deleted_at' => 'datetime',
            'organization_id' => 'integer'
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

    public static function getAvailableLanguages(): array
    {
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
    }
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->whereNull('organization_id');

            if ($user !== null) {
                $q->orWhereHas('organization.members', function (Builder $inner) use ($user) {
                    $inner->where('users.id', $user->id);
                });
            }
        });
    }
}
