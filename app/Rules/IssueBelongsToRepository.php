<?php

namespace App\Rules;

use App\Services\GitHubApiService;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class IssueBelongsToRepository implements ValidationRule, DataAwareRule
{
    protected $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $repoUrl = $this->data['repo_url'] ?? null;

        if (!$repoUrl) {
            $fail('Repository URL is required to validate issue.');
            return;
        }

        $repoInfo = GitHubApiService::parseGitHubUrl($repoUrl);
        $issueInfo = GitHubApiService::parseGitHubIssueUrl($value);

        if (!$repoInfo || !$issueInfo) {
            return;
        }

        if ($repoInfo['full_name'] !== $issueInfo['repo_full_name']) {
            $fail("The issue must belong to the repository: {$repoInfo['full_name']}");
        }
    }
}
