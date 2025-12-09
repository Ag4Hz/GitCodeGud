<?php

namespace App\Models;

use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    protected $fillable = [
        'repo_id',
        'url',
        'description',
        'name',
        'provider',
        'git_id',
    ];


    public function repo(): BelongsTo
    {
        return $this->belongsTo(Repo::class);
    }

    public function bounties(): HasOne
    {
        return $this->hasOne(Bounty::class);
    }

    private static function normalizeProviderName(string $provider): string
    {
        return match (strtolower($provider)) {
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            'bitbucket' => 'Bitbucket',
            'gitea' => 'Gitea',
            'sourcehut', 'srht' => 'SourceHut',
            default => ucfirst($provider),
        };
    }


    public static function getAvailableProviders(): array
    {
        return Issue::query()
            ->selectRaw('provider, COUNT(*) as total')
            ->whereNotNull('provider')
            ->groupBy('provider')
            ->orderBy('provider')
            ->get()
            ->map(fn ($row) => [
                'name' => self::normalizeProviderName($row->provider),
                'value' => $row->provider,
                'count' => $row->total,
            ])
            ->toArray();
    }


}
