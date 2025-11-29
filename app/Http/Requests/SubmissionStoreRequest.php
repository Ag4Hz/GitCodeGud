<?php

namespace App\Http\Requests;

use App\Models\Bounty;
use App\Rules\PullRequestBelongsToRepository;
use App\Rules\UniqueSubmissionForBounty;
use App\Rules\ValidPullRequestUrl;
use App\Services\GitHubApiService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Services\GitProviderFactory;

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
                new ValidPullRequestUrl(),
                $bounty ? new PullRequestBelongsToRepository($bounty->issue->repo->url) : '',
            ],
        ];
    }

    public function provider(): string
    {
        $url = $this->input('pr_url');

        if (str_contains($url, 'github')) {
            return 'github';
        }

        if (str_contains($url, 'gitlab')) {
            return 'gitlab';
        }

        if (str_contains($url, 'bitbucket')) {
            return 'bitbucket';
        }

        return 'unknown';
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

        $provider = $this->provider();

        try {
            $service = GitProviderFactory::getProvider($provider, $user);
        } catch (\InvalidArgumentException $e) {
            $validator->errors()->add('pr_url', "Unsupported provider: {$provider}");
            return;
        }

        $parsed = $service::parseGitPullRequestUrl($prUrl);

        if (!$parsed) {
            $validator->errors()->add('pr_url', 'Invalid PR/MR URL format for provider: ' . $provider);
            return;
        }

        $repoFullName = $parsed['full_name'];
        $prNumber = $parsed['pr_number'];

        $prData = $service->getPullRequest($repoFullName, $prNumber);

        if (empty($prData)) {
            $validator->errors()->add('pr_url', 'Could not access PR/MR from provider.');
            return;
        }

        if (!isset($prData['state']) || !in_array(strtolower($prData['state']), ['open', 'opened'])) {
            $validator->errors()->add('pr_url', 'PR/MR is not open.');
        }
    }
}
