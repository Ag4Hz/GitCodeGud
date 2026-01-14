<?php

namespace App\Http\Requests;

use App\Models\Bounty;
use App\Rules\PullRequestBelongsToRepository;
use App\Rules\UniqueSubmissionForBounty;
use App\Rules\ValidPullRequestUrl;
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

        if (!$user) {
            return;
        }

        $provider = $this->provider();

        if ($provider === 'unknown') {
            $validator->errors()->add('pr_url', 'Unsupported git provider.');
            return;
        }

        $bounty = Bounty::with('issue')->find($this->input('bounty_id'));

        if (!$bounty || !$bounty->issue) {
            return;
        }

        $issueProvider = $bounty->issue->provider;

        if ($provider !== $issueProvider) {
            $prType = $issueProvider === 'gitlab' ? 'merge request' : 'pull request';
            $validator->errors()->add(
                'pr_url',
                "This bounty is for a {$issueProvider} issue. Please submit a {$issueProvider} {$prType}."
            );
            return;
        }

        try {
            $service = GitProviderFactory::getProvider($provider, $user);

            $parsed = $service::parseGitPullRequestUrl($prUrl);

            if (!$parsed) {
                $validator->errors()->add('pr_url', 'Invalid PR/MR URL format');
                return;
            }

            $repoFullName = $parsed['full_name'];
            $prNumber = $parsed['pr_number'];

            $isOpen = $service->isPullRequestOpen($repoFullName, $prNumber);

            if (!$isOpen) {
                $validator->errors()->add('pr_url', 'The PR/MR must be open');
            }

        } catch (\Exception $e) {
            $validator->errors()->add('pr_url', 'Could not validate PR/MR: ' . $e->getMessage());
        }
    }
}
