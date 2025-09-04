<?php

namespace App\Rules;

use App\Services\GitHubApiService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GitHubIssueUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!GitHubApiService::isValidGitHubIssueUrl($value)) {
            $fail('Please enter a valid GitHub issue URL (e.g., https://github.com/user/repo/issues/123).');
        }
    }
}
