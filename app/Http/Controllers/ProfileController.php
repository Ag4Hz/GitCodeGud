<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Http\Resources\BountyResource;
use App\Models\Bounty;
use App\Models\User;
use App\Services\SkillSyncService;
use App\Services\FollowStatsService;

use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\UserBountyService;
class ProfileController extends Controller
{
    public function __construct(
        private UserBountyService  $userBountyService,
        protected SkillSyncService $gitHubSkillSync,
        private FollowStatsService $followStatsService,
        private ReviewService      $reviewService,
    ) {}

    public function show(Request $request, ?User $user = null): Response
    {
        if (!$user) {
            $user = $request->user();
        }
        $this->followStatsService->attachCounts($user);
        $canReview = $this->reviewService->canUserReview($user);
        $bounties = $this->userBountyService->getUserBountiesWithDeleted($user);

        $connectedProviders = $user->providers()
            ->pluck('provider')
            ->unique()
            ->values()
            ->toArray();

        // Fallback to legacy OAuth provider if no user_providers exist
        if (empty($connectedProviders) && $user->oauth_provider) {
            $connectedProviders = [$user->oauth_provider];
        }

        $repoCountsByProvider = $user->repos->countBy('provider')->toArray();

        return Inertia::render('Profile', [
            'user' => array_merge(
                XPHelper::getUserWithXP($user),
                [
                    'followers_count'  => $user->followers_count,
                    'followings_count' => $user->followings_count,
                ]
            ),
            'profileUserId' => $user->id,
            'followers'  => $this->followStatsService->getFollowers($user),
            'followings' => $this->followStatsService->getFollowings($user),
            'bounties' => BountyResource::collection($bounties),
            'isFollowing' => auth()->check()? auth()->user()->isFollowing($user): false,
            'isOwner' => $request->user() && $request->user()->id === $user->id,
            'reviews'    => $this->reviewService->getUserReviews($user),
            'canReview'   => $canReview,
            'ratingAvg'   => $this->reviewService->getUserRatingStats($user)['average'],
            'connectedProviders' => $connectedProviders,
            'repoCountsByProvider' => $repoCountsByProvider,

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
