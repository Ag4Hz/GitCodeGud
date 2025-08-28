<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Http\Resources\BountyResource;
use App\Models\Bounty;
use App\Models\User;
use App\Services\GitHubSkillSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\UserBountyService;
class ProfileController extends Controller
{
    public function __construct(
        private UserBountyService $userBountyService,
        protected GitHubSkillSyncService $gitHubSkillSync,
    ) {}

    public function show(Request $request, User $user = null): Response
    {
        if (!$user) {
            $user = $request->user();
        }

        $bounties = $this->userBountyService->getUserBountiesWithDeleted($user);
        return Inertia::render('Profile', [
            'user' => XPHelper::getUserWithXP($user),
            'bounties' => BountyResource::collection($bounties),
            'isOwner' => $request->user() && $request->user()->id === $user->id,
        ]);
    }
    public function syncGitHubSkills(Request $request): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user || !$user->oauth_provider_token) {
            return false;
        }

        return $this->gitHubSkillSync->syncUserSkillsFromGitHub($user);
    }
    public function handleGitHubSkillsSync(Request $request): RedirectResponse
    {
        $success = $this->syncGitHubSkills($request);

        if (!$success) {
            return redirect()->back()->with('error', 'Failed to sync skills from GitHub. GitHub token may not be available or sync failed. Please try again.');
        }

        return redirect()->route('profile.show')->with('success', 'Skills successfully synced from GitHub!');
    }
}
