<?php

namespace App\Models;

use App\Services\GitHubApiService;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'provider',
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

    protected function prNumber(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if (!$this->pr_url) {
                    return null;
                }

                $prInfo = GitHubApiService::parseGitPullRequestUrl($this->pr_url);
                return $prInfo['pr_number'] ?? null;
            }
        );
    }
    protected function repoFullName(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (!$this->pr_url) {
                    return null;
                }

                $prInfo = GitHubApiService::parseGitPullRequestUrl($this->pr_url);
                return $prInfo['repo_full_name'] ?? null;
            }
        );
    }
    protected function repoOwner(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (!$this->pr_url) {
                    return null;
                }

                $prInfo = GitHubApiService::parseGitPullRequestUrl($this->pr_url);
                return $prInfo['owner'] ?? null;
            }
        );
    }
    protected function repoName(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (!$this->pr_url) {
                    return null;
                }

                $prInfo = GitHubApiService::parseGitPullRequestUrl($this->pr_url);
                return $prInfo['name'] ?? null;
            }
        );
    }
    public function hasValidPrUrl(): bool
    {
        return $this->pr_url && GitHubApiService::isValidGitPullRequestUrl($this->pr_url);
    }
    public function canBeResubmitted(): bool
    {
        return $this->status === 'rejected';
    }
}
