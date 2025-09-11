<?php

namespace App\Rules;

use App\Services\GitHubApiService;
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
        $prInfo = GitHubApiService::parseGitHubPullRequestUrl($value);
        if (!$prInfo) {
            $fail('Invalid Pull Request URL format.');
            return;
        }

        $expectedRepoInfo = GitHubApiService::parseGitHubUrl($this->expectedRepoUrl);
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

        if ($prRepoFullName !== $expectedRepoFullName) {
            $fail("The Pull Request must belong to the repository: {$expectedRepoFullName}");
        }
    }
}
