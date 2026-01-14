<?php

namespace App\Http\Requests;

use App\Models\Bounty;

use App\Rules\GitPullRequestUrl;
use App\Rules\PullRequestBelongsToRepository;
use App\Rules\UniqueSubmissionForBounty;
use App\Services\GitProviderFactory;
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

                new GitPullRequestUrl(),
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

        if (!$user || !$prUrl) {
            return;
        }

        $providerKey = GitPullRequestUrl::detectProvider($prUrl);
        if (!$providerKey) {
            return;
        }

        $prInfo = GitPullRequestUrl::parsePullRequestUrl($prUrl);
        if (!$prInfo) {
            return;
        }

        $userProvider = $user->providers()->where('provider', $providerKey)->first();
        if (!$userProvider || !$userProvider->token) {
            return;
        }

        try {
            $providerService = GitProviderFactory::getProvider($providerKey, $userProvider);

            $prData = $providerService->getPullRequest($prInfo['repo_full_name'], $prInfo['pr_number']);

            if (empty($prData)) {
                $validator->errors()->add('pr_url', 'Could not access the Pull Request/Merge Request. Please ensure it exists and you have access to it.');
            }
        } catch (\Exception $e) {
            $validator->errors()->add('pr_url', 'Could not verify access to the Pull Request/Merge Request.');
        }
    }
}
