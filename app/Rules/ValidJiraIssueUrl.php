<?php

namespace App\Rules;

use App\Services\JiraApiService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidJiraIssueUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!JiraApiService::isValidIssueUrl($value)) {
            $fail('The Jira issue URL must be in the format: https://{workspace}.atlassian.net/browse/PROJ-123');
        }
    }
}
