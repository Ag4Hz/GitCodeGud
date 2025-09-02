<?php

namespace App\Models;

use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;


    protected $fillable = [
        'bounty_id',
        'user_id',
        'status',
        'pr_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function bounty(): BelongsTo
    {
        return $this->belongsTo(Bounty::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function getPrNumberAttribute(): ?int
    {
        if (!$this->pr_url) {
            return null;
        }

        $prInfo = \App\Services\GitHubApiService::parseGitHubPullRequestUrl($this->pr_url);
        return $prInfo['pr_number'] ?? null;
    }

    /**
     * Get the repository full name from the PR URL
     */
    public function getRepoFullNameAttribute(): ?string
    {
        if (!$this->pr_url) {
            return null;
        }

        $prInfo = \App\Services\GitHubApiService::parseGitHubPullRequestUrl($this->pr_url);
        return $prInfo['repo_full_name'] ?? null;
    }

    /**
     * Get the repository owner from the PR URL
     */
    public function getRepoOwnerAttribute(): ?string
    {
        if (!$this->pr_url) {
            return null;
        }

        $prInfo = \App\Services\GitHubApiService::parseGitHubPullRequestUrl($this->pr_url);
        return $prInfo['owner'] ?? null;
    }

    /**
     * Get the repository name from the PR URL
     */
    public function getRepoNameAttribute(): ?string
    {
        if (!$this->pr_url) {
            return null;
        }

        $prInfo = \App\Services\GitHubApiService::parseGitHubPullRequestUrl($this->pr_url);
        return $prInfo['name'] ?? null;
    }

    /**
     * Check if the PR URL is valid
     */
    public function hasValidPrUrl(): bool
    {
        return $this->pr_url && \App\Services\GitHubApiService::isValidGitHubPullRequestUrl($this->pr_url);
    }
}
