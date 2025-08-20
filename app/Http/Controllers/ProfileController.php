<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Models\User;
use App\Services\GitHubSkillSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected GitHubSkillSyncService $gitHubSkillSync
    ) {}

    public function show(Request $request): Response
    {
        return Inertia::render('Profile', [
            'user' => XPHelper::getUserWithXP($request->user()),
        ]);
    }

    public function syncGitHubSkills(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user || !$user->oauth_provider_token) {
            return redirect()->back()->with('error', 'GitHub token not available. Please reconnect your GitHub account.');
        }

        $success = $this->gitHubSkillSync->syncUserSkillsFromGitHub($user);

        if (!$success) {
            return redirect()->back()->with('error', 'Failed to sync skills from GitHub. Please try again.');
        }
        return redirect()->route('profile.show')->with('success', 'Skills successfully synced from GitHub!');
    }
}
