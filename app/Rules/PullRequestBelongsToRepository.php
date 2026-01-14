<?php

namespace App\Rules;

use App\Services\BitbucketApiService;
use App\Services\GitHubApiService;
use App\Services\GitLabApiService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PullRequestBelongsToRepository implements ValidationRule
{
    protected $expectedRepoUrl;

    public function __construct(string $expectedRepoUrl)
    {
        $this->expectedRepoUrl = $expectedRepoUrl;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $prInfo = GitHubApiService::parseGitPullRequestUrl($value)
               ?? GitLabApiService::parseGitPullRequestUrl($value)
               ?? BitbucketApiService::parseGitPullRequestUrl($value);

        if (!$prInfo) {
            $fail('Invalid Pull Request/Merge Request URL format.');
            return;
        }

        $expectedRepoInfo = GitHubApiService::parseGitUrl($this->expectedRepoUrl)
                         ?? GitLabApiService::parseGitUrl($this->expectedRepoUrl)
                         ?? BitbucketApiService::parseGitUrl($this->expectedRepoUrl);

        if (!$expectedRepoInfo) {
            $fail('Invalid repository URL format.');
            return;
        }

        $prRepoFullName = $prInfo['repo_full_name'] ?? null;
        $expectedRepoFullName = $expectedRepoInfo['full_name'] ?? null;

        if (!$prRepoFullName || !$expectedRepoFullName) {
            $fail('Unable to determine repository information.');
            return;
        }

        if (strcasecmp($prRepoFullName, $expectedRepoFullName) !== 0) {
            $fail("The Pull Request/Merge Request must belong to the repository: {$expectedRepoFullName}");
        }
    }
}
