<?php

namespace App\Rules;

use App\Services\BitbucketApiService;
use App\Services\GitHubApiService;
use App\Services\GitLabApiService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPullRequestUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isGitHub = GitHubApiService::isValidGitPullRequestUrl($value);
        $isGitLab = GitLabApiService::isValidGitPullRequestUrl($value);
        $isBitbucket = BitbucketApiService::isValidGitPullRequestUrl($value);

        if (!$isGitHub && !$isGitLab && !$isBitbucket) {
            $fail('Please enter a valid Pull Request or Merge Request URL from GitHub, GitLab or Bitbucket.');
        }
    }
}
