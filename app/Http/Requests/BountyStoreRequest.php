<?php

namespace App\Http\Requests;

use App\Models\Bounty;
use App\Models\Issue;
use App\Rules\GitHubIssueUrl;
use App\Rules\GitHubRepositoryUrl;
use App\Rules\IssueBelongsToRepository;
use App\Rules\UniqueIssueForBounty;
use App\Services\GitHubApiService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BountyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = $this->user();
        $githubApi = new GitHubApiService($user);
        if (!$githubApi->hasValidToken()) {
            return false;
        }

        if ($this->filled('repository_full_name')) {
            $repositoryFullName = $this->input('repository_full_name');
            $repoUrl = 'https://github.com/' . $repositoryFullName;

            return $user->can('createForRepository', [Bounty::class, $repoUrl]);
        }
        return false;
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reward_xp' => ['required', 'integer', 'min:1', 'max:1000'],
        ];

        // Support both URL-based and form-based input methods
        if ($this->filled('repo_url') && $this->filled('issue_url')) {
            $rules['repo_url'] = [
                'required',
                'url',
                new GitHubRepositoryUrl(),
            ];
            $rules['issue_url'] = [
                'required',
                'url',
                new GitHubIssueUrl(),
                new IssueBelongsToRepository(),
                new UniqueIssueForBounty(),
            ];
        }

        if ($this->filled('repository_full_name') && $this->filled('issue_number')) {
            $rules['repository_full_name'] = ['required', 'string'];
            $rules['issue_number'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Handle URL-based validation
            if ($this->filled('issue_url')) {
                $this->validateIssueStatus($validator);
            }

            // Handle form-based validation
            if ($this->filled('repository_full_name') && $this->filled('issue_number')) {
                $this->validateSelectedRepositoryAndIssue($validator);
            }
        });
    }

    private function validateIssueStatus(Validator $validator): void
    {
        $issueUrl = $this->input('issue_url');
        $user = $this->user();

        if (!$user || !$issueUrl) {
            return;
        }

        $githubApi = new GitHubApiService($user);

        if (!$githubApi->hasValidToken()) {
            $validator->errors()->add('issue_url', 'GitHub API access is required to validate issues.');
            return;
        }

        $issueInfo = GitHubApiService::parseGitHubIssueUrl($issueUrl);
        if (!$issueInfo) {
            return;
        }
        
        $isOpen = $githubApi->isIssueOpen($issueInfo['repo_full_name'], $issueInfo['issue_number']);

        if (!$isOpen) {
            $validator->errors()->add('issue_url', 'Only open GitHub issues can be used for bounties.');
        }
    }

    private function validateSelectedRepositoryAndIssue(Validator $validator): void
    {
        $repositoryFullName = $this->input('repository_full_name');
        $issueNumber = $this->input('issue_number');

        if (!$repositoryFullName || !$issueNumber) {
            $validator->errors()->add('repository_full_name', 'Please select both a repository and an issue.');
            return;
        }

        $issueUrl = "https://github.com/{$repositoryFullName}/issues/{$issueNumber}";

        $existingIssue = Issue::where('url', $issueUrl)->first();
        if ($existingIssue) {
            $existingBounty = Bounty::withTrashed()->where('issue_id', $existingIssue->id)->first();
            if ($existingBounty) {
                if ($existingBounty->trashed()) {
                    $validator->errors()->add('issue_number', 'An archived bounty already exists for this issue. Please restore the existing bounty instead of creating a new one.');
                } else {
                    $validator->errors()->add('issue_number', 'A bounty already exists for this issue. Each issue can only have one bounty.');
                }
                return;
            }
        }

        $user = $this->user();
        $githubApi = new GitHubApiService($user);

        if (!$githubApi->hasValidToken()) {
            $validator->errors()->add('repository_full_name', 'GitHub API access is required to validate issues.');
            return;
        }

        $isOpen = $githubApi->isIssueOpen($repositoryFullName, $issueNumber);

        if (!$isOpen) {
            $validator->errors()->add('issue_number', 'Only open GitHub issues can be used for bounties.');
        }
    }

    public function getValidatedDataForStore(): array
    {
        $validated = $this->validated();

        if (isset($validated['repository_full_name']) && isset($validated['issue_number'])) {
            $validated['repo_url'] = "https://github.com/{$validated['repository_full_name']}";
            $validated['issue_url'] = "https://github.com/{$validated['repository_full_name']}/issues/{$validated['issue_number']}";

            unset($validated['repository_full_name'], $validated['issue_number']);
        }

        return $validated;
    }
}