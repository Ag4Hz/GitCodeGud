<?php

namespace App\Http\Requests;

use App\Models\Bounty;
use App\Rules\GitHubPullRequestUrl;
use App\Rules\PullRequestBelongsToRepository;
use App\Rules\UniqueSubmissionForBounty;
use App\Services\GitHubApiService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $bounty = Bounty::with('issue.repo')->find($this->input('bounty_id'));

        return [
            'bounty_id' => [
                'required',
                'integer',
                'exists:bounties,id',
                new UniqueSubmissionForBounty(),
            ],
            'pr_url' => [
                'required',
                'url',
                new GitHubPullRequestUrl(),
                $bounty ? new PullRequestBelongsToRepository($bounty->issue->repo->url) : '',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'bounty_id.required' => 'Bounty ID is required.',
            'bounty_id.exists' => 'Invalid bounty selected.',
            'pr_url.required' => 'Pull Request URL is required.',
            'pr_url.url' => 'Please enter a valid URL.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validatePullRequestAccess($validator);
        });
    }

    private function validatePullRequestAccess(Validator $validator): void
    {
        $prUrl = $this->input('pr_url');
        $user = $this->user();
        if (!$user || !$user->oauth_provider_token) {
            return;
        }

        $prInfo = GitHubApiService::parseGitHubPullRequestUrl($prUrl);
        if (!$prInfo) {
            return;
        }

        $githubApi = new GitHubApiService($user);
        $prData = $githubApi->getPullRequest($prInfo['repo_full_name'], $prInfo['pr_number']);
        if (empty($prData)) {
            $validator->errors()->add('pr_url', 'Could not access the Pull Request. Please ensure it exists and you have access to it.');
        }
    }
}
