<?php

namespace App\Rules;

use App\Services\GitHubApiService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GitHubRepositoryUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!GitHubApiService::isValidGitHubUrl($value)) {
            $fail('Please enter a valid GitHub repository URL (e.g., https://github.com/user/repo).');
        }
    }
}
