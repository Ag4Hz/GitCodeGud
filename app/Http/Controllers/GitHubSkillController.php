<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GitHubSkillSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GitHubSkillController extends Controller
{
    public function __construct(
        private GitHubSkillSyncService $gitHubSkillSync
    ) {}

    public function sync(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $github = $user->providers()
            ->where('provider', 'github')
            ->first();

        if (!$user || !$github->token) {
            return redirect()->back()->with('error', 'GitHub token not available. Please reconnect your GitHub account.');
        }

        $success = $this->gitHubSkillSync->syncUserSkillsFromGitHub($user, $github);

        if (!$success) {
            return redirect()->back()->with('error', 'Failed to sync skills from GitHub. Please try again.');
        }

        return redirect()->route('profile.show')->with('success', 'Skills successfully synced from GitHub!');
    }
}
