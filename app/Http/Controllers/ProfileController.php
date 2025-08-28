<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Http\Resources\BountyResource;
use App\Models\User;
use App\Services\FollowStatsService;
use App\Services\UserBountyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private UserBountyService $userBountyService,
        private FollowStatsService $followStatsService
    ) {}

    public function show(Request $request, User $user = null): Response
    {
        if (!$user) {
            $user = $request->user();
        }

        $this->followStatsService->attachCounts($user);

        $bounties = $this->userBountyService->getUserBountiesWithDeleted($user);
        return Inertia::render('Profile', [
            'user' => array_merge(
                XPHelper::getUserWithXP($user),
                [
                    'followers_count'  => $user->followers_count,
                    'followings_count' => $user->followings_count,
                ]
            ),
            'followers'  => $this->followStatsService->getFollowers($user),
            'followings' => $this->followStatsService->getFollowings($user),
            'bounties' => BountyResource::collection($bounties),
            'isFollowing' => auth()->check()? auth()->user()->isFollowing($user): false,
            'isOwner' => $request->user() && $request->user()->id === $user->id,
        ]);
    }
}
