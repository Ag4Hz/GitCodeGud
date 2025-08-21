<?php

namespace App\Http\Requests;

use App\Models\Bounty;
use App\Models\Issue;
use App\Services\GitHubApiService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BountyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reward_xp' => ['required', 'integer', 'min:1', 'max:1000'],
            'repo_url' => ['required', 'url'],
            'issue_url' => ['required', 'url'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateGitHubUrls($validator);
        });
    }

    private function validateGitHubUrls(Validator $validator): void
    {
        $repoUrl = $this->input('repo_url');
        $issueUrl = $this->input('issue_url');

        // 1. Validate repo URL format using GitHubApiService
        if (!GitHubApiService::isValidGitHubUrl($repoUrl)) {
            $validator->errors()->add('repo_url', 'Please enter a valid GitHub repository URL.');
            return;
        }

        // 2. Validate issue URL format using GitHubApiService
        if (!GitHubApiService::isValidGitHubIssueUrl($issueUrl)) {
            $validator->errors()->add('issue_url', 'Please enter a valid GitHub issue URL.');
            return;
        }

        // 3. Parse URLs using GitHubApiService
        $repoInfo = GitHubApiService::parseGitHubUrl($repoUrl);
        $issueInfo = GitHubApiService::parseGitHubIssueUrl($issueUrl);

        // 4. Check if issue belongs to the same repository
        if ($repoInfo['full_name'] !== $issueInfo['repo_full_name']) {
            $validator->errors()->add('issue_url', 'The issue must belong to the specified repository.');
            return;
        }

        // 5. Check if bounty already exists for this issue URL
        $existingIssue = Issue::where('url', $issueUrl)->first();
        if ($existingIssue) {
            $existingBounty = Bounty::where('issue_id', $existingIssue->id)->exists();
            if ($existingBounty) {
                $validator->errors()->add('issue_url', 'A bounty already exists for this GitHub issue. Each issue can only have one bounty.');
                return;
            }
        }
        $user = $this->user();
        $githubApi = new GitHubApiService($user);

        if (!$githubApi->hasValidToken()) {
            $validator->errors()->add('issue_url', 'GitHub API access is required to validate issues.');
            return;
        }

        try {
            $isOpen = $githubApi->isIssueOpen($issueInfo['repo_full_name'], $issueInfo['issue_number']);

            if (!$isOpen) {
                $validator->errors()->add('issue_url', 'Only open GitHub issues can be used for bounties.');
            }
        } catch (\Exception $e) {
            $validator->errors()->add('issue_url', 'Could not verify issue status. Please ensure the issue exists and you have access to it.');
        }
    }
}
