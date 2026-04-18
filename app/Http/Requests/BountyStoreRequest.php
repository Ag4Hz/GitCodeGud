<?php

namespace App\Http\Requests;

use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Organization;
use App\Rules\GitHubIssueUrl;
use App\Rules\GitHubRepositoryUrl;
use App\Rules\IssueBelongsToRepository;
use App\Rules\UniqueIssueForBounty;
use App\Rules\ValidJiraIssueUrl;
use App\Services\GitHubApiService;
use App\Services\GitRepoService;
use App\Services\JiraApiService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BountyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = $this->user();
        $provider = $this->input('provider', 'github');

        if ($this->filled('repository_full_name')) {
            $repositoryFullName = $this->input('repository_full_name');

            $repoUrl = match ($provider) {
                'gitlab' => "https://gitlab.com/{$repositoryFullName}",
                'bitbucket' => "https://bitbucket.org/{$repositoryFullName}",
                default => "https://github.com/{$repositoryFullName}",
            };

            return $user->can('createForRepository', [Bounty::class, $repoUrl, $provider]);
        }
        return false;
    }

    public function rules(): array
    {
        $provider = $this->input('provider', 'github');

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reward_xp' => ['required', 'integer', 'min:1', 'max:1000'],
            'organization_id' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'organization_id')
                    ->where('user_id', $this->user()->id),
            ],
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

        if ($this->filled('repository_full_name')) {
            $rules['repository_full_name'] = ['required', 'string'];

            if ($provider === 'bitbucket') {
                $rules['jira_issue_url'] = [
                    'required',
                    'url',
                    new ValidJiraIssueUrl(),
                ];
            } else {
                $rules['issue_number'] = ['required', 'integer', 'min:1'];
            }
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $provider = $this->input('provider', 'github');

            // Handle URL-based validation
            if ($this->filled('issue_url')) {
                $this->validateIssueStatus($validator);
            }

            // Handle form-based validation
            if ($this->filled('repository_full_name')) {
                if ($provider === 'bitbucket' && $this->filled('jira_issue_url')) {
                    $this->validateJiraIssue($validator);
                } elseif ($provider !== 'bitbucket' && $this->filled('issue_number')) {
                    $this->validateSelectedRepositoryAndIssue($validator);
                }
            }

            if ($this->filled('organization_id') && !$validator->errors()->has('organization_id')) {
                $this->validateOrganizationProvider($validator, $provider);
            }
        });
    }

    private function validateOrganizationProvider(Validator $validator, string $provider): void
    {
        $organizationId = $this->input('organization_id');
        $org = Organization::find($organizationId);

        if (!$org) {
            return;
        }

        $repoFullName = $this->input('repository_full_name');

        $orgRepo = match ($provider) {
            'github'    => $org->github_repo,
            'gitlab'    => $org->gitlab_repo,
            'bitbucket' => $org->bitbucket_repo,
            default     => null,
        };

        if (!$orgRepo) {
            $validator->errors()->add(
                'organization_id',
                "This organization has no {$provider} repository configured."
            );
            return;
        }

        if ($repoFullName && $orgRepo !== $repoFullName) {
            $validator->errors()->add(
                'organization_id',
                "This organization is linked to the repository \"{$orgRepo}\". Please select that repository, or choose a different organization."
            );
        }
    }

    private function validateIssueStatus(Validator $validator): void
    {
        $issueUrl = $this->input('issue_url');
        $user = $this->user();

        if (!$user || !$issueUrl) {
            return;
        }

        $githubProvider = $user->providers()->where('provider', 'github')->first();
        if (!$githubProvider) {
            $validator->errors()->add('issue_url', 'GitHub API access is required to validate issues.');
            return;
        }

        $githubApi = new GitHubApiService($githubProvider);

        if (!$githubApi->hasValidToken()) {
            $validator->errors()->add('issue_url', 'GitHub API access is required to validate issues.');
            return;
        }

        $issueInfo = GitHubApiService::parseGitIssueUrl($issueUrl);
        if (!$issueInfo) {
            return;
        }

        $isOpen = $githubApi->isIssueOpen($issueInfo['repo_full_name'], $issueInfo['issue_number']);

        if (!$isOpen) {
            $validator->errors()->add('issue_url', 'Only open GitHub issues can be used for bounties.');
        }
    }

    private function validateJiraIssue(Validator $validator): void
    {
        $jiraIssueUrl = $this->input('jira_issue_url');
        $user = $this->user();

        if (!$user || !$jiraIssueUrl) {
            return;
        }

        $jiraProvider = $user->providers()->where('provider', 'jira')->first();
        if (!$jiraProvider || !$jiraProvider->token) {
            $validator->errors()->add('jira_issue_url', 'A linked Jira account is required to validate Jira issues. Please connect your Jira account in Account Settings.');
            return;
        }

        $issueInfo = JiraApiService::parseIssueUrl($jiraIssueUrl);
        if (!$issueInfo) {
            return;
        }

        $existingIssue = Issue::where('url', $jiraIssueUrl)->first();
        if ($existingIssue) {
            $existingBounty = Bounty::withTrashed()->where('issue_id', $existingIssue->id)->first();
            if ($existingBounty) {
                $message = $existingBounty->trashed()
                    ? 'An archived bounty already exists for this Jira issue. Please restore the existing bounty instead of creating a new one.'
                    : 'A bounty already exists for this Jira issue. Each issue can only have one bounty.';
                $validator->errors()->add('jira_issue_url', $message);
                return;
            }
        }

        $jiraApi = JiraApiService::fromProvider($jiraProvider);
        $isOpen = $jiraApi->isIssueOpen($issueInfo['workspace'], $issueInfo['issue_key']);

        if (!$isOpen) {
            $validator->errors()->add('jira_issue_url', 'Only open Jira issues can be used for bounties.');
        }
    }

    private function validateSelectedRepositoryAndIssue(Validator $validator): void
    {
        $repositoryFullName = $this->input('repository_full_name');
        $issueNumber = $this->input('issue_number');
        $provider = $this->input('provider', 'github');

        if (!$repositoryFullName || !$issueNumber) {
            $validator->errors()->add('repository_full_name', 'Please select both a repository and an issue.');
            return;
        }

        $issueUrl = match ($provider) {
            'gitlab' => "https://gitlab.com/{$repositoryFullName}/-/issues/{$issueNumber}",
            default => "https://github.com/{$repositoryFullName}/issues/{$issueNumber}",
        };

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
        $repoService = new GitRepoService($user);

        if (!$repoService->hasProvider($provider)) {
            $validator->errors()->add('repository_full_name', ucfirst($provider) . ' API access is required to validate issues.');
            return;
        }

        $isOpen = $repoService->isIssueOpen($provider, $repositoryFullName, (int)$issueNumber);

        if (!$isOpen) {
            $validator->errors()->add('issue_number', 'Only open issues can be used for bounties.');
        }
    }

    public function getValidatedDataForStore(): array
    {
        $validated = $this->validated();
        $provider = $this->input('provider', 'github');

        if (isset($validated['repository_full_name'])) {
            $repoFullName = $validated['repository_full_name'];

            if ($provider === 'bitbucket') {
                $validated['repo_url'] = "https://bitbucket.org/{$repoFullName}";
                $validated['issue_url'] = $validated['jira_issue_url'];
                unset($validated['jira_issue_url']);
            } else {
                $issueNumber = $validated['issue_number'];
                $validated['repo_url'] = match ($provider) {
                    'gitlab' => "https://gitlab.com/{$repoFullName}",
                    default  => "https://github.com/{$repoFullName}",
                };
                $validated['issue_url'] = match ($provider) {
                    'gitlab' => "https://gitlab.com/{$repoFullName}/-/issues/{$issueNumber}",
                    default  => "https://github.com/{$repoFullName}/issues/{$issueNumber}",
                };
                unset($validated['issue_number']);
            }

            unset($validated['repository_full_name']);
        }

        return $validated;
    }
}
