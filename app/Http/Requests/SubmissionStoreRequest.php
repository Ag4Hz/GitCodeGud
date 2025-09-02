<?php

namespace App\Http\Requests;

use App\Models\Bounty;
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
        return [
            'bounty_id' => ['required', 'integer', 'exists:bounties,id'],
            'pr_url' => ['required', 'url'],
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
            $this->validatePullRequestUrl($validator);
        });
    }

    private function validatePullRequestUrl(Validator $validator): void
    {
        $prUrl = $this->input('pr_url');
        $bountyId = $this->input('bounty_id');

        // 1. Validate PR URL format using GitHubApiService
        if (!GitHubApiService::isValidGitHubPullRequestUrl($prUrl)) {
            $validator->errors()->add('pr_url', 'Please enter a valid GitHub Pull Request URL (e.g., https://github.com/user/repo/pull/123).');
            return;
        }

        // 2. Get bounty and validate PR belongs to the same repo
        $bounty = Bounty::with('issue.repo')->find($bountyId);
        if (!$bounty) {
            $validator->errors()->add('bounty_id', 'Invalid bounty.');
            return;
        }

        // 3. Parse PR URL to get repo information
        $prInfo = GitHubApiService::parseGitHubPullRequestUrl($prUrl);
        $expectedRepoFullName = GitHubApiService::parseGitHubUrl($bounty->issue->repo->url)['full_name'];

        // 4. Validate PR references the correct repository
        if ($prInfo['repo_full_name'] !== $expectedRepoFullName) {
            $validator->errors()->add('pr_url', "The Pull Request must belong to the bounty repository: {$expectedRepoFullName}");
            return;
        }

        // 5. Check for duplicate submissions for this bounty by the same user
        $existingSubmission = $bounty->submissions()
            ->where('user_id', auth()->id())
            ->exists();

        if ($existingSubmission) {
            $validator->errors()->add('bounty_id', 'You have already submitted a solution for this bounty.');
            return;
        }

        // 6. Validate PR exists and is accessible
        $user = $this->user();
        if ($user && $user->oauth_provider_token) {
            $githubApi = new GitHubApiService($user);

            try {
                $prData = $githubApi->getPullRequest($prInfo['repo_full_name'], $prInfo['pr_number']);
                if (empty($prData)) {
                    $validator->errors()->add('pr_url', 'Could not access the Pull Request. Please ensure it exists and you have access to it.');
                }
            } catch (\Exception $e) {
                $validator->errors()->add('pr_url', 'Could not verify Pull Request. Please ensure the URL is correct and accessible.');
            }
        }
    }
}
