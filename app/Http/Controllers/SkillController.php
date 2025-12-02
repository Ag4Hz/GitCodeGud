<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BitbucketApiService;
use App\Services\GitHubApiService;
use App\Services\GitLabApiService;
use App\Services\SkillSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillController extends Controller
{
    public function __construct(
        private SkillSyncService $skillSync
    )
    {
    }

    public function syncGitHub(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $github = $user?->providers()
            ->where('provider', 'github')
            ->first();

        if (!$user || !$github || !$github->token) {
            return redirect()
                ->back()
                ->with('error', 'GitHub token not available. Please reconnect your GitHub account.');
        }

        $api = new GitHubApiService($github);

        $success = $this->skillSync->syncUserSkillsFromProvider($user, $api);

        if (!$success) {
            return redirect()
                ->back()
                ->with('error', 'Failed to sync skills from GitHub. Please try again.');
        }

        return redirect()
            ->route('profile.show')
            ->with('success', 'Skills successfully synced from GitHub!');
    }

    public function syncGitLab(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $gitlab = $user?->providers()
            ->where('provider', 'gitlab')
            ->first();

        if (!$user || !$gitlab || !$gitlab->token) {
            return redirect()
                ->back()
                ->with('error', 'GitLab token not available. Please reconnect your GitLab account.');
        }

        $api = new GitLabApiService($gitlab);

        $success = $this->skillSync->syncUserSkillsFromProvider($user, $api);

        if (!$success) {
            return redirect()
                ->back()
                ->with('error', 'Failed to sync skills from GitLab. Please try again.');
        }

        return redirect()
            ->route('profile.show')
            ->with('success', 'Skills successfully synced from GitLab!');
    }

    public function syncBitbucket(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $bitbucket = $user?->providers()
            ->where('provider', 'bitbucket')
            ->first();

        if (!$user || !$bitbucket || !$bitbucket->token) {
            return redirect()
                ->back()
                ->with('error', 'Bitbucket token not available. Please reconnect your Bitbucket account.');
        }

        $api = new BitbucketApiService($bitbucket);

        $success = $this->skillSync->syncUserSkillsFromProvider($user, $api);

        if (!$success) {
            return redirect()
                ->back()
                ->with('error', 'Failed to sync skills from Bitbucket. Please try again.');
        }

        return redirect()
            ->route('profile.show')
            ->with('success', 'Skills successfully synced from Bitbucket!');
    }

}
