<?php

namespace App\Rules;

use App\Services\GitHubApiService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GitHubPullRequestUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!GitHubApiService::isValidGitPullRequestUrl($value)) {
            $fail('Please enter a valid GitHub Pull Request URL (e.g., https://github.com/user/repo/pull/123).');
        }
    }
}
