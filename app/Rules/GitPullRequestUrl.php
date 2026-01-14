<?php

namespace App\Rules;

use App\Services\BitbucketApiService;
use App\Services\GitHubApiService;
use App\Services\GitLabApiService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GitPullRequestUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isValid = GitHubApiService::isValidGitPullRequestUrl($value) ||
                   GitLabApiService::isValidGitPullRequestUrl($value) ||
                   BitbucketApiService::isValidGitPullRequestUrl($value);

        if (!$isValid) {
            $fail('Please enter a valid Pull Request/Merge Request URL from GitHub, GitLab, or Bitbucket.');
        }
    }

    /**
     * Detect the provider from the PR/MR URL
     */
    public static function detectProvider(string $url): ?string
    {
        if (GitHubApiService::isValidGitPullRequestUrl($url)) {
            return 'github';
        }

        if (GitLabApiService::isValidGitPullRequestUrl($url)) {
            return 'gitlab';
        }

        if (BitbucketApiService::isValidGitPullRequestUrl($url)) {
            return 'bitbucket';
        }

        return null;
    }

    /**
     * Parse PR/MR URL using the appropriate service
     */
    public static function parsePullRequestUrl(string $url): ?array
    {
        $provider = self::detectProvider($url);

        if (!$provider) {
            return null;
        }

        return match ($provider) {
            'github' => GitHubApiService::parseGitPullRequestUrl($url),
            'gitlab' => GitLabApiService::parseGitPullRequestUrl($url),
            'bitbucket' => BitbucketApiService::parseGitPullRequestUrl($url),
            default => null,
        };
    }
}

