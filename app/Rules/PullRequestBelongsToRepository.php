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
        $expectedRepoFullName = GitHubApiService::parseGitHubUrl($this->expectedRepoUrl)['full_name'];

        if ($prInfo['repo_full_name'] !== $expectedRepoFullName) {
            $fail("The Pull Request must belong to the repository: {$expectedRepoFullName}");
        }
    }
}
