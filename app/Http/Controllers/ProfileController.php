<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Models\Bounty;
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
        /** @var User $user */
        $user = $request->user();
        $bounties = Bounty::with(['issue.repo', 'submissions'])
            ->whereHas('issue.repo', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        $bounties->getCollection()->transform(function ($bounty) {
            $bounty->submissions_count = $bounty->submissions->count();
            return $bounty;
        });

        return Inertia::render('Profile', [
            'user' => XPHelper::getUserWithXP($user),
            'bounties' => $bounties,
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
