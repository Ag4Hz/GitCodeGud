<?php

namespace App\Http\Requests;

use App\Models\Bounty;
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
        $repoUrl = $this->input('repo_url');

        return $user->can('createForRepository', [Bounty::class, $repoUrl]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reward_xp' => ['required', 'integer', 'min:1', 'max:1000'],
            'repo_url' => [
                'required',
                'url',
                new GitHubRepositoryUrl(),
            ],
            'issue_url' => [
                'required',
                'url',
                new GitHubIssueUrl(),
                new IssueBelongsToRepository(),
                new UniqueIssueForBounty(),
            ],
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateIssueStatus($validator);
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
}
